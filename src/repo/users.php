<?php
/**
 * User repository.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function users_create(string $name, string $email, string $phone, string $password): int
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare('INSERT INTO users (name, email, phone, password_hash) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, $phone, $hash]);
    return (int) db()->lastInsertId();
}

function users_find_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT id, name, email, phone, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    return $u ?: null;
}

function users_email_taken(string $email): bool
{
    $stmt = db()->prepare('SELECT 1 FROM users WHERE email = ?');
    $stmt->execute([$email]);
    return (bool) $stmt->fetchColumn();
}
