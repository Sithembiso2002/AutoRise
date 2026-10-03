<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check()) {
            Session::put('_intended', $request->path());
            flash('error', 'Please log in to continue.');
            return Response::redirect(url('login'));
        }
        return $next($request);
    }
}