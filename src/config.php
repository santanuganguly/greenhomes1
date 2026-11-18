<?php
/**
 * Loads environment variables from .env (if present) and exposes a single
 * db() helper that returns a shared PDO connection.
 *
 * In dev, .env is read from the project root. In production, set real env vars.
 */

declare(strict_types=1);

const GH_ENV_KEYS = [
    'GH_DB_HOST', 'GH_DB_PORT', 'GH_DB_NAME', 'GH_DB_USER', 'GH_DB_PASS',
    'GH_APP_URL', 'GH_APP_ENV',
    'GH_CONTACT_PHONE', 'GH_CONTACT_EMAIL', 'GH_CONTACT_ADDRESS',
    'GH_NOTIFY_TO', 'GH_NOTIFY_FROM', 'GH_MAIL_LOG_ONLY', 'GH_GOOGLE_SHEETS_API_URL',
    'GH_BANK_NAME', 'GH_ACCOUNT_NAME', 'GH_ACCOUNT_NO', 'GH_IFSC', 'GH_UPI_ID', 'GH_GSTIN',
];

function gh_load_env(string $rootDir): void
{
    $envFile = $rootDir . '/.env';
    if (!is_readable($envFile)) {
        return;
    }
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $k = trim($k);
        $v = trim($v);
        if (strlen($v) >= 2 && $v[0] === '"' && substr($v, -1) === '"') {
            $v = substr($v, 1, -1);
        }
        if (in_array($k, GH_ENV_KEYS, true) && getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
}

function gh_env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    if ($v === false || $v === '') {
        return $default;
    }
    return $v;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        gh_env('GH_DB_HOST', '127.0.0.1'),
        gh_env('GH_DB_PORT', '3306'),
        gh_env('GH_DB_NAME', 'green_homes')
    );
    $pdo = new PDO($dsn, gh_env('GH_DB_USER', 'root'), gh_env('GH_DB_PASS', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function gh_db(): PDO
{
    return db();
}

function gh_is_dev(): bool
{
    return gh_env('GH_APP_ENV', 'development') === 'development';
}

function gh_app_url(): string
{
    return rtrim((string) gh_env('GH_APP_URL', 'http://127.0.0.1:8000'), '/');
}
