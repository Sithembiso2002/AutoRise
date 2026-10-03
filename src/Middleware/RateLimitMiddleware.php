<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;

final class RateLimitMiddleware
{
    public function handle(Request $request, callable $next, string $bucket = 'default', int $max = 60, int $window = 60): Response
    {
        $identifier = Auth::id() !== null
            ? 'user:' . Auth::id()
            : 'ip:' . $request->ip();

        $result = RateLimiter::check($bucket, $identifier, $max, $window);

        if (!$result['allowed']) {
            $retry = (int) $result['retry_after'];
            $minutes = ceil($retry / 60);

            return Response::error(
                429,
                'Too many requests. Please try again in ' . $minutes . ' minute(s).'
            )->withHeader('Retry-After', (string) $retry);
        }

        return $next($request);
    }
}