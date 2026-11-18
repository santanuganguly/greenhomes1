<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__, 2);
if (!file_exists($root . '/.env')) {
    $root = dirname(__DIR__, 3);
}
if (!file_exists($root . '/.env')) {
    $root = '/home/u495244335/domains/thegreen.homes/public_html';
}

require_once $root . '/src/config.php';
gh_load_env($root);
require_once $root . '/src/auth.php';

$db = db();
echo "<h3>Database FCM & Notification Fix Script</h3>";

try {
    $db->exec("ALTER TABLE users MODIFY COLUMN fcm_token TEXT DEFAULT NULL");
    echo "<p>✅ Updated users.fcm_token column to TEXT.</p>";
} catch (Exception $e) {
    try {
        $db->exec("ALTER TABLE users ADD COLUMN fcm_token TEXT DEFAULT NULL");
        echo "<p>✅ Added users.fcm_token TEXT column.</p>";
    } catch(Exception $e2) {
        echo "<p>Column setup note: " . htmlspecialchars($e2->getMessage()) . "</p>";
    }
}

try {
    $count = $db->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    echo "<p>✅ Reset $count unread notification badges so green dot is cleared.</p>";
} catch (Exception $e) {
    echo "<p>Notification update note: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    $db->exec("ALTER TABLE weekly_updates ADD COLUMN photos TEXT DEFAULT NULL");
    echo "<p>✅ Added weekly_updates.photos column.</p>";
} catch (Exception $e) {
    echo "<p>weekly_updates.photos note: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    $db->exec("ALTER TABLE engineer_notes ADD COLUMN photos TEXT DEFAULT NULL");
    echo "<p>✅ Added engineer_notes.photos column.</p>";
} catch (Exception $e) {
    echo "<p>engineer_notes.photos note: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><strong>Migration complete!</strong></p>";
