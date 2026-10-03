<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

final class LoginController
{
    public function show(Request $request): Response
    {
        return Response::html(view('auth.login', [
            'title' => 'Login | ' . brand_name(),
        ]));
    }

    public function store(Request $request): Response
    {
        $email    = $request->str('email');
        $password = (string) $request->input('password', '');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email'    => 'required|email',
                'password' => 'required|min:6',
            ]
        );

        if ($validator->fails()) {
            remember_old(['email' => $email]);
            flash('error', (string) $validator->firstError());
            return Response::redirect(url('login'));
        }

        $user = Auth::attempt($email, $password, $request->ip());

        if (!$user) {
            remember_old(['email' => $email]);
            flash('error', 'Invalid credentials, or your account is temporarily locked.');
            return Response::redirect(url('login'));
        }

        Csrf::rotate();

        // Staff roles (admin / manager / staff) → go straight to the admin panel
        if (Auth::is('admin', 'manager', 'staff')) {
            flash('success', 'Welcome back, ' . ($user['name'] ?? 'Admin') . '!');
            return Response::redirect(url('admin'));
        }

        // Regular customers → home page, or whatever page they were trying to reach
        $redirect = intended(url('/'));
        flash('success', 'Welcome back, ' . ($user['name'] ?? 'friend') . '!');
        return Response::redirect($redirect);
    }
}