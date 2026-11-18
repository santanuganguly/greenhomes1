<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('head_engineer');
$db = db();

$selectedProject = (int) ($_GET['project_id'] ?? 0);
$projects = $db->prepare("SELECT id, name FROM projects WHERE manager_id = ? ORDER BY name");
$projects->execute([$user['id']]);
$projects = $projects->fetchAll();

$ideas = [];
if ($selectedProject) {
    $stmt = $db->prepare(
        "SELECT c.*, u.name AS sender_name, u.role AS sender_role FROM chat_messages c
         JOIN users u ON c.sender_id = u.id
         WHERE c.project_id = ? ORDER BY c.created_at DESC"
    );
    $stmt->execute([$selectedProject]);
    $ideas = $stmt->fetchAll();
} elseif (!empty($projects)) {
    $pids = array_column($projects, 'id');
    if (!empty($pids)) {
        $placeholders = implode(',', array_fill(0, count($pids), '?'));
        $stmt = $db->prepare(
            "SELECT c.*, u.name AS sender_name, u.role AS sender_role, p.name as project_name FROM chat_messages c
             JOIN users u ON c.sender_id = u.id
             JOIN projects p ON c.project_id = p.id
             WHERE c.project_id IN ($placeholders) ORDER BY c.created_at DESC LIMIT 50"
        );
        $stmt->execute($pids);
        $ideas = $stmt->fetchAll();
    }
}

portal_header(['title' => 'Design Ideas', 'active' => 'design-ideas', 'tab' => 'pm-design-ideas']);
?>

<div class="hero">
    <h1>Design Ideas</h1>
    <p>View client design suggestions for your projects</p>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <form method="get">
        <div class="form-group" style="margin-bottom:0;">
            <label>Filter by Project</label>
            <select name="project_id" class="form-control" onchange="this.form.submit()" style="max-width:400px;">
                <option value="">All My Projects</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $selectedProject === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if (empty($ideas)): ?>
<div class="card"><p class="empty">No design ideas found.</p></div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:1rem;">
    <?php foreach ($ideas as $idea): ?>
    <?php
        $photoUrl = '';
        $tag = 'general';
        $text = $idea['message'];
        if (preg_match('/\[photo:(https?:\/\/[^\]]+)\]/', $text, $m)) {
            $photoUrl = $m[1];
            $text = str_replace($m[0], '', $text);
        }
        if (preg_match('/\[tag:(\w+)\]/', $text, $m)) {
            $tag = $m[1];
            $text = str_replace($m[0], '', $text);
        }
        $text = trim($text);
    ?>
    <div class="card" style="overflow:hidden;<?= $photoUrl ? 'border-left:4px solid #166534;' : '' ?>">
        <div style="display:flex;gap:1rem;<?= $photoUrl ? 'flex-direction:row;' : '' ?>">
            <?php if ($photoUrl): ?>
            <div style="flex:0 0 200px;cursor:pointer;" onclick="previewPhoto('<?= htmlspecialchars($photoUrl) ?>')">
                <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Design idea" style="width:200px;height:150px;object-fit:cover;border-radius:8px;transition:opacity .2s;" onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'" onerror="this.parentElement.innerHTML='<div style=\'width:200px;height:150px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;border-radius:8px;\'>📷</div>'">
            </div>
            <?php endif; ?>
            <div style="flex:1;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
                    <div>
                        <strong><?= htmlspecialchars($idea['sender_name']) ?></strong>
                        <span class="pill pill-<?= $idea['sender_role'] === 'client' ? 'blue' : 'gray' ?>" style="margin-left:.5rem;"><?= ucfirst(str_replace('_', ' ', $idea['sender_role'])) ?></span>
                        <?php if (!empty($idea['project_name'])): ?>
                        <small style="color:#64748b;margin-left:.5rem;">on <?= htmlspecialchars($idea['project_name']) ?></small>
                        <?php endif; ?>
                    </div>
                    <small style="color:#64748b;"><?= time_ago($idea['created_at']) ?></small>
                </div>
                <?php if ($text): ?>
                <p style="margin:.5rem 0;line-height:1.6;"><?= nl2br(htmlspecialchars($text)) ?></p>
                <?php endif; ?>
                <?php if ($tag !== 'general'): ?>
                <span style="display:inline-block;font-size:.7rem;padding:2px 8px;border-radius:99px;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;"><?= ucfirst($tag) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php portal_footer(); ?>
