<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

final class LogoutController
{
    public function destroy(Request $request): Response
    {
        Auth::logout();
        flash('success', 'You have been logged out.');
        return Response::redirect(url('/'));
    }
}