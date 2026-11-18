<?php
/**
 * Quote-request repository.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function quotes_create(array $data): int
{
    $sql = 'INSERT INTO quote_requests
        (user_id, name, email, phone, city, service_type, plot_size, bhk_type, budget_band, package, estimate_low, estimate_high, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = db()->prepare($sql);
    $stmt->execute([
        $data['user_id'] ?? null,
        $data['name'],
        $data['email'],
        $data['phone'],
        $data['city'],
        $data['service_type'],
        $data['plot_size'] ?? null,
        $data['bhk_type'] ?? null,
        $data['budget_band'] ?? null,
        $data['package'] ?? null,
        $data['estimate_low'] ?? null,
        $data['estimate_high'] ?? null,
        $data['notes'] ?? null,
    ]);
    return (int) db()->lastInsertId();
}

function quotes_for_user(int $userId): array
{
    $stmt = db()->prepare('SELECT * FROM quote_requests WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function quotes_all(): array
{
    return db()->query('SELECT * FROM quote_requests ORDER BY created_at DESC')->fetchAll();
}

function quotes_get(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM quote_requests WHERE id = ?');
    $stmt->execute([$id]);
    $q = $stmt->fetch();
    return $q ?: null;
}

function quotes_update_status(int $id, string $status): bool
{
    $allowed = ['new', 'contacted', 'won', 'lost'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }
    $stmt = db()->prepare('UPDATE quote_requests SET status = ? WHERE id = ?');
    return $stmt->execute([$status, $id]);
}

function quotes_stats(): array
{
    $row = db()->query('SELECT COUNT(*) AS total FROM quote_requests')->fetch();
    $week = db()->query("SELECT COUNT(*) AS c FROM quote_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch();
    $won = db()->query("SELECT COUNT(*) AS c FROM quote_requests WHERE status = 'won'")->fetch();
    $total = (int) $row['total'];
    return [
        'total' => $total,
        'week' => (int) $week['c'],
        'won' => (int) $won['c'],
        'conversion' => $total > 0 ? round(((int) $won['c'] / $total) * 100, 1) : 0.0,
    ];
}
