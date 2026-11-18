<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Gallery', 'tab' => 'gallery']);

$photos = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM photos WHERE project_id = ? AND is_video = 0 ORDER BY uploaded_at DESC");
    $stmt->execute([(int) $project['id']]);
    $photos = $stmt->fetchAll();
}
?>

<div class="p-content">
    <h1 class="p-title">Photo Gallery</h1>

    <?php if (empty($photos)): ?>
    <div class="empty">No photos uploaded yet.</div>
    <?php else: ?>
    <div class="grid grid-4">
        <?php foreach ($photos as $p): ?>
        <div style="position:relative;border-radius:8px;overflow:hidden;">
            <img src="<?= htmlspecialchars($p['url']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" style="width:100%;aspect-ratio:4/3;object-fit:cover;">
            <?php if ($p['category']): ?>
            <span class="pill pill-blue" style="position:absolute;top:.5rem;left:.5rem;font-size:.7rem;"><?= htmlspecialchars($p['category']) ?></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
