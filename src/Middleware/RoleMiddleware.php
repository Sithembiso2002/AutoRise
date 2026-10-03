<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

final class RoleMiddleware
{
    public function handle(Request $request, callable $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            flash('error', 'Please log in to continue.');
            return Response::redirect(url('login'));
        }
        if (!Auth::is(...$roles)) {
            return Response::error(403, 'You do not have permission to access this page.');
        }
        return $next($request);
    }
}