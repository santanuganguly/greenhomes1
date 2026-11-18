<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('admin', 'super_admin');
$db = db();

mark_all_notifications_read((int)$user['id']);

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    $recipient = $_POST['recipient'] ?? '';
    $type = $_POST['type'] ?? 'info';
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $projectId = (int) ($_POST['project_id'] ?? 0) ?: null;

    if ($title === '' || $message === '') {
        $error = 'Title and message are required.';
    } else {
        if ($recipient === 'all_clients') {
            $stmt = $db->query("SELECT id FROM users WHERE role = 'client'");
            $clients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($clients as $cid) {
                notify_user((int) $cid, $type, $title, $message, null, $projectId);
            }
        } elseif ($recipient === 'all_users') {
            $stmt = $db->query("SELECT id FROM users");
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($users as $uid) {
                notify_user((int) $uid, $type, $title, $message, null, $projectId);
            }
        } elseif ($projectId) {
            notify_project_users($projectId, $type, $title, $message);
        }
        audit_log('notification_sent', 'notification', null, "$title to $recipient");
        $success = 'Notification sent.';
    }
}

$recent = $db->query("SELECT n.*, u.name as user_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC LIMIT 20")->fetchAll();
$projects = $db->query("SELECT id, name FROM projects ORDER BY name")->fetchAll();

portal_header(['title' => 'Notifications', 'active' => 'notifications', 'tab' => 'admin-notifications']);
?>

<div class="hero">
    <h1>Notifications</h1>
    <p>Send notifications to users</p>
</div>

<?php if ($success): ?><div class="alert alert-green"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-red"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="grid grid-2">
    <div class="card">
        <div class="card-title">Send Notification</div>
        <form method="post">
            <input type="hidden" name="action" value="send">
            <div class="form-group">
                <label>Recipient</label>
                <select name="recipient" class="form-control" id="recipient-select">
                    <option value="all_clients">All Clients</option>
                    <option value="all_users">All Users</option>
                    <option value="project">Project-Specific</option>
                </select>
            </div>
            <div class="form-group" id="project-group" style="display:none">
                <label>Project</label>
                <select name="project_id" class="form-control">
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type" class="form-control">
                    <option value="info">Info</option>
                    <option value="update">Update</option>
                    <option value="alert">Alert</option>
                </select>
            </div>
            <div class="form-group"><label>Title *</label><input type="text" name="title" required class="form-control"></div>
            <div class="form-group"><label>Message *</label><textarea name="message" rows="4" required class="form-control"></textarea></div>
            <button type="submit" class="btn">Send Notification</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Recent Notifications</div>
        <?php if (empty($recent)): ?>
            <p class="empty">No notifications sent yet</p>
        <?php else: ?>
            <?php foreach ($recent as $n): ?>
                <div style="border-bottom:1px solid #e2e8f0;padding:.5rem 0">
                    <div style="display:flex;justify-content:space-between">
                        <strong><?= htmlspecialchars($n['title']) ?></strong>
                        <small><?= time_ago($n['created_at']) ?></small>
                    </div>
                    <small>To: <?= htmlspecialchars($n['user_name'] ?? '—') ?> | <?= ucfirst($n['type']) ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('recipient-select').addEventListener('change', function() {
    document.getElementById('project-group').style.display = this.value === 'project' ? 'block' : 'none';
});
</script>

<?php portal_footer(); ?>
