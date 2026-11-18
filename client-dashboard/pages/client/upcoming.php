<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Upcoming Work', 'tab' => 'upcoming']);

$works = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM upcoming_works WHERE project_id = ? ORDER BY week_commencing ASC, sort_order ASC");
    $stmt->execute([(int) $project['id']]);
    $works = $stmt->fetchAll();
}
?>

<div class="p-content">
    <h1 class="p-title">Upcoming Work</h1>

    <?php if (empty($works)): ?>
    <div class="empty">No upcoming work scheduled.</div>
    <?php else: ?>
    <div class="grid grid-2">
        <?php foreach ($works as $w): ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:.5rem;">
                <div style="font-weight:600;"><?= htmlspecialchars($w['title']) ?></div>
                <span class="pill <?= $w['status'] === 'completed' ? 'pill-green' : ($w['status'] === 'in_progress' ? 'pill-yellow' : 'pill-gray') ?>">
                    <?= ucfirst(htmlspecialchars(str_replace('_', ' ', $w['status']))) ?>
                </span>
            </div>
            <?php if ($w['description']): ?>
            <div style="font-size:.9rem;color:#555;margin-bottom:.5rem;"><?= nl2br(htmlspecialchars($w['description'])) ?></div>
            <?php endif; ?>
            <?php if ($w['week_commencing']): ?>
            <div style="font-size:.8rem;color:#888;">Week of <?= date('M j, Y', strtotime($w['week_commencing'])) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
