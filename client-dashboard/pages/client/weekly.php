<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Weekly Progress', 'tab' => 'weekly']);

$updates = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM weekly_updates WHERE project_id = ? AND status = 'published' ORDER BY week_number DESC");
    $stmt->execute([(int) $project['id']]);
    $updates = $stmt->fetchAll();
}
?>

<div class="p-content">
    <h1 class="p-title">Weekly Progress</h1>

    <?php if (empty($updates)): ?>
    <div class="empty">No updates yet. Check back soon!</div>
    <?php else: ?>
    <?php foreach ($updates as $u):
        $pid = (int) $project['id'];
        $uid = (int) $u['id'];
        $photos = $db->prepare("SELECT * FROM weekly_update_photos WHERE weekly_update_id = ? ORDER BY sort_order");
        $photos->execute([$uid]);
        $photoList = $photos->fetchAll();

        $vids = $db->prepare("SELECT * FROM weekly_update_videos WHERE weekly_update_id = ?");
        $vids->execute([$uid]);
        $videoList = $vids->fetchAll();
    ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:start; flex-wrap:wrap; gap:.5rem; margin-bottom:.75rem;">
            <div style="flex:1; min-width:0; display:flex; flex-direction:column; align-items:flex-start; gap:0.25rem;">
                <span class="pill pill-blue">Week <?= (int) $u['week_number'] ?></span>
                <strong class="card-title" style="margin:0;"><?= htmlspecialchars($u['title']) ?></strong>
            </div>
            <div style="font-size:.85rem; color:#888; flex-shrink:0;"><?= date('M j, Y', strtotime($u['created_at'])) ?></div>
        </div>

        <?php if ($u['work_completed']): ?>
        <div style="margin-bottom:.75rem;font-size:.9rem;"><?= nl2br(htmlspecialchars($u['work_completed'])) ?></div>
        <?php endif; ?>

        <?php if (!empty($photoList)): ?>
        <div class="grid grid-4" style="margin-bottom:.75rem;">
            <?php foreach ($photoList as $p): ?>
            <img src="<?= htmlspecialchars($p['url']) ?>" alt="<?= htmlspecialchars($p['caption'] ?? '') ?>" style="width:100%;border-radius:8px;object-fit:cover;aspect-ratio:4/3;">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($videoList)): ?>
        <div style="margin-bottom:.75rem;">
            <?php foreach ($videoList as $v): ?>
            <video controls style="max-width:100%;border-radius:8px;max-height:300px;">
                <source src="<?= htmlspecialchars($v['url']) ?>" type="video/mp4">
            </video>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($u['remarks']): ?>
        <div style="font-size:.85rem;color:#555;margin-bottom:.75rem;font-style:italic;"><?= nl2br(htmlspecialchars($u['remarks'])) ?></div>
        <?php endif; ?>

        <div class="progress"><div class="progress-fill" style="width:<?= (int) $u['progress_after'] ?>%"></div></div>
        <div style="text-align:right;font-size:.8rem;margin-top:.25rem;"><?= (int) $u['progress_after'] ?>% complete</div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
