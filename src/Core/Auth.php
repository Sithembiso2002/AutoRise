<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private const MAX_ATTEMPTS    = 5;
    private const LOCKOUT_SECONDS = 900; // 15 minutes

    /**
     * Attempt login. Returns the user array on success, null on failure.
     */
    public static function attempt(string $email, string $password, string $ip): ?array
    {
        $email = strtolower(trim($email));

        if (self::isLocked($email, $ip)) {
            return null;
        }

        $user = db()->selectOne(
            'SELECT * FROM users WHERE email = :e LIMIT 1',
            ['e' => $email]
        );

        if (!$user || (int) $user['is_active'] !== 1) {
            self::recordFailure($email, $ip);
            return null;
        }

        if (!password_verify($password, (string) $user['password'])) {
            self::recordFailure($email, $ip);
            return null;
        }

        // Upgrade hash if we've increased cost since this row was written
        if (password_needs_rehash((string) $user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            db()->update(
                'users',
                ['password' => $newHash],
                'user_id = :id',
                ['id' => (int) $user['user_id']]
            );
        }

        self::login($user);
        self::clearFailures($email, $ip);
        return $user;
    }

    public static function login(array $user): void
    {
        Session::regenerate();

        Session::put('user', [
            'id'       => (int) $user['user_id'],
            'email'    => $user['email'],
            'name'     => $user['name'],
            'username' => $user['username'] ?? null,
            'role'     => $user['role'],
            'avatar'   => $user['avatar'] ?? null,
        ]);
        Session::put('_created', time());

        db()->update(
            'users',
            [
                'last_login_at' => date('Y-m-d H:i:s'),
            ],
            'user_id = :id',
            ['id' => (int) $user['user_id']]
        );
    }

    public static function logout(): void
    {
        if (self::check()) {
            $sid = session_id();
            if ($sid) {
                try {
                    db()->execute(
                        'DELETE FROM user_sessions WHERE session_id = :s',
                        ['s' => $sid]
                    );
                } catch (\Throwable) {
                    // table may not exist yet Ã¢â‚¬â€ ignore
                }
            }
        }
        Session::destroy();
    }

    public static function user(): ?array
    {
        return Session::get('user');
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u['id'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u['role'] ?? null;
    }

    public static function is(string ...$roles): bool
    {
        $role = self::role();
        return $role !== null && in_array($role, $roles, true);
    }

    public static function isAdmin(): bool
    {
        return self::is('admin', 'manager');
    }

    public static function isStaff(): bool
    {
        return self::is('admin', 'manager', 'staff');
    }

    // ---------- brute force tracking ----------

    private static function isLocked(string $email, string $ip): bool
    {
        try {
            $count = (int) db()->scalar(
                'SELECT COUNT(*) FROM login_attempts
                 WHERE (email = :e OR ip_address = :i)
                   AND attempted_at > :since',
                [
                    'e'     => $email,
                    'i'     => $ip,
                    'since' => date('Y-m-d H:i:s', time() - self::LOCKOUT_SECONDS),
                ]
            );
            return $count >= self::MAX_ATTEMPTS;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function recordFailure(string $email, string $ip): void
    {
        try {
            db()->insert('login_attempts', [
                'email'        => $email,
                'ip_address'   => $ip,
                'attempted_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // ignore
        }
    }

    private static function clearFailures(string $email, string $ip): void
    {
        try {
            db()->execute(
                'DELETE FROM login_attempts WHERE email = :e AND ip_address = :i',
                ['e' => $email, 'i' => $ip]
            );
        } catch (\Throwable) {
            // ignore
        }
    }
}