<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\DevMailer;

final class ForgotPasswordController
{
    public function show(Request $request): Response
    {
        return Response::html(view('auth.forgot-password', [
            'title' => 'Forgot Password | ' . brand_name(),
        ]));
    }

    public function send(Request $request): Response
    {
        $email = strtolower($request->str('email'));

        $validator = Validator::make(
            ['email' => $email],
            ['email' => 'required|email']
        );

        if ($validator->fails()) {
            remember_old(['email' => $email]);
            flash('error', (string) $validator->firstError());
            return Response::redirect(url('forgot-password'));
        }

        // Always respond with "email sent" even if the user doesn't exist —
        // prevents account enumeration.
        $user = db()->selectOne(
            'SELECT user_id, name, email FROM users WHERE email = :e AND is_active = 1',
            ['e' => $email]
        );

        if ($user) {
            // Invalidate previous unused tokens for this email
            db()->execute(
                'DELETE FROM password_resets WHERE email = :e AND used_at IS NULL',
                ['e' => $email]
            );

            $plain  = bin2hex(random_bytes(32));
            $hashed = hash('sha256', $plain);
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            db()->insert('password_resets', [
                'email'      => $email,
                'token_hash' => $hashed,
                'expires_at' => $expires,
                'ip_address' => $request->ip(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $resetUrl = url('reset-password/' . $plain);

            DevMailer::sendPasswordReset($email, (string) $user['name'], $resetUrl);
        }

        flash('success', 'If an account exists with that email, we have sent a password reset link.');
        return Response::redirect(url('login'));
    }
}