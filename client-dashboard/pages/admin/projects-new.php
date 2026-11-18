<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('admin', 'super_admin');
$db = db();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_project') {
    $clientId = (int) ($_POST['client_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $budget = (float) ($_POST['budget'] ?? 0);
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['expected_completion_date'] ?? null;
    $managerId = (int) ($_POST['manager_id'] ?? 0) ?: null;
    $engineerId = (int) ($_POST['engineer_id'] ?? 0) ?: null;
    $description = trim($_POST['description'] ?? '');

    if ($name === '' || $clientId === 0) {
        $error = 'Project name and client are required.';
    } else {
        $stmt = $db->prepare(
            "INSERT INTO projects (client_id, manager_id, engineer_id, name, location, budget, start_date, expected_completion_date, description, status, progress_percentage)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'not_started', 0)"
        );
        $stmt->execute([$clientId, $managerId, $engineerId, $name, $location, $budget, $startDate, $endDate, $description]);
        $pid = (int) $db->lastInsertId();
        
        // Auto-create project chat room
        if (file_exists(dirname(__DIR__, 3) . '/src/chat.php')) { require_once dirname(__DIR__, 3) . '/src/chat.php'; } else { require_once dirname(__DIR__, 4) . '/src/chat.php'; }
        chat_create_room($pid);
        
        audit_log('project_created', 'project', $pid, $name);
        header('Location: /admin/projects?id=' . $pid);
        exit;
    }
}

$clients = $db->query("SELECT id, name, email FROM users WHERE role = 'client' ORDER BY name")->fetchAll();
$managers = $db->query("SELECT id, name FROM users WHERE role = 'head_engineer' ORDER BY name")->fetchAll();
$engineers = $db->query("SELECT id, name FROM users WHERE role = 'site_engineer' ORDER BY name")->fetchAll();

portal_header(['title' => 'New Project', 'active' => 'projects', 'tab' => 'admin-projects']);
?>

<div class="hero">
    <h1>Create New Project</h1>
</div>

<?php if ($error): ?><div class="alert alert-red"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post">
        <input type="hidden" name="action" value="create_project">
        <div class="grid grid-2">
            <div class="form-group">
                <label>Project Name *</label>
                <input type="text" name="name" required class="form-control">
            </div>
            <div class="form-group">
                <label>Client *</label>
                <select name="client_id" required class="form-control">
                    <option value="">Select client...</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" class="form-control">
            </div>
            <div class="form-group">
                <label>Budget (₹)</label>
                <input type="number" name="budget" step="0.01" class="form-control">
            </div>
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control">
            </div>
            <div class="form-group">
                <label>Expected Completion</label>
                <input type="date" name="expected_completion_date" class="form-control">
            </div>
            <div class="form-group">
                <label>Project Manager</label>
                <select name="manager_id" class="form-control">
                    <option value="0">None</option>
                    <?php foreach ($managers as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Site Engineer</label>
                <select name="engineer_id" class="form-control">
                    <option value="0">None</option>
                    <?php foreach ($engineers as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="4" class="form-control"></textarea>
        </div>
        <button type="submit" class="btn">Create Project</button>
        <a href="/admin/projects" class="btn btn-outline" style="margin-left:.5rem">Cancel</a>
    </form>
</div>

<?php portal_footer(); ?>
