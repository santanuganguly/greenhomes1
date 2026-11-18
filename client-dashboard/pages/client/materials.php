<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Materials', 'tab' => 'materials']);

$materials = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM material_tracking WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([(int) $project['id']]);
    $materials = $stmt->fetchAll();
}
?>

<div class="p-content">
    <h1 class="p-title">Material Tracking</h1>

    <?php if (empty($materials)): ?>
    <div class="empty">No materials tracked yet.</div>
    <?php else: ?>
    <div class="grid grid-2">
        <?php foreach ($materials as $m):
            $ordered = (float) $m['quantity_ordered'];
            $delivered = (float) $m['quantity_delivered'];
            $pct = $ordered > 0 ? min(100, round(($delivered / $ordered) * 100)) : 0;
        ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:.5rem;">
                <div style="font-weight:600;"><?= htmlspecialchars($m['material_name']) ?></div>
                <span class="pill <?= $m['status'] === 'received' ? 'pill-green' : ($m['status'] === 'partial' ? 'pill-yellow' : 'pill-gray') ?>">
                    <?= ucfirst(htmlspecialchars($m['status'])) ?>
                </span>
            </div>
            <div style="font-size:.85rem;color:#666;margin-bottom:.5rem;">
                <?= $delivered ?> / <?= $ordered ?> <?= htmlspecialchars($m['unit']) ?>
            </div>
            <div class="progress"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
            <?php if ($m['expected_date']): ?>
            <div style="font-size:.75rem;color:#888;margin-top:.5rem;">Expected: <?= date('M j, Y', strtotime($m['expected_date'])) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
