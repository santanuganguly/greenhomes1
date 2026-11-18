<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Book Site Visit', 'tab' => 'appointments']);

$appointments = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM appointments WHERE project_id = ? ORDER BY appointment_date DESC");
    $stmt->execute([(int) $project['id']]);
    $appointments = $stmt->fetchAll();
}
?>

<div class="p-content">
    <h1 class="p-title">Book a Site Visit</h1>

    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-title">Schedule New Visit</div>
        <form method="POST" action="/api/portal">
            <input type="hidden" name="action" value="appointment_book">
            <input type="hidden" name="project_id" value="<?= $project ? $project['id'] : '' ?>">
            <div class="grid grid-2">
                <div>
                    <label style="font-size:.85rem;font-weight:600;">Date *</label>
                    <input type="date" name="appointment_date" required class="input" style="width:100%;margin-top:.25rem;">
                </div>
                <div>
                    <label style="font-size:.85rem;font-weight:600;">Time</label>
                    <input type="time" name="appointment_time" class="input" style="width:100%;margin-top:.25rem;">
                </div>
            </div>
            <div style="margin-top:.75rem;">
                <label style="font-size:.85rem;font-weight:600;">Purpose</label>
                <textarea name="purpose" rows="3" class="input" placeholder="Reason for visit…" style="width:100%;margin-top:.25rem;resize:vertical;"></textarea>
            </div>
            <button type="submit" class="btn" style="margin-top:1rem;">Book Visit</button>
        </form>
    </div>

    <?php if (!empty($appointments)): ?>
    <div class="card" style="overflow-x:auto;">
        <div class="card-title">Your Appointments</div>
        <table class="table">
            <thead>
                <tr><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $a): ?>
                <tr>
                    <td><?= date('M j, Y', strtotime($a['appointment_date'])) ?></td>
                    <td><?= $a['appointment_time'] ? date('g:i A', strtotime($a['appointment_time'])) : '—' ?></td>
                    <td><?= htmlspecialchars(mb_substr($a['purpose'] ?? '', 0, 60)) ?></td>
                    <td>
                        <span class="pill <?= $a['status'] === 'confirmed' ? 'pill-green' : ($a['status'] === 'cancelled' ? 'pill-red' : ($a['status'] === 'completed' ? 'pill-blue' : 'pill-yellow')) ?>">
                            <?= ucfirst(htmlspecialchars($a['status'])) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
