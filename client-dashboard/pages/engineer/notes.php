<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('site_engineer');
$db = db();

$notes = $db->prepare(
    "SELECT n.*, p.name as project_name
     FROM engineer_notes n
     JOIN projects p ON n.project_id = p.id
     WHERE n.created_by = ?
     ORDER BY n.created_at DESC"
);
$notes->execute([$user['id']]);
$notes = $notes->fetchAll();

portal_header(['title' => 'Daily Notes', 'active' => 'notes', 'tab' => 'eng-notes']);
?>

<div class="hero">
    <h1>Daily Notes</h1>
    <p>Your work history</p>
</div>

<?php if (empty($notes)): ?>
    <div class="card"><p class="empty">No notes yet. Go to Today's Work to submit your first report.</p></div>
<?php else: ?>
    <?php foreach ($notes as $n): ?>
        <div class="card" style="margin-bottom:1rem">
            <div style="display:flex;justify-content:space-between">
                <strong><?= htmlspecialchars($n['project_name']) ?></strong>
                <small><?= date('M j, Y \a\t g:i A', strtotime($n['created_at'])) ?></small>
            </div>
            <p style="margin-top:.5rem"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php portal_footer(); ?>
