<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('site_engineer');
$db = db();

$projects = $db->prepare("SELECT id, name FROM projects WHERE engineer_id = ? ORDER BY name");
$projects->execute([$user['id']]);
$projects = $projects->fetchAll();

$selectedProject = (int) ($_GET['project_id'] ?? ($_POST['project_id'] ?? 0));
$issues = [];

if ($selectedProject) {
    $stmt = $db->prepare("SELECT * FROM project_issues WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([$selectedProject]);
    $issues = $stmt->fetchAll();
}

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'report_issue') {
        $stmt = $db->prepare("INSERT INTO project_issues (project_id, reported_by, title, severity, description, impact, expected_delay, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'open')");
        $stmt->execute([
            (int)$_POST['project_id'], $user['id'],
            trim($_POST['title']), $_POST['severity'],
            trim($_POST['description']), trim($_POST['impact']),
            trim($_POST['expected_delay'] ?? '')
        ]);
        audit_log('issue_reported', 'issue', (int) $db->lastInsertId());
        $success = 'Issue reported.';
        $selectedProject = (int) $_POST['project_id'];
    }

    if ($action === 'update_status') {
        $stmt = $db->prepare("UPDATE project_issues SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], (int)$_POST['issue_id']]);
        $success = 'Issue status updated.';
        $selectedProject = (int) $_POST['project_id'];
    }
}

portal_header(['title' => 'Issues', 'active' => 'issues', 'tab' => 'eng-issues']);
?>

<div class="hero">
    <h1>Issue Tracker</h1>
    <p>Report and track project issues</p>
</div>

<?php if ($success): ?><div class="alert alert-green"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:1.5rem">
    <?php if (empty($projects)): ?>
        <p class="empty">No projects assigned</p>
    <?php else: ?>
        <form method="get">
            <div class="form-group">
                <label>Select Project</label>
                <select name="project_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Choose project...</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $selectedProject === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php if ($selectedProject): ?>
<div class="grid grid-2">
    <div class="card">
        <div class="card-title">Report Issue</div>
        <form method="post">
            <input type="hidden" name="action" value="report_issue">
            <input type="hidden" name="project_id" value="<?= $selectedProject ?>">
            <div class="form-group"><label>Title *</label><input type="text" name="title" required class="form-control"></div>
            <div class="form-group">
                <label>Severity *</label>
                <select name="severity" class="form-control">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <div class="form-group"><label>Description *</label><textarea name="description" rows="3" required class="form-control"></textarea></div>
            <div class="form-group"><label>Impact</label><textarea name="impact" rows="2" class="form-control" placeholder="How does this impact the project?"></textarea></div>
            <div class="form-group"><label>Expected Delay</label><input type="text" name="expected_delay" class="form-control" placeholder="e.g. 2 days"></div>
            <button type="submit" class="btn btn-danger">Report Issue</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Issues</div>
        <?php if (empty($issues)): ?>
            <p class="empty">No issues reported</p>
        <?php else: ?>
            <?php foreach ($issues as $i): ?>
                <div style="border-bottom:1px solid #e2e8f0;padding:.75rem 0">
                    <div style="display:flex;justify-content:space-between">
                        <strong><?= htmlspecialchars($i['title']) ?></strong>
                        <span class="pill pill-<?= $i['severity'] === 'critical' ? 'red' : ($i['severity'] === 'high' ? 'red' : ($i['severity'] === 'medium' ? 'yellow' : 'gray')) ?>"><?= ucfirst($i['severity']) ?></span>
                    </div>
                    <p style="margin:.25rem 0;font-size:.85rem"><?= htmlspecialchars($i['description']) ?></p>
                    <?php if ($i['expected_delay']): ?><small>Expected delay: <?= htmlspecialchars($i['expected_delay']) ?></small><?php endif; ?>
                    <form method="post" style="margin-top:.5rem">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="issue_id" value="<?= $i['id'] ?>">
                        <input type="hidden" name="project_id" value="<?= $selectedProject ?>">
                        <select name="status" onchange="this.form.submit()" class="form-control" style="width:auto">
                            <option value="open" <?= $i['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="in_progress" <?= $i['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="resolved" <?= $i['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="closed" <?= $i['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php portal_footer(); ?>
