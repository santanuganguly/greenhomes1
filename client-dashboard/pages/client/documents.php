<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Documents', 'tab' => 'documents']);

$docs = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM documents WHERE project_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([(int) $project['id']]);
    $docs = $stmt->fetchAll();
}

$icons = [
    'pdf' => '📄', 'doc' => '📝', 'docx' => '📝', 'xls' => '📊', 'xlsx' => '📊',
    'jpg' => '🖼', 'jpeg' => '🖼', 'png' => '🖼', 'dwg' => '📐', 'dxf' => '📐',
];
?>

<div class="p-content">
    <h1 class="p-title">Documents</h1>

    <?php if (empty($docs)): ?>
    <div class="empty">No documents uploaded yet.</div>
    <?php else: ?>
    <div class="grid grid-3">
        <?php foreach ($docs as $d):
            $ext = strtolower(pathinfo($d['url'], PATHINFO_EXTENSION));
            $icon = $icons[$ext] ?? '📁';
        ?>
        <a href="<?= htmlspecialchars($d['url']) ?>" target="_blank" rel="noopener" class="card" style="text-decoration:none;color:inherit;display:block;">
            <div style="font-size:2rem;margin-bottom:.5rem;"><?= $icon ?></div>
            <div style="font-weight:600;"><?= htmlspecialchars($d['title']) ?></div>
            <div style="font-size:.8rem;color:#888;margin-top:.25rem;"><?= htmlspecialchars(ucfirst($d['type'])) ?></div>
            <div style="font-size:.75rem;color:#aaa;margin-top:.25rem;"><?= date('M j, Y', strtotime($d['uploaded_at'])) ?></div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
