<?php
declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Auth\Auth;
use App\Notify\Notifier;
use App\Payments\PayNowGateway;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;

final class PaymentController
{
    public function show(string $reference): void
    {
        Auth::requireLogin();
        $tx = Database::fetch('SELECT * FROM payment_transactions WHERE reference = ? AND user_id = ?', [$reference, Auth::id()]);
        if (!$tx) {
            http_response_code(404);
            View::make('public/404', ['title' => 'Payment not found'], 'layouts/app');
            return;
        }
        View::make('app/payments/show', [
            'title' => 'Registration payment',
            'tx' => $tx,
            'gateway' => new PayNowGateway(),
        ], 'layouts/app');
    }

    public function simulatePay(string $reference): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        if (\App\Support\Env::get('APP_ENV') === 'production' && !(new PayNowGateway())->isEnabled()) {
            Response::flash('error', 'Live payments must complete via PayNow.');
            Response::redirect('/app/payments/' . $reference);
        }
        $tx = Database::fetch('SELECT * FROM payment_transactions WHERE reference = ? AND user_id = ?', [$reference, Auth::id()]);
        if (!$tx) {
            Response::flash('error', 'Payment not found.');
            Response::redirect('/app');
        }
        (new PayNowGateway())->markPaid($reference);
        Notifier::notifyUser(Auth::id(), 'Payment received', 'Your ZBIF registration payment was recorded.', '/app');
        Response::flash('success', 'Payment marked paid.');
        Response::redirect('/app/payments/' . $reference);
    }

    public function returnFromPaynow(): void
    {
        Auth::requireLogin();
        $ref = (string) ($_GET['ref'] ?? '');
        if ($ref !== '') {
            Response::redirect('/app/payments/' . urlencode($ref));
        }
        Response::redirect('/app');
    }

    public function paynowResult(): void
    {
        // PayNow server-to-server callback (best-effort)
        $ref = (string) ($_POST['reference'] ?? $_GET['reference'] ?? '');
        $status = strtolower((string) ($_POST['status'] ?? ''));
        if ($ref !== '' && in_array($status, ['paid', 'awaiting delivery', 'delivered'], true)) {
            (new PayNowGateway())->markPaid($ref);
        }
        echo 'OK';
    }

    public function startRegistrationPayment(): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        $profile = Database::fetch(
            'SELECT * FROM participation_profiles WHERE user_id = ? ORDER BY id DESC LIMIT 1',
            [Auth::id()]
        );
        if (!$profile) {
            Response::flash('error', 'No registration profile found.');
            Response::redirect('/app');
        }
        if (in_array($profile['payment_status'], ['paid', 'waived', 'not_required'], true) && (float) $profile['registration_fee'] <= 0) {
            Response::flash('success', 'No payment required.');
            Response::redirect('/app');
        }
        $user = Auth::user();
        $fee = (float) $profile['registration_fee'];
        if ($fee <= 0) {
            $event = \App\Domain\EventContext::current();
            $fee = (float) ($event['registration_fee'] ?? 0);
        }
        if ($fee <= 0) {
            Response::flash('success', 'Registration is free for this edition.');
            Response::redirect('/app');
        }
        $gw = new PayNowGateway();
        $result = $gw->charge([
            'user_id' => Auth::id(),
            'participation_profile_id' => (int) $profile['id'],
            'amount' => $fee,
            'email' => $user['email'] ?? '',
            'description' => 'ZBIF InnovaMatch registration',
        ]);
        if (!empty($result['redirect_url']) && ($result['status'] ?? '') !== 'skipped') {
            Response::redirect($result['redirect_url']);
        }
        if (($result['status'] ?? '') === 'skipped') {
            $gw->markWaived((int) $profile['id'], Auth::id(), $fee);
            Response::flash('success', 'Payments dormant: fee waived for this environment.');
            Response::redirect('/app');
        }
        Response::redirect('/app/payments/' . ($result['reference'] ?? ''));
    }
}
