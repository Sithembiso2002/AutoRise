<?php
declare(strict_types=1);

namespace App\Services\Payments;

use App\Core\Config;

final class GatewayFactory
{
    public static function make(?string $name = null): PaymentGateway
    {
        $name ??= (string) Config::get('payment.default', 'mock');

        return match ($name) {
            'stripe' => new StripeGateway(),
            'mock'   => new MockGateway(),
            default  => throw new \RuntimeException("Unknown payment gateway: {$name}"),
        };
    }
}