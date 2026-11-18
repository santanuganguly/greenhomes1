<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('site_engineer');
$db = db();

$projects = $db->prepare("SELECT id, name FROM projects WHERE engineer_id = ? AND status != 'completed' ORDER BY name");
$projects->execute([$user['id']]);
$projects = $projects->fetchAll();

$selectedProject = (int) ($_GET['project_id'] ?? ($_POST['project_id'] ?? 0));
$materials = [];

if ($selectedProject) {
    $stmt = $db->prepare("SELECT * FROM material_tracking WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([$selectedProject]);
    $materials = $stmt->fetchAll();
}

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_material') {
        $stmt = $db->prepare("INSERT INTO material_tracking (project_id, material_name, quantity_ordered, unit, status, notes) VALUES (?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([(int)$_POST['project_id'], trim($_POST['name']), (float)$_POST['quantity'], $_POST['unit'], trim($_POST['notes'] ?? '')]);
        $success = 'Material added.';
        $selectedProject = (int) $_POST['project_id'];
    }

    if ($action === 'update_delivery') {
        $newStatus = $_POST['status'];
        $dbStatus = match($newStatus) {
            'ordered' => 'partial',
            'in_transit' => 'partial',
            'delivered' => 'received',
            default => $newStatus,
        };
        $stmt = $db->prepare("UPDATE material_tracking SET status = ?, received_date = CASE WHEN ? = 'received' THEN CURDATE() ELSE received_date END WHERE id = ?");
        $stmt->execute([$dbStatus, $dbStatus, (int)$_POST['material_id']]);
        $success = 'Material updated.';
        $selectedProject = (int) $_POST['project_id'];
    }
}

portal_header(['title' => 'Materials', 'active' => 'materials', 'tab' => 'eng-materials']);
?>

<div class="hero">
    <h1>Material Tracking</h1>
    <p>Track materials for your projects</p>
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
        <div class="card-title">Add Material</div>
        <form method="post">
            <input type="hidden" name="action" value="add_material">
            <input type="hidden" name="project_id" value="<?= $selectedProject ?>">
            <div class="form-group"><label>Name *</label><input type="text" name="name" required class="form-control"></div>
            <div class="grid grid-2">
                <div class="form-group"><label>Quantity *</label><input type="number" name="quantity" step="0.01" required class="form-control"></div>
                <div class="form-group"><label>Unit</label>
                    <select name="unit" class="form-control">
                        <option value="pcs">Pieces</option>
                        <option value="kg">Kg</option>
                        <option value="bags">Bags</option>
                        <option value="cuft">Cu.Ft</option>
                        <option value="ltr">Liters</option>
                        <option value="tons">Tons</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Notes</label><input type="text" name="notes" class="form-control"></div>
            <button type="submit" class="btn">Add Material</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Materials</div>
        <?php if (empty($materials)): ?>
            <p class="empty">No materials tracked</p>
        <?php else: ?>
            <?php foreach ($materials as $m): ?>
                <div style="border-bottom:1px solid #e2e8f0;padding:.75rem 0">
                    <div style="display:flex;justify-content:space-between">
                        <strong><?= htmlspecialchars($m['material_name']) ?></strong>
                        <span class="pill pill-<?= $m['status'] === 'received' ? 'green' : ($m['status'] === 'partial' ? 'blue' : 'yellow') ?>"><?= ucfirst($m['status']) ?></span>
                    </div>
                    <small><?= $m['quantity_ordered'] ?> <?= $m['unit'] ?> <?= $m['notes'] ? '| ' . htmlspecialchars($m['notes']) : '' ?></small>
                    <?php if ($m['status'] !== 'received'): ?>
                        <form method="post" style="margin-top:.5rem">
                            <input type="hidden" name="action" value="update_delivery">
                            <input type="hidden" name="material_id" value="<?= $m['id'] ?>">
                            <input type="hidden" name="project_id" value="<?= $selectedProject ?>">
                            <select name="status" onchange="this.form.submit()" class="form-control" style="width:auto">
                                <option value="pending" <?= $m['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="ordered" <?= $m['status'] === 'partial' ? 'selected' : '' ?>>Ordered</option>
                                <option value="in_transit" <?= $m['status'] === 'partial' ? 'selected' : '' ?>>In Transit</option>
                                <option value="delivered" <?= $m['status'] === 'received' ? 'selected' : '' ?>>Received</option>
                            </select>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php portal_footer(); ?>
