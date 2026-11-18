<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('head_engineer', 'admin', 'super_admin');
$db = db();

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $updateId = (int) ($_POST['update_id'] ?? 0);

    if ($action === 'approve' && $updateId) {
        $stmt = $db->prepare("UPDATE weekly_updates SET status = 'approved', published_at = NOW(), reviewed_by = ? WHERE id = ?");
        $stmt->execute([$user['id'], $updateId]);
        audit_log('weekly_approved', 'weekly_update', $updateId);
        $success = 'Update approved and published.';
    }

    if ($action === 'request_changes' && $updateId) {
        $stmt = $db->prepare("UPDATE weekly_updates SET status = 'changes_requested', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$user['id'], $updateId]);
        $success = 'Changes requested.';
    }
}

if (in_array($user['role'], ['admin', 'super_admin'], true)) {
    $pending = $db->query(
        "SELECT w.*, p.name as project_name, u.name as submitted_by
         FROM weekly_updates w
         JOIN projects p ON w.project_id = p.id
         LEFT JOIN users u ON w.created_by = u.id
         WHERE w.status = 'pending_approval'
         ORDER BY w.created_at DESC"
    )->fetchAll();
} else {
    $pending = $db->prepare(
        "SELECT w.*, p.name as project_name, u.name as submitted_by
         FROM weekly_updates w
         JOIN projects p ON w.project_id = p.id
         LEFT JOIN users u ON w.created_by = u.id
         WHERE (p.manager_id = ? OR p.engineer_id = ?) AND w.status = 'pending_approval'
         ORDER BY w.created_at DESC"
    );
    $pending->execute([$user['id'], $user['id']]);
    $pending = $pending->fetchAll();
}

portal_header(['title' => 'Approvals', 'active' => 'approvals', 'tab' => 'pm-approvals']);
?>

<div class="hero">
    <h1>Pending Approvals</h1>
    <p>Weekly updates awaiting your review</p>
</div>

<?php if ($success): ?><div class="alert alert-green"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if (empty($pending)): ?>
    <div class="card"><p class="empty">No pending approvals</p></div>
<?php else: ?>
    <?php foreach ($pending as $u): ?>
        <div class="card" style="margin-bottom:1rem; border-left:4px solid #eab308;">
            <div style="display:flex;justify-content:space-between;align-items:start; flex-wrap:wrap; gap:0.5rem;">
                <div>
                    <h3 style="margin:0; font-size:1.05rem; font-weight:700;"><?= htmlspecialchars($u['title']) ?></h3>
                    <p style="margin:.25rem 0;color:#64748b; font-size:0.82rem;">
                        Project: <strong><?= htmlspecialchars($u['project_name']) ?></strong> — Week <?= $u['week_number'] ?>
                        | Submitted by <strong><?= htmlspecialchars($u['submitted_by'] ?? 'Engineer') ?></strong>
                        | <?= date('M j, Y', strtotime($u['created_at'])) ?>
                    </p>
                </div>
                <span class="pill pill-yellow">Pending Review</span>
            </div>

            <?php if ($u['work_completed']): ?>
                <div style="margin-top:.75rem; font-size:0.9rem; color:#334155; line-height:1.5;">
                    <strong>Work Completed:</strong><br>
                    <?= nl2br(htmlspecialchars($u['work_completed'])) ?>
                </div>
            <?php endif; ?>

            <?php if ($u['remarks']): ?>
                <div style="margin-top:.5rem; font-size:0.85rem; color:#64748b; font-style:italic;">
                    Remarks: <?= htmlspecialchars($u['remarks']) ?>
                </div>
            <?php endif; ?>

            <!-- PHOTOS DISPLAY -->
            <?php
            $photos = !empty($u['photos']) ? json_decode($u['photos'], true) : [];
            if (!empty($photos) && is_array($photos)):
            ?>
                <div style="margin-top:0.75rem;">
                    <strong style="font-size:0.85rem; color:#475569;">Site Progress Photos:</strong>
                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.35rem;">
                        <?php foreach ($photos as $imgUrl): ?>
                            <a href="<?= htmlspecialchars($imgUrl) ?>" target="_blank" style="border-radius:8px; overflow:hidden; border:1px solid #cbd5e1;">
                                <img src="<?= htmlspecialchars($imgUrl) ?>" style="width:85px; height:85px; object-fit:cover; display:block;">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div style="margin-top:1rem; display:flex; gap:.5rem;">
                <form method="post" style="display:inline">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="update_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="background:#166534; color:#fff;">Approve & Publish</button>
                </form>
                <form method="post" style="display:inline">
                    <input type="hidden" name="action" value="request_changes">
                    <input type="hidden" name="update_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Request Changes</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php portal_footer(); ?>
