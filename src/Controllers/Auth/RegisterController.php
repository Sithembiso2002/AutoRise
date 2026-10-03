<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

final class RegisterController
{
    public function show(Request $request): Response
    {
        return Response::html(view('auth.register', [
            'title' => 'Sign Up | BuyCar',
        ]));
    }

    public function store(Request $request): Response
    {
        $data = [
            'username' => $request->str('username'),
            'email'    => strtolower($request->str('email')),
            'first_name' => $request->str('first_name'),
            'last_name'  => $request->str('last_name'),
            'password' => (string) $request->input('password', ''),
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
        ];

        $validator = Validator::make($data, [
            'username'   => 'required|min:3|max:60|alpha_dash|unique:users,username',
            'email'      => 'required|email|max:190|unique:users,email',
            'first_name' => 'required|min:1|max:100',
            'last_name'  => 'required|min:1|max:100',
            'password'   => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            remember_old([
                'username'   => $data['username'],
                'email'      => $data['email'],
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
            ]);
            flash('error', (string) $validator->firstError());
            return Response::redirect(url('register'));
        }

        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $fullName = trim($data['first_name'] . ' ' . $data['last_name']);

        $userId = db()->insert('users', [
            'username'   => $data['username'],
            'name'       => $fullName,
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'password'   => $hash,
            'role'       => 'customer',
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $user = db()->selectOne('SELECT * FROM users WHERE user_id = :id', ['id' => $userId]);

        if ($user) {
            Auth::login($user);
            Csrf::rotate();
            flash('success', 'Welcome to BuyCar, ' . $user['name'] . '!');
            return Response::redirect(url('/'));
        }

        flash('error', 'Something went wrong creating your account. Please try again.');
        return Response::redirect(url('register'));
    }
}