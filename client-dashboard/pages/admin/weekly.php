<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('admin', 'super_admin');
$db = db();

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $updateId = (int)($_POST['update_id'] ?? 0);

    if ($action === 'approve' && $updateId) {
        $stmt = $db->prepare("UPDATE weekly_updates SET status = 'approved', published_at = NOW(), reviewed_by = ? WHERE id = ?");
        $stmt->execute([$user['id'], $updateId]);
        audit_log('weekly_approved', 'weekly_update', $updateId);
        $success = 'Weekly update approved and published.';
    }
}

$updates = $db->query(
    "SELECT w.*, p.name as project_name, u.name as created_by_name
     FROM weekly_updates w
     JOIN projects p ON w.project_id = p.id
     LEFT JOIN users u ON w.created_by = u.id
     ORDER BY w.created_at DESC"
)->fetchAll();

portal_header(['title' => 'Weekly Updates', 'active' => 'weekly', 'tab' => 'admin-weekly']);
?>

<div class="hero">
    <h1>Weekly Updates</h1>
    <p>All progress reports & site photos across projects</p>
</div>

<?php if ($success): ?><div class="alert alert-green"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if (empty($updates)): ?>
    <div class="card"><p class="empty">No weekly updates found</p></div>
<?php else: ?>
    <?php foreach ($updates as $u): ?>
        <div class="card" style="margin-bottom:1.25rem; border-left:4px solid <?= $u['status'] === 'approved' || $u['status'] === 'published' ? '#166534' : '#eab308' ?>;">
            <div style="display:flex; justify-content:space-between; align-items:start; flex-wrap:wrap; gap:0.5rem;">
                <div style="flex:1; min-width:0;">
                    <h3 class="card-title" style="margin:0;">
                        Week <?= $u['week_number'] ?>: <?= htmlspecialchars($u['title']) ?>
                    </h3>
                    <p style="margin:.25rem 0; font-size:0.85rem; color:#64748b; line-height:1.4;">
                        Project: <strong><?= htmlspecialchars($u['project_name']) ?></strong> | 
                        Submitted by: <strong><?= htmlspecialchars($u['created_by_name'] ?? 'Engineer') ?></strong> | 
                        Date: <?= date('M j, Y', strtotime($u['created_at'])) ?> | 
                        Progress After: <strong><?= (int)$u['progress_after'] ?>%</strong>
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem; flex-shrink:0;">
                    <span class="pill pill-<?= $u['status'] === 'approved' || $u['status'] === 'published' ? 'green' : ($u['status'] === 'pending_approval' ? 'yellow' : 'red') ?>">
                        <?= ucfirst(str_replace('_', ' ', $u['status'])) ?>
                    </span>
                    <?php if ($u['status'] === 'pending_approval'): ?>
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="update_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm" style="background:#166534; color:#fff;">Approve & Publish</button>
                        </form>
                    <?php endif; ?>
                </div>
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
                <div style="margin-top:1rem;">
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
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php portal_footer(); ?>
