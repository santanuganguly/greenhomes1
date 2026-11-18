<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Before & After', 'tab' => 'beforeafter']);

$items = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM before_after WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([(int) $project['id']]);
    $items = $stmt->fetchAll();
}
?>

<div class="p-content">
    <h1 class="p-title">Before & After</h1>

    <?php if (empty($items)): ?>
    <div class="empty">No before/after comparisons yet.</div>
    <?php else: ?>
    <div class="grid grid-2">
        <?php foreach ($items as $item): ?>
        <div class="card">
            <div style="font-weight:600;margin-bottom:.75rem;"><?= htmlspecialchars($item['stage_name']) ?></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">
                <div>
                    <div style="text-align:center;font-size:.75rem;font-weight:600;color:#888;margin-bottom:.25rem;">BEFORE</div>
                    <img src="<?= htmlspecialchars($item['before_url']) ?>" alt="Before" style="width:100%;border-radius:6px;aspect-ratio:4/3;object-fit:cover;">
                </div>
                <div>
                    <div style="text-align:center;font-size:.75rem;font-weight:600;color:#16a34a;margin-bottom:.25rem;">AFTER</div>
                    <img src="<?= htmlspecialchars($item['after_url']) ?>" alt="After" style="width:100%;border-radius:6px;aspect-ratio:4/3;object-fit:cover;">
                </div>
            </div>
            <?php if ($item['description']): ?>
            <div style="font-size:.85rem;color:#555;margin-top:.75rem;"><?= nl2br(htmlspecialchars($item['description'])) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
