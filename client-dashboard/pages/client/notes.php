<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Engineer Notes', 'tab' => 'notes']);

$notes = [];
if ($project) {
    $stmt = $db->prepare(
        "SELECT e.*, u.name AS engineer_name FROM engineer_notes e
         LEFT JOIN users u ON e.created_by = u.id
         WHERE e.project_id = ? ORDER BY e.note_date DESC"
    );
    $stmt->execute([(int) $project['id']]);
    $notes = $stmt->fetchAll();
}
?>

<div class="p-content">
    <h1 class="p-title">Engineer Notes</h1>

    <?php if (empty($notes)): ?>
    <div class="empty">No notes yet.</div>
    <?php else: ?>
    <?php foreach ($notes as $n): ?>
    <div class="card" style="margin-bottom:1rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;margin-bottom:.5rem;">
            <div style="font-size:.8rem;color:#888;"><?= date('M j, Y', strtotime($n['note_date'])) ?></div>
            <?php if ($n['engineer_name']): ?>
            <div style="font-size:.8rem;color:#666;">by <?= htmlspecialchars($n['engineer_name']) ?></div>
            <?php endif; ?>
        </div>
        <div style="font-size:.9rem;"><?= nl2br(htmlspecialchars($n['content'])) ?></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
