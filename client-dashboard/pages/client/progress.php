<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Timeline', 'tab' => 'progress']);

$milestones = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM milestones WHERE project_id = ? ORDER BY order_index ASC");
    $stmt->execute([(int) $project['id']]);
    $milestones = $stmt->fetchAll();
}
?>

<div class="p-content">
    <h1 class="p-title">Project Timeline</h1>

    <?php if (empty($milestones)): ?>
    <div class="empty">No milestones defined yet.</div>
    <?php else: ?>
    <div style="position:relative;padding-left:2rem;">
        <div style="position:absolute;left:0.75rem;top:0;bottom:0;width:2px;background:#e5e7eb;"></div>
        <?php foreach ($milestones as $m): ?>
        <div style="position:relative;margin-bottom:2rem;padding-left:1.5rem;">
            <div style="position:absolute;left:-1.25rem;top:.25rem;width:1.5rem;height:1.5rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;
                <?php if ($m['status'] === 'completed'): ?>background:#16a34a;color:#fff;
                <?php elseif ($m['status'] === 'in_progress'): ?>background:#facc15;color:#000;
                <?php else: ?>background:#d1d5db;color:#666;
                <?php endif; ?>">
                <?php if ($m['status'] === 'completed'): ?>✔<?php endif; ?>
            </div>
            <div class="card" style="margin:0;">
                <div style="font-weight:600;"><?= htmlspecialchars($m['title']) ?></div>
                <?php if ($m['description']): ?>
                <div style="font-size:.9rem;color:#555;margin-top:.25rem;"><?= nl2br(htmlspecialchars($m['description'])) ?></div>
                <?php endif; ?>
                <div style="font-size:.8rem;color:#888;margin-top:.5rem;">
                    <?php if ($m['expected_date']): ?>
                    Expected: <?= date('M j, Y', strtotime($m['expected_date'])) ?>
                    <?php endif; ?>
                    <?php if ($m['completed_date']): ?>
                    · Completed: <?= date('M j, Y', strtotime($m['completed_date'])) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
