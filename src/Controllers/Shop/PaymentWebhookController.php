<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Request;
use App\Core\Response;
use App\Services\Payments\GatewayFactory;
use App\Services\PaymentService;

final class PaymentWebhookController
{
    public function __construct(private readonly PaymentService $payments = new PaymentService()) {}

    public function handle(Request $request, string $provider): Response
    {
        $payload = file_get_contents('php://input') ?: '';
        if ($payload === '') {
            return Response::json(['error' => 'Empty payload'], 400);
        }

        // Collect headers into an array
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = str_replace('_', '-', substr($k, 5));
                $headers[$name] = $v;
            }
        }

        try {
            $gateway = GatewayFactory::make($provider);
            $event   = $gateway->parseWebhook($payload, $headers);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 400);
        }

        try {
            $this->payments->handleWebhookPayload($event);
        } catch (\Throwable $e) {
            return Response::json(['error' => 'Handler failed: ' . $e->getMessage()], 500);
        }

        return Response::json(['received' => true]);
    }
}