<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

final class ResetPasswordController
{
    public function show(Request $request, string $token): Response
    {
        $row = $this->findValidToken($token);

        if (!$row) {
            flash('error', 'This reset link is invalid or has expired. Please request a new one.');
            return Response::redirect(url('forgot-password'));
        }

        return Response::html(view('auth.reset-password', [
            'title' => 'Reset Password | ' . brand_name(),
            'token' => $token,
            'email' => $row['email'],
        ]));
    }

    public function update(Request $request, string $token): Response
    {
        $row = $this->findValidToken($token);
        if (!$row) {
            flash('error', 'This reset link is invalid or has expired.');
            return Response::redirect(url('forgot-password'));
        }

        $data = [
            'password'              => (string) $request->input('password', ''),
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
        ];

        $validator = Validator::make($data, [
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            flash('error', (string) $validator->firstError());
            return Response::redirect(url('reset-password/' . $token));
        }

        $user = db()->selectOne(
            'SELECT user_id FROM users WHERE email = :e LIMIT 1',
            ['e' => $row['email']]
        );
        if (!$user) {
            flash('error', 'Account no longer exists.');
            return Response::redirect(url('forgot-password'));
        }

        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        db()->execute(
            'UPDATE users SET password = :p, updated_at = :now WHERE user_id = :id',
            ['p' => $hash, 'now' => date('Y-m-d H:i:s'), 'id' => (int) $user['user_id']]
        );

        db()->execute(
            'UPDATE password_resets SET used_at = :now WHERE token_hash = :h',
            ['now' => date('Y-m-d H:i:s'), 'h' => hash('sha256', $token)]
        );

        // Force logout of any existing session for this user
        Auth::logout();
        Csrf::rotate();

        flash('success', 'Your password has been reset. Please log in with your new password.');
        return Response::redirect(url('login'));
    }

    private function findValidToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;

        $hashed = hash('sha256', $token);

        return db()->selectOne(
            'SELECT * FROM password_resets
             WHERE token_hash = :h AND used_at IS NULL AND expires_at > :now
             LIMIT 1',
            ['h' => $hashed, 'now' => date('Y-m-d H:i:s')]
        );
    }
}