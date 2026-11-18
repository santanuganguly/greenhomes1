<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
if (!file_exists($root . '/src/config.php')) { $root = dirname(__DIR__, 3); }
require_once $root . '/src/config.php';
gh_load_env($root);
require_once $root . '/src/auth.php';
require_once $root . '/src/csrf.php';
require_once $root . '/src/chat.php';

$user = current_user();
if (!$user) {
    http_response_code(401);
    exit('Unauthorized');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = db();

// --- FETCH MESSAGES ---
if ($action === 'fetch' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $roomId = (int)($_GET['room_id'] ?? 0);
    $after = (int)($_GET['after'] ?? 0);
    $before = (int)($_GET['before'] ?? 0);

    if (!$roomId || !chat_can_access($roomId, (int)$user['id'])) {
        http_response_code(403);
        exit('Forbidden');
    }

    if ($after > 0) {
        $stmt = $db->prepare(
            "SELECT m.*, u.name AS sender_name, u.role AS sender_role,
             (SELECT COUNT(*) FROM message_read_status rs WHERE rs.message_id = m.id) AS read_count,
             (SELECT COUNT(*) FROM message_delivery_status ds WHERE ds.message_id = m.id) AS delivered_count,
             ((SELECT COUNT(*) FROM project_chat_members cm WHERE cm.room_id = m.room_id) - 1) AS total_members
             FROM project_chat_messages m
             JOIN users u ON m.sender_id = u.id
             WHERE m.room_id = ? AND m.id > ?
             ORDER BY m.created_at ASC"
        );
        $stmt->execute([$roomId, $after]);
    } elseif ($before > 0) {
        $stmt = $db->prepare(
            "SELECT m.*, u.name AS sender_name, u.role AS sender_role,
             (SELECT COUNT(*) FROM message_read_status rs WHERE rs.message_id = m.id) AS read_count,
             (SELECT COUNT(*) FROM message_delivery_status ds WHERE ds.message_id = m.id) AS delivered_count,
             ((SELECT COUNT(*) FROM project_chat_members cm WHERE cm.room_id = m.room_id) - 1) AS total_members
             FROM project_chat_messages m
             JOIN users u ON m.sender_id = u.id
             WHERE m.room_id = ? AND m.id < ?
             ORDER BY m.created_at DESC
             LIMIT 50"
        );
        $stmt->execute([$roomId, $before]);
        $rows = $stmt->fetchAll();
        $rows = array_reverse($rows);
        header('Content-Type: application/json');
        echo json_encode(['messages' => $rows, 'has_more' => count($rows) === 50]);
        exit;
    } else {
        $stmt = $db->prepare(
            "SELECT m.*, u.name AS sender_name, u.role AS sender_role,
             (SELECT COUNT(*) FROM message_read_status rs WHERE rs.message_id = m.id) AS read_count,
             (SELECT COUNT(*) FROM message_delivery_status ds WHERE ds.message_id = m.id) AS delivered_count,
             ((SELECT COUNT(*) FROM project_chat_members cm WHERE cm.room_id = m.room_id) - 1) AS total_members
             FROM project_chat_messages m
             JOIN users u ON m.sender_id = u.id
             WHERE m.room_id = ?
             ORDER BY m.created_at DESC
             LIMIT 50"
        );
        $stmt->execute([$roomId]);
        $rows = $stmt->fetchAll();
        $rows = array_reverse($rows);
    }

    $messages = $rows ?? $stmt->fetchAll();

    // Mark these messages as delivered to the fetching user
    if (!empty($messages)) {
        $delivStmt = $db->prepare("INSERT IGNORE INTO message_delivery_status (message_id, user_id) VALUES (?, ?)");
        foreach ($messages as $m) {
            if ($m['sender_id'] != $user['id']) {
                $delivStmt->execute([$m['id'], $user['id']]);
            }
        }
    }

    // Attach files to each message
    foreach ($messages as &$msg) {
        $msg['files'] = chat_get_files((int)$msg['id']);
        if ($msg['reply_to_id']) {
            $r = $db->prepare("SELECT id, message, sender_id, sender_name FROM (SELECT m.id, m.message, m.sender_id, u.name AS sender_name FROM project_chat_messages m JOIN users u ON m.sender_id = u.id) sub WHERE id = ?");
            $r->execute([$msg['reply_to_id']]);
            $msg['reply_to'] = $r->fetch();
        }
    }
    unset($msg);

    header('Content-Type: application/json');
    echo json_encode(['messages' => $messages]);
    exit;
}

// --- SEND MESSAGE ---
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['_csrf'] ?? null);
    $roomId = (int)($_POST['room_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $replyToId = !empty($_POST['reply_to_id']) ? (int)$_POST['reply_to_id'] : null;
    $clientMsgId = !empty($_POST['client_msg_id']) ? $_POST['client_msg_id'] : null;

    if (!$roomId || !$message) {
        http_response_code(400);
        exit('Message required');
    }

    if (!chat_can_access($roomId, (int)$user['id'])) {
        http_response_code(403);
        exit('Forbidden');
    }

    $msgId = chat_send_message($roomId, (int)$user['id'], $message, $replyToId, $clientMsgId);

    $room = $db->prepare("SELECT r.project_id, p.name FROM project_chat_rooms r JOIN projects p ON r.project_id = p.id WHERE r.id = ?");
    $room->execute([$roomId]);
    $roomData = $room->fetch();
    if ($roomData) {
        chat_notify_members($roomId, (int)$user['id'], $roomData['name'], $message);
    }

    $stmt = $db->prepare(
        "SELECT m.*, u.name AS sender_name, u.role AS sender_role,
         (SELECT COUNT(*) FROM message_read_status rs WHERE rs.message_id = m.id) AS read_count,
         (SELECT COUNT(*) FROM message_delivery_status ds WHERE ds.message_id = m.id) AS delivered_count,
         ((SELECT COUNT(*) FROM project_chat_members cm WHERE cm.room_id = m.room_id) - 1) AS total_members
         FROM project_chat_messages m JOIN users u ON m.sender_id = u.id WHERE m.id = ?"
    );
    $stmt->execute([$msgId]);
    $msgData = $stmt->fetch();
    $msgData['files'] = [];

    header('Content-Type: application/json');
    echo json_encode(['message' => $msgData]);
    exit;
}

// --- UPLOAD FILE ---
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['_csrf'] ?? null);
    $roomId = (int)($_POST['room_id'] ?? 0);
    $replyToId = !empty($_POST['reply_to_id']) ? (int)$_POST['reply_to_id'] : null;
    $clientMsgId = !empty($_POST['client_msg_id']) ? $_POST['client_msg_id'] : null;

    if (!$roomId || !chat_can_access($roomId, (int)$user['id'])) {
        http_response_code(403);
        exit('Forbidden');
    }

    if (empty($_FILES['file'])) {
        http_response_code(400);
        exit('No file uploaded');
    }

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = chat_allowed_types();

    if (!in_array($ext, $allowed, true)) {
        http_response_code(400);
        exit('File type not allowed');
    }

    $maxSize = 50 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        exit('File too large (max 50MB)');
    }

    $fileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $uploadDir = __DIR__ . '/../uploads/project_chat/';
    $filePath = 'uploads/project_chat/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
        http_response_code(500);
        exit('Upload failed');
    }

    $caption = trim($_POST['caption'] ?? '');
    $mimeType = $file['type'];

    $msgId = chat_send_file_message(
        $roomId, (int)$user['id'], $caption, $filePath, $file['name'], $mimeType, $file['size'], $replyToId, $clientMsgId
    );

    $room = $db->prepare("SELECT r.project_id, p.name FROM project_chat_rooms r JOIN projects p ON r.project_id = p.id WHERE r.id = ?");
    $room->execute([$roomId]);
    $roomData = $room->fetch();
    if ($roomData) {
        chat_notify_members($roomId, (int)$user['id'], $roomData['name'], $caption ?: 'Sent a file');
    }

    $stmt = $db->prepare(
        "SELECT m.*, u.name AS sender_name, u.role AS sender_role,
         (SELECT COUNT(*) FROM message_read_status rs WHERE rs.message_id = m.id) AS read_count,
         (SELECT COUNT(*) FROM message_delivery_status ds WHERE ds.message_id = m.id) AS delivered_count,
         ((SELECT COUNT(*) FROM project_chat_members cm WHERE cm.room_id = m.room_id) - 1) AS total_members
         FROM project_chat_messages m JOIN users u ON m.sender_id = u.id WHERE m.id = ?"
    );
    $stmt->execute([$msgId]);
    $msgData = $stmt->fetch();
    $msgData['files'] = chat_get_files($msgId);

    header('Content-Type: application/json');
    echo json_encode(['message' => $msgData]);
    exit;
}

// --- MARK READ ---
if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $messageId = (int)($_POST['message_id'] ?? 0);

    if ($messageId) {
        chat_mark_read($messageId, (int)$user['id']);
    } elseif ($roomId) {
        chat_mark_room_read($roomId, (int)$user['id']);
    }

    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// --- TYPING ---
if ($action === 'typing' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    if ($roomId && chat_can_access($roomId, (int)$user['id'])) {
        $stmt = $db->prepare("REPLACE INTO project_chat_typing (room_id, user_id, user_name, user_role, typed_at) VALUES (?,?,?,?,NOW())");
        $stmt->execute([$roomId, (int)$user['id'], $user['name'], $user['role']]);
        // Clean old typing statuses
        $db->prepare("DELETE FROM project_chat_typing WHERE typed_at < DATE_SUB(NOW(), INTERVAL 5 SECOND)")->execute();
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// --- GET TYPING ---
if ($action === 'get_typing' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $roomId = (int)($_GET['room_id'] ?? 0);
    if (!$roomId) {
        exit('[]');
    }
    $stmt = $db->prepare(
        "SELECT user_id, user_name, user_role FROM project_chat_typing WHERE room_id = ? AND user_id != ? AND typed_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)"
    );
    $stmt->execute([$roomId, (int)$user['id']]);
    $typing = $stmt->fetchAll();
    header('Content-Type: application/json');
    echo json_encode($typing);
    exit;
}

// --- POLL (get rooms with unread counts) ---
if ($action === 'poll' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $rooms = chat_get_user_rooms((int)$user['id']);
    $roomId = (int)($_GET['room_id'] ?? 0);
    $result = [];

    foreach ($rooms as $room) {
        $unread = chat_get_unread_count((int)$user['id'], (int)$room['room_id']);
        $result[] = [
            'room_id' => (int)$room['room_id'],
            'unread' => $unread,
            'last_msg' => $room['last_msg'] ?? '',
            'last_msg_at' => $room['last_msg_at'] ?? '',
        ];
    }

    $newMsgs = [];
    $lastId = (int)($_GET['last_message_id'] ?? 0);
    if ($roomId > 0 && $lastId > 0) {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM project_chat_messages WHERE room_id = ? AND id > ?"
        );
        $stmt->execute([$roomId, $lastId]);
        $newMsgs['count'] = (int)$stmt->fetchColumn();
    }

    header('Content-Type: application/json');
    echo json_encode(['rooms' => $result, 'new_messages' => $newMsgs ?? []]);
    exit;
}

http_response_code(400);
exit('Invalid action');
