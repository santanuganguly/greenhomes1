<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('admin', 'super_admin');
$db = db();

$projectId = (int) ($_GET['id'] ?? 0);
if (!$projectId) { header('Location: /admin/projects'); exit; }

$project = $db->prepare("SELECT p.*, u.name as client_name FROM projects p LEFT JOIN users u ON p.client_id = u.id WHERE p.id = ?");
$project->execute([$projectId]);
$project = $project->fetch();
if (!$project) { header('Location: /admin/projects'); exit; }

$tab = $_GET['tab'] ?? 'overview';
$success = $error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_project') {
        $stmt = $db->prepare("UPDATE projects SET name=?, location=?, status=?, progress_percentage=?, budget=?, start_date=?, expected_completion_date=?, manager_id=NULLIF(?,0), engineer_id=NULLIF(?,0) WHERE id=?");
        $stmt->execute([
            trim($_POST['name']), trim($_POST['location']), $_POST['status'],
            (int)$_POST['progress_percentage'], (float)$_POST['budget'],
            $_POST['start_date'] ?: null, $_POST['expected_completion_date'] ?: null,
            (int)($_POST['manager_id'] ?? 0), (int)($_POST['engineer_id'] ?? 0), $projectId
        ]);
        require_once dirname(__DIR__, 3) . '/src/chat.php';
        $stmt_room = $db->prepare("SELECT id FROM project_chat_rooms WHERE project_id = ?");
        $stmt_room->execute([$projectId]);
        if ($roomId = (int)$stmt_room->fetchColumn()) {
            chat_sync_members($roomId, $projectId);
        } else {
            chat_ensure_room($projectId);
        }
        audit_log('project_updated', 'project', $projectId);
        $success = 'Project updated.';
        // refresh project
        $project = $db->prepare("SELECT p.*, u.name as client_name FROM projects p LEFT JOIN users u ON p.client_id = u.id WHERE p.id = ?");
        $project->execute([$projectId]);
        $project = $project->fetch();
    }

    if ($action === 'add_milestone') {
        $stmt = $db->prepare("INSERT INTO milestones (project_id, title, description, due_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$projectId, trim($_POST['title']), trim($_POST['description']), $_POST['due_date'] ?: null]);
        $success = 'Milestone added.';
        $tab = 'milestones';
    }

    if ($action === 'update_milestone') {
        $stmt = $db->prepare("UPDATE milestones SET status=? WHERE id=? AND project_id=?");
        $stmt->execute([$_POST['status'], (int)$_POST['milestone_id'], $projectId]);
        $success = 'Milestone updated.';
        $tab = 'milestones';
    }

    if ($action === 'create_invoice') {
        $stmt = $db->prepare("INSERT INTO invoices (project_id, invoice_no, amount, description, due_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$projectId, trim($_POST['invoice_no']), (float)$_POST['amount'], trim($_POST['description']), $_POST['due_date']]);
        $success = 'Invoice created.';
        $tab = 'invoices';
    }

    if ($action === 'update_invoice') {
        $stmt = $db->prepare("UPDATE invoices SET status=?, paid_date=? WHERE id=? AND project_id=?");
        $stmt->execute([$_POST['status'], $_POST['paid_date'] ?: null, (int)$_POST['invoice_id'], $projectId]);
        $success = 'Invoice updated.';
        $tab = 'invoices';
    }

    if ($action === 'add_photo') {
        $stmt = $db->prepare("INSERT INTO photos (project_id, url, caption, uploaded_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$projectId, trim($_POST['url']), trim($_POST['caption']), $user['id']]);
        $success = 'Photo added.';
        $tab = 'photos';
    }

    if ($action === 'add_document') {
        $stmt = $db->prepare("INSERT INTO documents (project_id, title, url, type, uploaded_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$projectId, trim($_POST['title']), trim($_POST['url']), $_POST['type'], $user['id']]);
        $success = 'Document added.';
        $tab = 'documents';
    }

    if ($action === 'respond_suggestion') {
        $stmt = $db->prepare("UPDATE suggestions SET admin_response=?, responded_at=NOW() WHERE id=? AND project_id=?");
        $stmt->execute([trim($_POST['response']), (int)$_POST['suggestion_id'], $projectId]);
        $success = 'Response saved.';
        $tab = 'suggestions';
    }
}

$milestones = $db->prepare("SELECT * FROM milestones WHERE project_id = ? ORDER BY due_date ASC");
$milestones->execute([$projectId]);
$milestones = $milestones->fetchAll();

$invoices = $db->prepare("SELECT * FROM invoices WHERE project_id = ? ORDER BY created_at DESC");
$invoices->execute([$projectId]);
$invoices = $invoices->fetchAll();

$photos = $db->prepare("SELECT * FROM photos WHERE project_id = ? ORDER BY created_at DESC");
$photos->execute([$projectId]);
$photos = $photos->fetchAll();

$documents = $db->prepare("SELECT * FROM documents WHERE project_id = ? ORDER BY uploaded_at DESC");
$documents->execute([$projectId]);
$documents = $documents->fetchAll();

$suggestions = $db->prepare("SELECT s.*, u.name as user_name FROM suggestions s LEFT JOIN users u ON s.user_id = u.id WHERE s.project_id = ? ORDER BY s.created_at DESC");
$suggestions->execute([$projectId]);
$suggestions = $suggestions->fetchAll();

$managers = $db->query("SELECT id, name FROM users WHERE role = 'head_engineer' ORDER BY name")->fetchAll();
$engineers = $db->query("SELECT id, name FROM users WHERE role = 'site_engineer' ORDER BY name")->fetchAll();

portal_header(['title' => htmlspecialchars($project['name']), 'active' => 'projects', 'tab' => 'admin-projects']);
?>

<div class="hero">
    <h1><?= htmlspecialchars($project['name']) ?></h1>
    <p>Client: <?= htmlspecialchars($project['client_name'] ?? '—') ?></p>
</div>

<?php if ($success): ?><div class="alert alert-green"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-red"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
    <?php
    $tabs = ['overview'=>'Overview','milestones'=>'Milestones','invoices'=>'Invoices','photos'=>'Photos','documents'=>'Documents','suggestions'=>'Suggestions'];
    foreach ($tabs as $k => $l): ?>
        <a href="?id=<?= $projectId ?>&tab=<?= $k ?>" class="btn btn-sm <?= $tab === $k ? '' : 'btn-outline' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'overview'): ?>
<div class="card">
    <div class="card-title">Project Details</div>
    <form method="post">
        <input type="hidden" name="action" value="update_project">
        <div class="grid grid-2">
            <div class="form-group"><label>Name</label><input type="text" name="name" value="<?= htmlspecialchars($project['name']) ?>" class="form-control"></div>
            <div class="form-group"><label>Location</label><input type="text" name="location" value="<?= htmlspecialchars($project['location'] ?? '') ?>" class="form-control"></div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <?php foreach (['not_started'=>'Not Started','in_progress'=>'In Progress','on_hold'=>'On Hold','completed'=>'Completed'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $project['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Progress (%)</label><input type="number" name="progress_percentage" value="<?= $project['progress_percentage'] ?>" min="0" max="100" class="form-control"></div>
            <div class="form-group"><label>Budget (₹)</label><input type="number" name="budget" value="<?= $project['budget'] ?>" step="0.01" class="form-control"></div>
            <div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="<?= $project['start_date'] ?>" class="form-control"></div>
            <div class="form-group"><label>Expected Completion</label><input type="date" name="expected_completion_date" value="<?= $project['expected_completion_date'] ?>" class="form-control"></div>
            <div class="form-group">
                <label>Manager</label>
                <select name="manager_id" class="form-control">
                    <option value="0">None</option>
                    <?php foreach ($managers as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= (int)$project['manager_id'] === $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Engineer</label>
                <select name="engineer_id" class="form-control">
                    <option value="0">None</option>
                    <?php foreach ($engineers as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= (int)$project['engineer_id'] === $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn">Save Changes</button>
    </form>
</div>

<?php elseif ($tab === 'milestones'): ?>
<div class="grid grid-2">
    <div class="card">
        <div class="card-title">Add Milestone</div>
        <form method="post">
            <input type="hidden" name="action" value="add_milestone">
            <div class="form-group"><label>Title *</label><input type="text" name="title" required class="form-control"></div>
            <div class="form-group"><label>Description</label><textarea name="description" rows="3" class="form-control"></textarea></div>
            <div class="form-group"><label>Due Date</label><input type="date" name="due_date" class="form-control"></div>
            <button type="submit" class="btn">Add Milestone</button>
        </form>
    </div>
    <div class="card">
        <div class="card-title">Milestones</div>
        <?php if (empty($milestones)): ?>
            <p class="empty">No milestones yet</p>
        <?php else: ?>
            <?php foreach ($milestones as $m): ?>
                <div style="border-bottom:1px solid #e2e8f0;padding:.75rem 0;display:flex;justify-content:space-between;align-items:center">
                    <div>
                        <strong><?= htmlspecialchars($m['title']) ?></strong>
                        <?php if ($m['due_date']): ?><small> — Due: <?= $m['due_date'] ?></small><?php endif; ?>
                        <p style="margin:0;font-size:.85rem;color:#64748b"><?= htmlspecialchars($m['description'] ?? '') ?></p>
                    </div>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="action" value="update_milestone">
                        <input type="hidden" name="milestone_id" value="<?= $m['id'] ?>">
                        <select name="status" onchange="this.form.submit()" class="form-control" style="width:auto">
                            <?php foreach (['pending'=>'Pending','in_progress'=>'In Progress','completed'=>'Completed'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $m['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($tab === 'invoices'): ?>
<div class="grid grid-2">
    <div class="card">
        <div class="card-title">Create Invoice</div>
        <form method="post">
            <input type="hidden" name="action" value="create_invoice">
            <div class="form-group"><label>Invoice No *</label><input type="text" name="invoice_no" required class="form-control"></div>
            <div class="form-group"><label>Amount (₹) *</label><input type="number" name="amount" step="0.01" required class="form-control"></div>
            <div class="form-group"><label>Description</label><textarea name="description" rows="3" class="form-control"></textarea></div>
            <div class="form-group"><label>Due Date *</label><input type="date" name="due_date" required class="form-control"></div>
            <button type="submit" class="btn">Create Invoice</button>
        </form>
    </div>
    <div class="card">
        <div class="card-title">Invoices</div>
        <?php if (empty($invoices)): ?>
            <p class="empty">No invoices</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>#</th><th>Amount</th><th>Due</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($invoices as $i): ?>
                    <tr>
                        <td><?= htmlspecialchars($i['invoice_no']) ?></td>
                        <td><?= format_currency((float) $i['amount']) ?></td>
                        <td><?= $i['due_date'] ?></td>
                        <td><span class="pill pill-<?= $i['status'] === 'paid' ? 'green' : ($i['status'] === 'overdue' ? 'red' : 'yellow') ?>"><?= ucfirst($i['status']) ?></span></td>
                        <td>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="update_invoice">
                                <input type="hidden" name="invoice_id" value="<?= $i['id'] ?>">
                                <select name="status" onchange="this.form.submit()" class="form-control" style="width:auto">
                                    <option value="pending" <?= $i['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="paid" <?= $i['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                    <option value="overdue" <?= $i['status'] === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($tab === 'photos'): ?>
<div class="grid grid-2">
    <div class="card">
        <div class="card-title">Add Photo</div>
        <form method="post">
            <input type="hidden" name="action" value="add_photo">
            <div class="form-group"><label>Image URL *</label><input type="url" name="url" required class="form-control"></div>
            <div class="form-group"><label>Caption</label><input type="text" name="caption" class="form-control"></div>
            <button type="submit" class="btn">Add Photo</button>
        </form>
    </div>
    <div class="card">
        <div class="card-title">Photos</div>
        <?php if (empty($photos)): ?>
            <p class="empty">No photos yet</p>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($photos as $ph): ?>
                    <div style="border-radius:8px;overflow:hidden">
                        <img src="<?= htmlspecialchars($ph['url']) ?>" alt="<?= htmlspecialchars($ph['caption'] ?? '') ?>" style="width:100%;height:120px;object-fit:cover">
                        <?php if ($ph['caption']): ?><small><?= htmlspecialchars($ph['caption']) ?></small><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($tab === 'documents'): ?>
<div class="grid grid-2">
    <div class="card">
        <div class="card-title">Add Document</div>
        <form method="post">
            <input type="hidden" name="action" value="add_document">
            <div class="form-group"><label>Title *</label><input type="text" name="title" required class="form-control"></div>
            <div class="form-group"><label>URL *</label><input type="url" name="url" required class="form-control"></div>
            <div class="form-group">
                <label>Type</label>
                <select name="type" class="form-control">
                    <option value="contract">Contract</option>
                    <option value="invoice">Invoice</option>
                    <option value="receipt">Receipt</option>
                    <option value="report">Report</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <button type="submit" class="btn">Add Document</button>
        </form>
    </div>
    <div class="card">
        <div class="card-title">Documents</div>
        <?php if (empty($documents)): ?>
            <p class="empty">No documents</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Title</th><th>Type</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($documents as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['title']) ?></td>
                        <td><span class="pill pill-blue"><?= ucfirst($d['type']) ?></span></td>
                        <td><?= date('M j, Y', strtotime($d['created_at'])) ?></td>
                        <td><a href="<?= htmlspecialchars($d['url']) ?>" target="_blank" class="btn btn-sm">Download</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($tab === 'suggestions'): ?>
<div class="card">
    <div class="card-title">Suggestions & Feedback</div>
    <?php if (empty($suggestions)): ?>
        <p class="empty">No suggestions</p>
    <?php else: ?>
        <?php foreach ($suggestions as $s): ?>
            <div style="border-bottom:1px solid #e2e8f0;padding:1rem 0">
                <div style="display:flex;justify-content:space-between">
                    <strong><?= htmlspecialchars($s['user_name'] ?? 'User') ?></strong>
                    <small><?= date('M j, Y', strtotime($s['created_at'])) ?></small>
                </div>
                <p><?= nl2br(htmlspecialchars($s['message'] ?? '')) ?></p>
                <?php if ($s['admin_response']): ?>
                    <div class="alert alert-green"><strong>Response:</strong> <?= nl2br(htmlspecialchars($s['admin_response'])) ?></div>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="action" value="respond_suggestion">
                        <input type="hidden" name="suggestion_id" value="<?= $s['id'] ?>">
                        <div class="form-group"><textarea name="response" rows="2" class="form-control" placeholder="Write response..."></textarea></div>
                        <button type="submit" class="btn btn-sm">Respond</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php portal_footer(); ?>
