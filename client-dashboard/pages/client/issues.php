<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Issues', 'tab' => 'issues']);

$issues = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM project_issues WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([(int) $project['id']]);
    $issues = $stmt->fetchAll();
}
?>

<div class="p-content">
    <h1 class="p-title">Issue Tracker</h1>

    <?php if (empty($issues)): ?>
    <div class="empty">No issues reported.</div>
    <?php else: ?>
    <?php foreach ($issues as $i): ?>
    <div class="card" style="margin-bottom:1rem;">
        <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:.5rem;margin-bottom:.5rem;">
            <div style="font-weight:600;"><?= htmlspecialchars($i['title']) ?></div>
            <div style="display:flex;gap:.5rem;">
                <span class="pill <?= $i['severity'] === 'critical' ? 'pill-red' : ($i['severity'] === 'high' ? 'pill-red' : ($i['severity'] === 'medium' ? 'pill-yellow' : 'pill-gray')) ?>">
                    <?= ucfirst(htmlspecialchars($i['severity'])) ?>
                </span>
                <span class="pill <?= $i['status'] === 'resolved' || $i['status'] === 'closed' ? 'pill-green' : ($i['status'] === 'in_progress' ? 'pill-blue' : 'pill-yellow') ?>">
                    <?= ucfirst(htmlspecialchars(str_replace('_', ' ', $i['status']))) ?>
                </span>
            </div>
        </div>
        <?php if ($i['description']): ?>
        <div style="font-size:.9rem;color:#555;margin-bottom:.5rem;"><?= nl2br(htmlspecialchars($i['description'])) ?></div>
        <?php endif; ?>
        <?php if ($i['impact']): ?>
        <div style="font-size:.85rem;color:#666;margin-bottom:.5rem;"><strong>Impact:</strong> <?= htmlspecialchars($i['impact']) ?></div>
        <?php endif; ?>
        <?php if ($i['resolution']): ?>
        <div style="font-size:.85rem;color:#16a34a;margin-top:.5rem;"><strong>Resolution:</strong> <?= nl2br(htmlspecialchars($i['resolution'])) ?></div>
        <?php endif; ?>
        <div style="font-size:.75rem;color:#aaa;margin-top:.5rem;">Reported <?= date('M j, Y', strtotime($i['created_at'])) ?></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
