<?php
declare(strict_types=1);

namespace App\Core;

final class RateLimiter
{
    /**
     * Check if $identifier is under the limit for $bucket.
     *
     * Returns ['allowed' => bool, 'remaining' => int, 'retry_after' => int].
     */
    public static function check(string $bucket, string $identifier, int $maxAttempts, int $windowSeconds): array
    {
        $now = time();

        try {
            $row = db()->selectOne(
                'SELECT id, hits, reset_at FROM rate_limits
                 WHERE bucket = :b AND identifier = :i',
                ['b' => $bucket, 'i' => $identifier]
            );

            if (!$row) {
                db()->insert('rate_limits', [
                    'bucket'     => $bucket,
                    'identifier' => $identifier,
                    'hits'       => 1,
                    'reset_at'   => date('Y-m-d H:i:s', $now + $windowSeconds),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                return ['allowed' => true, 'remaining' => $maxAttempts - 1, 'retry_after' => 0];
            }

            $resetTs = strtotime((string) $row['reset_at']);

            // Window expired → reset
            if ($now >= $resetTs) {
                db()->update(
                    'rate_limits',
                    [
                        'hits'       => 1,
                        'reset_at'   => date('Y-m-d H:i:s', $now + $windowSeconds),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ],
                    'id = :id',
                    ['id' => (int) $row['id']]
                );
                return ['allowed' => true, 'remaining' => $maxAttempts - 1, 'retry_after' => 0];
            }

            $hits = (int) $row['hits'];

            if ($hits >= $maxAttempts) {
                return [
                    'allowed'     => false,
                    'remaining'   => 0,
                    'retry_after' => max(1, $resetTs - $now),
                ];
            }

            db()->update(
                'rate_limits',
                ['hits' => $hits + 1, 'updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => (int) $row['id']]
            );

            return [
                'allowed'     => true,
                'remaining'   => $maxAttempts - $hits - 1,
                'retry_after' => 0,
            ];
        } catch (\Throwable) {
            // Never block traffic if rate limiter fails
            return ['allowed' => true, 'remaining' => $maxAttempts, 'retry_after' => 0];
        }
    }

    /**
     * Clear a bucket for an identifier (e.g. on successful login).
     */
    public static function clear(string $bucket, string $identifier): void
    {
        try {
            db()->execute(
                'DELETE FROM rate_limits WHERE bucket = :b AND identifier = :i',
                ['b' => $bucket, 'i' => $identifier]
            );
        } catch (\Throwable) {
            // ignore
        }
    }
}