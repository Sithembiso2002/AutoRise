<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

final class CsrfMiddleware
{
    private const METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, callable $next): Response
    {
        if (!in_array($request->method(), self::METHODS, true)) {
            return $next($request);
        }

        $token = $request->input('_token') ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

        if (!Csrf::verify(is_string($token) ? $token : null)) {
            return Response::error(419, 'Your session has expired or the form token was invalid. Please try again.');
        }

        return $next($request);
    }
}