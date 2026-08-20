<?php
declare(strict_types=1);

namespace App\Payments;

interface PaymentGateway
{
    /** @param array<string, mixed> $payload */
    public function charge(array $payload): array;

    public function isEnabled(): bool;
}
