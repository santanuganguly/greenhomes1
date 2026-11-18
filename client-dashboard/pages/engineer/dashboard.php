<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('site_engineer');
$db = db();

$projects = $db->prepare(
    "SELECT p.*, u.name as client_name, m.name as manager_name
     FROM projects p
     LEFT JOIN users u ON p.client_id = u.id
     LEFT JOIN users m ON p.manager_id = m.id
     WHERE p.engineer_id = ? AND p.status != 'completed'
     ORDER BY p.created_at DESC"
);
$projects->execute([$user['id']]);
$projects = $projects->fetchAll();

portal_header(['title' => 'Engineer Dashboard', 'active' => '', 'tab' => 'eng-dashboard']);
?>

<div class="hero">
    <h1>Welcome, <?= htmlspecialchars($user['name']) ?></h1>
    <p>Site Engineer Dashboard</p>
</div>

<div class="card" style="margin-bottom:1.5rem">
    <div class="card-title">Assigned Projects</div>
    <?php if (empty($projects)): ?>
        <p class="empty">No active projects assigned</p>
    <?php else: ?>
        <?php foreach ($projects as $p): ?>
            <div style="border-bottom:1px solid #e2e8f0;padding:.75rem 0;display:flex;justify-content:space-between;align-items:center">
                <div>
                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                    <div style="font-size:.85rem;color:#64748b"><?= htmlspecialchars($p['location'] ?? '') ?> | Client: <?= htmlspecialchars($p['client_name'] ?? '—') ?></div>
                </div>
                <div style="text-align:right">
                    <div class="progress" style="width:100px"><div class="progress-fill" style="width:<?= $p['progress_percentage'] ?>%"></div></div>
                    <small><?= $p['progress_percentage'] ?>%</small>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="grid grid-3">
    <a href="/engineer/today" class="card" style="text-decoration:none;text-align:center">
        <div style="font-size:1.5rem;margin-bottom:.25rem">📋</div>
        <strong>Today's Work</strong>
    </a>
    <a href="/engineer/weekly" class="card" style="text-decoration:none;text-align:center">
        <div style="font-size:1.5rem;margin-bottom:.25rem">📝</div>
        <strong>Weekly Update</strong>
    </a>
    <a href="/engineer/notes" class="card" style="text-decoration:none;text-align:center">
        <div style="font-size:1.5rem;margin-bottom:.25rem">📒</div>
        <strong>Daily Notes</strong>
    </a>
</div>

<?php portal_footer(); ?>
