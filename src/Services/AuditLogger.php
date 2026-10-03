<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;

final class AuditLogger
{
    public static function log(string $action, ?string $entityType = null, ?string $entityId = null, ?array $before = null, ?array $after = null): void
    {
        try {
            db()->insert('admin_logs', [
                'user_id'     => Auth::id(),
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId !== null ? (string) $entityId : null,
                'before_data' => $before ? json_encode($before, JSON_UNESCAPED_SLASHES) : null,
                'after_data'  => $after  ? json_encode($after,  JSON_UNESCAPED_SLASHES) : null,
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // admin_logs may not exist in some setups — never break the request
        }
    }
}