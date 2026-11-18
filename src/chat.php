<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/push.php';

function chat_create_room(int $projectId): int
{
    $db = db();
    $db->prepare("INSERT IGNORE INTO project_chat_rooms (project_id) VALUES (?)")->execute([$projectId]);
    $stmt = $db->prepare("SELECT id FROM project_chat_rooms WHERE project_id = ?");
    $stmt->execute([$projectId]);
    return (int) $stmt->fetchColumn();
}

function chat_sync_members(int $roomId, int $projectId): void
{
    $db = db();
    $db->prepare("DELETE FROM project_chat_members WHERE room_id = ?")->execute([$roomId]);

    $stmt = $db->prepare("SELECT client_id, manager_id, engineer_id FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $p = $stmt->fetch();

    $members = [];
    if (!empty($p['client_id'])) $members[] = (int)$p['client_id'];
    if (!empty($p['manager_id'])) $members[] = (int)$p['manager_id'];
    if (!empty($p['engineer_id'])) $members[] = (int)$p['engineer_id'];

    $admins = $db->query("SELECT id FROM users WHERE role IN ('admin','super_admin')")->fetchAll(PDO::FETCH_COLUMN);
    $members = array_unique(array_merge($members, array_map('intval', $admins)));

    $ins = $db->prepare("INSERT IGNORE INTO project_chat_members (room_id, user_id) VALUES (?,?)");
    foreach ($members as $uid) {
        $ins->execute([$roomId, $uid]);
    }
}

function chat_get_room(int $projectId): ?array
{
    $db = db();
    $stmt = $db->prepare("SELECT r.*, p.name AS project_name, p.status AS project_status,
        cl.name AS client_name FROM project_chat_rooms r
        JOIN projects p ON r.project_id = p.id
        LEFT JOIN users cl ON p.client_id = cl.id
        WHERE r.project_id = ?");
    $stmt->execute([$projectId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function chat_get_user_rooms(int $userId): array
{
    $db = db();
    $stmt = $db->prepare(
        "SELECT r.id AS room_id, r.project_id, p.name AS project_name, p.status AS project_status,
         cl.name AS client_name,
         (SELECT COUNT(*) FROM project_chat_messages m WHERE m.room_id = r.id) AS msg_count,
         (SELECT m.message FROM project_chat_messages m WHERE m.room_id = r.id ORDER BY m.id DESC LIMIT 1) AS last_msg,
         (SELECT m.created_at FROM project_chat_messages m WHERE m.room_id = r.id ORDER BY m.id DESC LIMIT 1) AS last_msg_at,
         (SELECT COUNT(*) FROM project_chat_messages m
          JOIN message_read_status rs ON rs.message_id = m.id AND rs.user_id = ?
          WHERE m.room_id = r.id AND m.sender_id != ?) AS my_read_count,
         (SELECT COUNT(*) FROM project_chat_messages m WHERE m.room_id = r.id AND m.sender_id != ?) AS total_other_msgs
         FROM project_chat_members cm
         JOIN project_chat_rooms r ON cm.room_id = r.id
         JOIN projects p ON r.project_id = p.id
         LEFT JOIN users cl ON p.client_id = cl.id
         WHERE cm.user_id = ?
         ORDER BY last_msg_at DESC"
    );
    return $stmt->execute([$userId, $userId, $userId, $userId]) ? $stmt->fetchAll() : [];
}

function chat_get_messages(int $roomId, int $userId, int $offset = 0, int $limit = 50): array
{
    $db = db();
    $msgs = $db->prepare(
        "SELECT m.*, u.name AS sender_name, u.role AS sender_role,
         (SELECT COUNT(*) FROM message_read_status rs WHERE rs.message_id = m.id) AS read_count,
         (SELECT COUNT(*) FROM message_delivery_status ds WHERE ds.message_id = m.id) AS delivered_count,
         ((SELECT COUNT(*) FROM project_chat_members cm WHERE cm.room_id = m.room_id) - 1) AS total_members
         FROM project_chat_messages m
         JOIN users u ON m.sender_id = u.id
         WHERE m.room_id = ?
         ORDER BY m.created_at DESC
         LIMIT ? OFFSET ?"
    );
    $msgs->execute([$roomId, $limit, $offset]);
    $rows = $msgs->fetchAll();
    return array_reverse($rows);
}

function chat_send_message(int $roomId, int $senderId, string $message, ?int $replyToId = null, ?string $clientMsgId = null): int
{
    $db = db();
    if ($clientMsgId) {
        $stmt = $db->prepare("SELECT id FROM project_chat_messages WHERE client_msg_id = ?");
        $stmt->execute([$clientMsgId]);
        if ($existingId = $stmt->fetchColumn()) return (int)$existingId;
    }
    
    $db->prepare("INSERT INTO project_chat_messages (room_id, sender_id, message, reply_to_id, client_msg_id) VALUES (?,?,?,?,?)")
        ->execute([$roomId, $senderId, $message, $replyToId, $clientMsgId]);
    return (int)$db->lastInsertId();
}

function chat_send_file_message(int $roomId, int $senderId, string $message, string $filePath, string $fileName, string $fileType, int $fileSize, ?int $replyToId = null, ?string $clientMsgId = null): int
{
    $db = db();
    if ($clientMsgId) {
        $stmt = $db->prepare("SELECT id FROM project_chat_messages WHERE client_msg_id = ?");
        $stmt->execute([$clientMsgId]);
        if ($existingId = $stmt->fetchColumn()) return (int)$existingId;
    }

    $db->beginTransaction();
    $db->prepare("INSERT INTO project_chat_messages (room_id, sender_id, message, reply_to_id, client_msg_id) VALUES (?,?,?,?,?)")
        ->execute([$roomId, $senderId, $message, $replyToId, $clientMsgId]);
    $msgId = (int)$db->lastInsertId();
    $db->prepare("INSERT INTO project_chat_files (message_id, room_id, sender_id, file_name, file_path, file_type, file_size) VALUES (?,?,?,?,?,?,?)")
        ->execute([$msgId, $roomId, $senderId, $fileName, $filePath, $fileType, $fileSize]);
    $db->commit();
    return $msgId;
}

function chat_get_files(int $messageId): array
{
    $db = db();
    $stmt = $db->prepare("SELECT * FROM project_chat_files WHERE message_id = ?");
    $stmt->execute([$messageId]);
    return $stmt->fetchAll();
}

function chat_mark_read(int $messageId, int $userId): void
{
    $db = db();
    $db->prepare("INSERT IGNORE INTO message_read_status (message_id, user_id) VALUES (?,?)")
        ->execute([$messageId, $userId]);
}

function chat_mark_room_read(int $roomId, int $userId): void
{
    $db = db();
    $db->prepare(
        "INSERT IGNORE INTO message_read_status (message_id, user_id)
         SELECT m.id, ? FROM project_chat_messages m
         WHERE m.room_id = ? AND m.sender_id != ?"
    )->execute([$userId, $roomId, $userId]);

    // Mark corresponding project_chat notifications for this user/room as read
    $db->prepare(
        "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND (type = 'project_chat' OR link LIKE ?)"
    )->execute([$userId, "%room=$roomId%"]);
}

function chat_get_unread_count(int $userId, ?int $roomId = null): int
{
    $db = db();
    if ($roomId) {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM project_chat_messages m
             LEFT JOIN message_read_status rs ON rs.message_id = m.id AND rs.user_id = ?
             WHERE m.room_id = ? AND m.sender_id != ? AND rs.id IS NULL"
        );
        $stmt->execute([$userId, $roomId, $userId]);
    } else {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM project_chat_messages m
             JOIN project_chat_members cm ON cm.room_id = m.room_id AND cm.user_id = ?
             LEFT JOIN message_read_status rs ON rs.message_id = m.id AND rs.user_id = ?
             WHERE m.sender_id != ? AND rs.id IS NULL"
        );
        $stmt->execute([$userId, $userId, $userId]);
    }
    return (int)$stmt->fetchColumn();
}

function chat_search_messages(int $roomId, string $query, int $limit = 20): array
{
    $db = db();
    $stmt = $db->prepare(
        "SELECT m.*, u.name AS sender_name, u.role AS sender_role
         FROM project_chat_messages m JOIN users u ON m.sender_id = u.id
         WHERE m.room_id = ? AND m.message LIKE ?
         ORDER BY m.created_at DESC LIMIT ?"
    );
    $stmt->execute([$roomId, "%$query%", $limit]);
    return $stmt->fetchAll();
}

function chat_toggle_pin(int $messageId, int $userId): bool
{
    $db = db();
    $msg = $db->prepare("SELECT sender_id, is_pinned FROM project_chat_messages WHERE id = ?");
    $msg->execute([$messageId]);
    $row = $msg->fetch();
    if (!$row) return false;
    $newPin = $row['is_pinned'] ? 0 : 1;
    $db->prepare("UPDATE project_chat_messages SET is_pinned = ? WHERE id = ?")->execute([$newPin, $messageId]);
    return (bool)$newPin;
}

function chat_get_pinned(int $roomId): array
{
    $db = db();
    $stmt = $db->prepare(
        "SELECT m.*, u.name AS sender_name, u.role AS sender_role
         FROM project_chat_messages m JOIN users u ON m.sender_id = u.id
         WHERE m.room_id = ? AND m.is_pinned = 1
         ORDER BY m.created_at DESC"
    );
    $stmt->execute([$roomId]);
    return $stmt->fetchAll();
}

function chat_notify_members(int $roomId, int $senderId, string $projectName, string $preview): void
{
    $db = db();
    $sender = $db->prepare("SELECT name, role FROM users WHERE id = ?");
    $sender->execute([$senderId]);
    $s = $sender->fetch();
    $senderLabel = $s ? $s['name'] . ' (' . ucfirst(str_replace('_', ' ', $s['role'])) . ')' : 'Someone';

    $members = $db->prepare("
        SELECT m.user_id, u.fcm_token 
        FROM project_chat_members m 
        JOIN users u ON m.user_id = u.id 
        WHERE m.room_id = ? AND m.user_id != ?
    ");
    $members->execute([$roomId, $senderId]);

    $room = $db->prepare("SELECT project_id FROM project_chat_rooms WHERE id = ?");
    $room->execute([$roomId]);
    $projectId = (int)$room->fetchColumn();

    $ins = $db->prepare("INSERT INTO notifications (user_id, project_id, type, title, message, link) VALUES (?,?,?,?,?,?)");
    while ($m = $members->fetch()) {
        $title = "New message from $senderLabel";
        $body = "Project: $projectName — " . mb_strimwidth($preview, 0, 120, '...');
        $link = "/dashboard/project-chats?room=$roomId";
        
        $ins->execute([
            (int)$m['user_id'], $projectId, 'project_chat',
            $title,
            $body,
            $link
        ]);

        if (!empty($m['fcm_token'])) {
            send_push_notification($m['fcm_token'], $title, $body, [
                'link' => $link,
                'room_id' => $roomId,
                'project_id' => $projectId,
                'type' => 'project_chat',
                'title' => $title,
                'body' => $body
            ]);
        }
    }
}

function chat_can_access(int $roomId, int $userId): bool
{
    $db = db();
    $stmt = $db->prepare("SELECT COUNT(*) FROM project_chat_members WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$roomId, $userId]);
    return (int)$stmt->fetchColumn() > 0;
}

function chat_ensure_room(int $projectId): int
{
    $roomId = chat_create_room($projectId);
    chat_sync_members($roomId, $projectId);
    return $roomId;
}

function chat_allowed_types(): array
{
    return ['jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','mp4','webm','mp3','ogg','zip','rar','csv','txt','dwg','dxf'];
}

function chat_file_icon(string $ext): string
{
    $icons = [
        'pdf' => 'file-text', 'doc' => 'file-text', 'docx' => 'file-text',
        'xls' => 'file-spreadsheet', 'xlsx' => 'file-spreadsheet',
        'mp4' => 'video', 'webm' => 'video',
        'mp3' => 'music', 'ogg' => 'music',
        'zip' => 'archive', 'rar' => 'archive',
        'dwg' => 'blueprint', 'dxf' => 'blueprint',
    ];
    return $icons[$ext] ?? 'file';
}

function chat_get_room_members(int $roomId): array
{
    $db = db();
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.role 
        FROM project_chat_members pcm
        JOIN users u ON pcm.user_id = u.id
        WHERE pcm.room_id = ?
    ");
    $stmt->execute([$roomId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

