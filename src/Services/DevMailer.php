<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

final class DevMailer
{
    /**
     * Send a "password reset" email.
     * In local dev this just writes to storage/logs/mail.log so you can
     * copy the reset link. Swap for a real mailer later.
     */
    public static function sendPasswordReset(string $to, string $name, string $resetUrl): void
    {
        $dir = base_path('storage/logs');
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $log = $dir . '/mail.log';

        $entry = str_repeat('=', 72) . "\n"
               . 'Date:    ' . date('Y-m-d H:i:s') . "\n"
               . 'To:      ' . $to . ' (' . $name . ")\n"
               . 'Subject: Reset your ' . brand_name() . " password\n"
               . 'Link:    ' . $resetUrl . "\n"
               . str_repeat('=', 72) . "\n\n";

        @file_put_contents($log, $entry, FILE_APPEND);

        Logger::info('Password reset email "sent" (dev)', [
            'to'   => $to,
            'link' => $resetUrl,
        ]);

        // Also drop a copy in a plain-text "inbox" file that's easy to open:
        @file_put_contents($dir . '/inbox.txt', $entry, FILE_APPEND);
    }
}