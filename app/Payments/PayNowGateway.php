<?php
declare(strict_types=1);

namespace App\Payments;

use App\Support\Database;
use App\Support\Env;
use App\Support\Url;

final class PayNowGateway implements PaymentGateway
{
    public function isEnabled(): bool
    {
        return Env::bool('PAYMENTS_ENABLED', false) && (bool) Env::get('PAYNOW_INTEGRATION_ID');
    }

    public function charge(array $payload): array
    {
        if (!$this->isEnabled()) {
            return [
                'status' => 'skipped',
                'message' => 'Payments dormant; registration fee treated as free or waived.',
                'reference' => null,
            ];
        }

        $amount = (float) ($payload['amount'] ?? 0);
        $email = (string) ($payload['email'] ?? '');
        $userId = (int) ($payload['user_id'] ?? 0);
        $profileId = isset($payload['participation_profile_id']) ? (int) $payload['participation_profile_id'] : null;
        $reference = 'ZBIF-' . strtoupper(bin2hex(random_bytes(5)));

        // Initiate against PayNow when credentials present. Falls back to local pending if remote fails.
        $result = $this->initiateRemote($reference, $amount, $email, (string) ($payload['description'] ?? 'ZBIF registration'));
        $status = $result['status'] ?? 'pending';

        Database::query(
            'INSERT INTO payment_transactions (user_id, participation_profile_id, gateway, reference, amount, currency, status, poll_url, raw_json, created_at, updated_at)
             VALUES (?, ?, \'paynow\', ?, ?, \'USD\', ?, ?, ?, NOW(), NOW())',
            [
                $userId,
                $profileId,
                $reference,
                $amount,
                $status,
                $result['poll_url'] ?? null,
                json_encode($result),
            ]
        );

        if ($profileId) {
            Database::query(
                'UPDATE participation_profiles SET payment_status = ?, payment_reference = ?, registration_fee = ?, updated_at = NOW() WHERE id = ?',
                [$status === 'paid' ? 'paid' : 'pending', $reference, $amount, $profileId]
            );
        }

        return [
            'status' => $status,
            'reference' => $reference,
            'redirect_url' => $result['redirect_url'] ?? Url::to('/app/payments/' . $reference),
            'message' => $result['message'] ?? 'Payment initiated.',
        ];
    }

    public function markPaid(string $reference): bool
    {
        $tx = Database::fetch('SELECT * FROM payment_transactions WHERE reference = ?', [$reference]);
        if (!$tx) {
            return false;
        }
        Database::query(
            'UPDATE payment_transactions SET status = \'paid\', updated_at = NOW() WHERE id = ?',
            [$tx['id']]
        );
        if (!empty($tx['participation_profile_id'])) {
            Database::query(
                'UPDATE participation_profiles SET payment_status = \'paid\', updated_at = NOW() WHERE id = ?',
                [$tx['participation_profile_id']]
            );
        }
        return true;
    }

    public function markWaived(int $profileId, int $userId, float $amount = 0): string
    {
        $reference = 'WAIVE-' . strtoupper(bin2hex(random_bytes(4)));
        Database::query(
            'INSERT INTO payment_transactions (user_id, participation_profile_id, gateway, reference, amount, currency, status, created_at, updated_at)
             VALUES (?, ?, \'manual\', ?, ?, \'USD\', \'waived\', NOW(), NOW())',
            [$userId, $profileId, $reference, $amount]
        );
        Database::query(
            'UPDATE participation_profiles SET payment_status = \'waived\', payment_reference = ?, updated_at = NOW() WHERE id = ?',
            [$reference, $profileId]
        );
        return $reference;
    }

    public function health(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'integration_id_set' => (bool) Env::get('PAYNOW_INTEGRATION_ID'),
            'integration_key_set' => (bool) Env::get('PAYNOW_INTEGRATION_KEY'),
            'pending' => (int) (Database::fetch("SELECT COUNT(*) AS c FROM payment_transactions WHERE status = 'pending'")['c'] ?? 0),
            'paid' => (int) (Database::fetch("SELECT COUNT(*) AS c FROM payment_transactions WHERE status = 'paid'")['c'] ?? 0),
        ];
    }

    /** @return array<string, mixed> */
    private function initiateRemote(string $reference, float $amount, string $email, string $description): array
    {
        $id = Env::get('PAYNOW_INTEGRATION_ID');
        $key = Env::get('PAYNOW_INTEGRATION_KEY');
        if (!$id || !$key) {
            return [
                'status' => 'pending',
                'message' => 'Local pending payment (PayNow credentials incomplete). Complete or waive in portal.',
                'redirect_url' => Url::to('/app/payments/' . $reference),
            ];
        }

        // PayNow initiate transaction (hosted). Shared-host compatible via cURL.
        $returnUrl = Url::to('/app/payments/return?ref=' . urlencode($reference));
        $resultUrl = Url::to('/api/v1/payments/paynow/result');
        $fields = [
            'id' => $id,
            'reference' => $reference,
            'amount' => number_format($amount, 2, '.', ''),
            'additionalinfo' => $description,
            'returnurl' => $returnUrl,
            'resulturl' => $resultUrl,
            'status' => 'Message',
            'authemail' => $email,
        ];
        $fields['hash'] = $this->hash($fields, $key);

        $ch = curl_init('https://www.paynow.co.zw/interface/initiatetransaction');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $code >= 400) {
            return [
                'status' => 'pending',
                'message' => 'PayNow unreachable; payment held pending for manual completion.',
                'redirect_url' => Url::to('/app/payments/' . $reference),
                'http_code' => $code,
            ];
        }

        parse_str(str_replace(["\r", "\n"], '&', (string) $raw), $parsed);
        $status = strtolower((string) ($parsed['status'] ?? 'Error'));
        if ($status === 'ok' && !empty($parsed['browserurl'])) {
            return [
                'status' => 'pending',
                'message' => 'Redirect to PayNow to complete payment.',
                'redirect_url' => $parsed['browserurl'],
                'poll_url' => $parsed['pollurl'] ?? null,
                'raw' => $parsed,
            ];
        }

        return [
            'status' => 'pending',
            'message' => 'PayNow response: ' . ($parsed['error'] ?? $status),
            'redirect_url' => Url::to('/app/payments/' . $reference),
            'raw' => $parsed,
        ];
    }

    /** @param array<string, string> $values */
    private function hash(array $values, string $key): string
    {
        $string = '';
        foreach ($values as $v) {
            $string .= $v;
        }
        $string .= $key;
        return strtoupper(hash('sha512', $string));
    }
}
