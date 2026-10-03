<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

final class ProfileController
{
    private const AVATAR_DIR    = 'assets/uploads/avatars';
    private const AVATAR_MAX_MB = 2;
    private const AVATAR_MIMES  = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /* ============================================================
       OVERVIEW
       ============================================================ */

    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user   = $this->loadUser($userId);

        $stats = [
            'orders'  => (int) db()->scalar(
                'SELECT COUNT(*) FROM orders WHERE user_id = :id',
                ['id' => $userId]
            ),
            'spent'   => (float) db()->scalar(
                "SELECT COALESCE(SUM(total_amount),0) FROM orders
                 WHERE user_id = :id AND order_status IN ('paid','processing','shipped','delivered')",
                ['id' => $userId]
            ),
            'pending' => (int) db()->scalar(
                "SELECT COUNT(*) FROM orders WHERE user_id = :id AND order_status = 'pending'",
                ['id' => $userId]
            ),
        ];

        $recentOrders = db()->select(
            'SELECT * FROM orders WHERE user_id = :id ORDER BY order_date DESC LIMIT 5',
            ['id' => $userId]
        );

        return Response::html(view('shop.profile.overview', [
            'title'        => 'My Profile | ' . brand_name(),
            'user'         => $user,
            'stats'        => $stats,
            'recentOrders' => $recentOrders,
        ]));
    }

    /* ============================================================
       ORDERS LIST
       ============================================================ */

    public function orders(Request $request): Response
    {
        $userId = (int) Auth::id();
        $orders = db()->select(
            'SELECT * FROM orders WHERE user_id = :id ORDER BY order_date DESC',
            ['id' => $userId]
        );

        return Response::html(view('shop.profile.orders', [
            'title'  => 'My Orders | ' . brand_name(),
            'user'   => $this->loadUser($userId),
            'orders' => $orders,
        ]));
    }

    /* ============================================================
       SETTINGS
       ============================================================ */

    public function settings(Request $request): Response
    {
        $userId = (int) Auth::id();

        return Response::html(view('shop.profile.settings', [
            'title' => 'Settings | ' . brand_name(),
            'user'  => $this->loadUser($userId),
        ]));
    }

    public function updateSettings(Request $request): Response
    {
        $userId = (int) Auth::id();

        $data = [
            'name'     => $request->str('name'),
            'email'    => strtolower($request->str('email')),
            'phone'    => $request->str('phone'),
            'address'  => $request->str('address'),
            'city'     => $request->str('city'),
            'zip_code' => $request->str('zip_code'),
            'country'  => $request->str('country'),
        ];

        $validator = Validator::make($data, [
            'name'     => 'required|min:2|max:150',
            'email'    => 'required|email|max:190|unique_except:users,email,user_id,' . $userId,
            'phone'    => 'nullable|max:30',
            'address'  => 'nullable|max:255',
            'city'     => 'nullable|max:100',
            'zip_code' => 'nullable|max:20',
            'country'  => 'nullable|max:100',
        ]);

        if ($validator->fails()) {
            flash('error', (string) $validator->firstError());
            return Response::redirect(url('profile/settings'));
        }

        db()->update(
            'users',
            $data + ['updated_at' => date('Y-m-d H:i:s')],
            'user_id = :id',
            ['id' => $userId]
        );

        $this->refreshSession($userId);
        flash('success', 'Your profile has been updated.');
        return Response::redirect(url('profile/settings'));
    }

    /* ============================================================
       AVATAR
       ============================================================ */

    public function uploadAvatar(Request $request): Response
    {
        $userId = (int) Auth::id();
        $file   = $request->file('avatar');

        if (!$file || !isset($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Please select an image to upload.');
            return Response::redirect(url('profile/settings'));
        }

        $sizeMb = $file['size'] / 1024 / 1024;
        if ($sizeMb > self::AVATAR_MAX_MB) {
            flash('error', 'Image is too large. Maximum ' . self::AVATAR_MAX_MB . 'MB.');
            return Response::redirect(url('profile/settings'));
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file((string) $file['tmp_name']);

        if (!isset(self::AVATAR_MIMES[$mime])) {
            flash('error', 'Only JPG, PNG, or WebP images are allowed.');
            return Response::redirect(url('profile/settings'));
        }

        if (@getimagesize((string) $file['tmp_name']) === false) {
            flash('error', 'That file does not appear to be a valid image.');
            return Response::redirect(url('profile/settings'));
        }

        $ext = self::AVATAR_MIMES[$mime];
        $dir = base_path('public/' . self::AVATAR_DIR);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            flash('error', 'Upload folder is not writable.');
            return Response::redirect(url('profile/settings'));
        }

        $filename = 'u' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest     = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
            flash('error', 'Upload failed. Please try again.');
            return Response::redirect(url('profile/settings'));
        }

        $this->deleteAvatarFile($this->loadUser($userId)['avatar'] ?? null);

        $relPath = self::AVATAR_DIR . '/' . $filename;
        db()->update(
            'users',
            ['avatar' => $relPath, 'updated_at' => date('Y-m-d H:i:s')],
            'user_id = :id',
            ['id' => $userId]
        );

        $this->refreshSession($userId);
        flash('success', 'Profile picture updated.');
        return Response::redirect(url('profile/settings'));
    }

    public function removeAvatar(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user   = $this->loadUser($userId);

        $this->deleteAvatarFile($user['avatar'] ?? null);

        db()->update(
            'users',
            ['avatar' => null, 'updated_at' => date('Y-m-d H:i:s')],
            'user_id = :id',
            ['id' => $userId]
        );

        $this->refreshSession($userId);
        flash('success', 'Profile picture removed.');
        return Response::redirect(url('profile/settings'));
    }

    /* ============================================================
       PASSWORD
       ============================================================ */

    public function updatePassword(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user   = $this->loadUser($userId);

        $current = (string) $request->input('current_password', '');
        $new     = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('new_password_confirmation', '');

        if (!password_verify($current, (string) $user['password'])) {
            flash('error', 'Your current password is incorrect.');
            return Response::redirect(url('profile/settings'));
        }

        $validator = Validator::make(
            ['new_password' => $new, 'new_password_confirmation' => $confirm],
            ['new_password' => 'required|min:8|confirmed']
        );

        if ($validator->fails()) {
            flash('error', (string) $validator->firstError());
            return Response::redirect(url('profile/settings'));
        }

        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        db()->update(
            'users',
            ['password' => $hash, 'updated_at' => date('Y-m-d H:i:s')],
            'user_id = :id',
            ['id' => $userId]
        );

        Csrf::rotate();
        flash('success', 'Password changed successfully.');
        return Response::redirect(url('profile/settings'));
    }

    /* ============================================================
       PRIVATE HELPERS
       ============================================================ */

    private function loadUser(int $userId): ?array
    {
        return db()->selectOne('SELECT * FROM users WHERE user_id = :id', ['id' => $userId]);
    }

    private function refreshSession(int $userId): void
    {
        $fresh = $this->loadUser($userId);
        if ($fresh) {
            $_SESSION['user'] = [
                'id'       => (int) $fresh['user_id'],
                'email'    => $fresh['email'],
                'name'     => $fresh['name'],
                'username' => $fresh['username'] ?? null,
                'role'     => $fresh['role'],
                'avatar'   => $fresh['avatar'] ?? null,
            ];
        }
    }

    private function deleteAvatarFile(?string $relPath): void
    {
        if (!$relPath) return;

        $clean = ltrim(str_replace('\\', '/', $relPath), '/');
        if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://')) return;

        $full = base_path('public/' . $clean);
        if (is_file($full)) {
            @unlink($full);
        }
    }
}