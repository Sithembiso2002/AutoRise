<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditLogger;

final class UserController
{
    private const ROLES = ['customer', 'staff', 'manager', 'admin'];

    public function index(Request $request): Response
    {
        $q    = $request->str('q');
        $role = $request->str('role');
        $page = max(1, $request->int('page', 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $where  = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(name LIKE :q1 OR email LIKE :q2 OR username LIKE :q3)';
            $params['q1'] = '%' . $q . '%';
            $params['q2'] = '%' . $q . '%';
            $params['q3'] = '%' . $q . '%';
        }
        if (in_array($role, self::ROLES, true)) {
            $where[] = 'role = :role';
            $params['role'] = $role;
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) db()->scalar("SELECT COUNT(*) FROM users WHERE {$whereSql}", $params);

        $rows = db()->select(
            "SELECT user_id, username, name, email, role, is_active, avatar,
                    last_login_at, created_at
             FROM users
             WHERE {$whereSql}
             ORDER BY user_id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return Response::html(view('admin.users.index', [
            'title'    => 'Users | Admin',
            'active'   => 'users',
            'rows'     => $rows,
            'q'        => $q,
            'role'     => $role,
            'page'     => $page,
            'perPage'  => $perPage,
            'total'    => $total,
            'roles'    => self::ROLES,
        ]));
    }

    public function updateRole(Request $request, string $id): Response
    {
        $id   = (int) $id;
        $role = $request->str('role');

        if (!in_array($role, self::ROLES, true)) {
            flash('error', 'Invalid role.');
            return Response::redirect(url('admin/users'));
        }

        if ($id === Auth::id()) {
            flash('error', 'You cannot change your own role.');
            return Response::redirect(url('admin/users'));
        }

        $user = db()->selectOne('SELECT user_id, role FROM users WHERE user_id = :id', ['id' => $id]);
        if (!$user) return Response::notFound('User not found.');

        if ($user['role'] === 'admin' && !Auth::is('admin')) {
            flash('error', 'Only admins can change other admin roles.');
            return Response::redirect(url('admin/users'));
        }

        db()->update('users', ['role' => $role, 'updated_at' => date('Y-m-d H:i:s')], 'user_id = :id', ['id' => $id]);
        AuditLogger::log('user.role', 'user', (string) $id, ['role' => $user['role']], ['role' => $role]);
        flash('success', 'Role updated.');
        return Response::redirect(url('admin/users'));
    }

    public function toggleActive(Request $request, string $id): Response
    {
        $id = (int) $id;

        if ($id === Auth::id()) {
            flash('error', 'You cannot deactivate yourself.');
            return Response::redirect(url('admin/users'));
        }

        $user = db()->selectOne('SELECT user_id, is_active, role FROM users WHERE user_id = :id', ['id' => $id]);
        if (!$user) return Response::notFound('User not found.');

        $new = (int) $user['is_active'] === 1 ? 0 : 1;
        db()->update('users', ['is_active' => $new, 'updated_at' => date('Y-m-d H:i:s')], 'user_id = :id', ['id' => $id]);

        AuditLogger::log('user.toggle', 'user', (string) $id, ['is_active' => (int) $user['is_active']], ['is_active' => $new]);
        flash('success', 'User ' . ($new ? 'activated' : 'deactivated') . '.');
        return Response::redirect(url('admin/users'));
    }
}