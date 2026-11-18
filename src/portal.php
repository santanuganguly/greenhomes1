<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/push.php';

function notify_user(int $userId, string $type, string $title, string $message, ?string $link = null, ?int $projectId = null): void
{
    $db = db();
    $stmt = $db->prepare(
        "INSERT INTO notifications (user_id, project_id, type, title, message, link) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $projectId, $type, $title, $message, $link]);

    // Send FCM push notification if user has an active token
    $userStmt = $db->prepare("SELECT fcm_token FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $token = $userStmt->fetchColumn();
    if (!empty($token) && function_exists('send_push_notification')) {
        send_push_notification((string)$token, $title, strip_tags($message), [
            'link' => $link ?? '/dashboard',
            'type' => $type,
            'project_id' => $projectId ?? 0
        ]);
    }
}

function notify_project_users(int $projectId, string $type, string $title, string $message, ?string $link = null): void
{
    $stmt = db()->prepare(
        "SELECT DISTINCT u.id FROM users u
         JOIN projects p ON (p.client_id = u.id OR p.manager_id = u.id OR p.engineer_id = u.id)
         WHERE p.id = ?"
    );
    $stmt->execute([$projectId]);
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Also notify all admins
    $admins = db()->query("SELECT id FROM users WHERE role IN ('admin','super_admin')")->fetchAll(PDO::FETCH_COLUMN);
    $users = array_unique(array_merge($users, $admins));

    foreach ($users as $uid) {
        notify_user((int) $uid, $type, $title, $message, $link, $projectId);
    }
}

function audit_log(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
{
    $user = current_user();
    $stmt = db()->prepare(
        "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $user['id'] ?? null,
        $action,
        $entityType,
        $entityId,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
}

function get_unread_notification_count(int $userId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function get_recent_notifications(int $userId, int $limit = 10): array
{
    $stmt = db()->prepare(
        "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function mark_notification_read(int $notificationId, int $userId): void
{
    $stmt = db()->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
}

function mark_all_notifications_read(int $userId): void
{
    $stmt = db()->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
}

function get_role_dashboard(string $role): string
{
    return match ($role) {
        'super_admin', 'admin' => '/admin',
        'head_engineer' => '/pm',
        'site_engineer' => '/engineer',
        default => '/dashboard',
    };
}

function get_role_label(string $role): string
{
    return match ($role) {
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'head_engineer' => 'Head Engineer',
        'site_engineer' => 'Site Engineer',
        'client' => 'Client',
        default => 'User',
    };
}

function get_client_project(int $clientId): ?array
{
    $stmt = db()->prepare(
        "SELECT p.*,
         (SELECT COUNT(*) FROM milestones WHERE project_id = p.id AND status = 'completed') as completed_milestones,
         (SELECT COUNT(*) FROM milestones WHERE project_id = p.id) as total_milestones
         FROM projects p
         WHERE p.client_id = ? AND p.status != 'completed'
         ORDER BY p.created_at DESC LIMIT 1"
    );
    $stmt->execute([$clientId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    return $project ?: null;
}

function get_project_for_user(int $userId, string $role): array
{
    return match ($role) {
        'client' => get_client_project($userId),
        default => null,
    };
}

function format_currency(float $amount): string
{
    return '₹' . number_format($amount, 2);
}

function time_ago(string $datetime): string
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $timestamp);
}
