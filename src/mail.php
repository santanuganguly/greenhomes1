<?php
/**
 * Tiny mail wrapper. In dev, appends to storage/mail/*.eml instead of sending.
 * Returns true if sent (or logged).
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function send_mail(string $to, string $subject, string $bodyHtml, ?string $replyTo = null): bool
{
    $from = (string) gh_env('GH_NOTIFY_FROM', 'Green Homes <no-reply@thegreen.homes>');
    $logOnly = gh_env('GH_MAIL_LOG_ONLY', gh_is_dev() ? 'true' : 'false') === 'true';

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from,
    ];
    if ($replyTo) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    $headersStr = implode("\r\n", $headers);

    if ($logOnly) {
        $dir = dirname(__DIR__) . '/storage/mail';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/' . date('Y-m-d_His') . '_' . substr(md5($subject . microtime(true)), 0, 8) . '.eml';
        $payload = "To: $to\r\nSubject: $subject\r\n$headersStr\r\n\r\n$bodyHtml\r\n";
        return (bool) file_put_contents($file, $payload);
    }

    return @mail($to, $subject, $bodyHtml, $headersStr);
}
