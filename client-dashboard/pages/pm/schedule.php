<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('head_engineer');
$db = db();

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm') {
    $stmt = $db->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ? AND project_id IN (SELECT id FROM projects WHERE manager_id = ?)");
    $stmt->execute([(int)$_POST['appointment_id'], $user['id']]);
    $success = 'Appointment confirmed.';
}

$appointments = $db->prepare(
    "SELECT a.*, p.name as project_name, c.name as client_name
     FROM appointments a
     JOIN projects p ON a.project_id = p.id
     LEFT JOIN users u ON p.client_id = u.id
     LEFT JOIN users c ON p.client_id = c.id
     WHERE p.manager_id = ?
     ORDER BY a.date ASC, a.time ASC"
);
$appointments->execute([$user['id']]);
$appointments = $appointments->fetchAll();

portal_header(['title' => 'Schedule', 'active' => 'schedule', 'tab' => 'pm-schedule']);
?>

<div class="hero">
    <h1>Schedule</h1>
    <p>Upcoming appointments</p>
</div>

<?php if ($success): ?><div class="alert alert-green"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card">
    <?php if (empty($appointments)): ?>
        <p class="empty">No appointments scheduled</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Date</th><th>Time</th><th>Project</th><th>Client</th><th>Purpose</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($appointments as $a): ?>
                <tr>
                    <td><?= date('M j, Y', strtotime($a['date'])) ?></td>
                    <td><?= $a['time'] ?></td>
                    <td><?= htmlspecialchars($a['project_name']) ?></td>
                    <td><?= htmlspecialchars($a['client_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($a['purpose'] ?? '—') ?></td>
                    <td><span class="pill pill-<?= $a['status'] === 'confirmed' ? 'green' : ($a['status'] === 'cancelled' ? 'red' : 'yellow') ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td>
                        <?php if ($a['status'] !== 'confirmed'): ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="confirm">
                                <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                                <button type="submit" class="btn btn-sm">Confirm</button>
                            </form>
                        <?php else: ?>
                            <small>Confirmed</small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
