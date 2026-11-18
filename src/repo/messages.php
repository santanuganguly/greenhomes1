<?php
/**
 * Contact-message repository.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function messages_create(string $name, string $email, ?string $phone, ?string $subject, string $body): int
{
    $stmt = db()->prepare('INSERT INTO contact_messages (name, email, phone, subject, body) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$name, $email, $phone, $subject, $body]);
    return (int) db()->lastInsertId();
}

function messages_all(): array
{
    return db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
}
