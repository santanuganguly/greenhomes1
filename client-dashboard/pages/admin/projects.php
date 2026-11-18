<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('admin', 'super_admin');
$db = db();

$projects = $db->query(
    "SELECT p.*, u.name as client_name, m.name as manager_name,
            (SELECT w.published_at FROM weekly_updates w WHERE w.project_id = p.id AND w.status = 'published' ORDER BY w.published_at DESC LIMIT 1) as last_update_date
     FROM projects p
     LEFT JOIN users u ON p.client_id = u.id
     LEFT JOIN users m ON p.manager_id = m.id
     ORDER BY p.created_at DESC"
)->fetchAll();

portal_header(['title' => 'All Projects', 'tab' => 'admin-projects']);
?>

<div style="max-width:680px;margin:0 auto;">

<div class="p-header-row" style="margin-bottom:1rem;">
    <div class="p-header-title">
        <h1 style="font-size:1.3rem;">All Projects</h1>
    </div>
    <div class="p-header-actions">
        <a href="/admin/projects/new" class="btn btn-sm">+ New</a>
    </div>
</div>

<div class="gh-search">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="projectSearch" class="form-control" placeholder="Search projects, clients..." onkeyup="filterProjects()">
</div>

<div class="gh-list" style="margin-bottom:2rem;">
    <?php if (empty($projects)): ?>
        <div class="empty">No projects found</div>
    <?php else: ?>
        <?php foreach ($projects as $p): 
            $progress = (int)($p['progress_percentage'] ?? 0);
            $initials = mb_strtoupper(mb_substr($p['name'], 0, 2));
            
            $statusColor = 'pill-blue';
            if ($p['status'] === 'completed') $statusColor = 'pill-green';
            if ($p['status'] === 'on_hold') $statusColor = 'pill-yellow';
        ?>
        <a href="/admin/projects?id=<?= $p['id'] ?>" class="gh-list-item project-item" data-search="<?= strtolower(htmlspecialchars($p['name'] . ' ' . ($p['client_name'] ?? ''))) ?>">
            <div class="gh-avatar gh-avatar--lg gh-avatar--green"><?= $initials ?></div>
            <div class="gh-item-info">
                <div class="gh-item-title"><?= htmlspecialchars($p['name']) ?></div>
                <div class="gh-item-sub">
                    <?= htmlspecialchars($p['client_name'] ?? 'No Client') ?>
                    <?php if ($p['manager_name']): ?> • PM: <?= htmlspecialchars($p['manager_name']) ?><?php endif; ?>
                </div>
                <div class="gh-progress-row" style="margin-top:6px;">
                    <div class="gh-progress-track">
                        <div class="gh-progress-fill" style="width:<?= $progress ?>%"></div>
                    </div>
                    <div class="gh-progress-pct"><?= $progress ?>%</div>
                </div>
            </div>
            <div class="gh-item-meta">
                <span class="pill <?= $statusColor ?>"><?= ucfirst(str_replace('_', ' ', $p['status'])) ?></span>
                <?php if ($p['last_update_date']): ?>
                <div class="gh-item-time" style="margin-top:6px;" title="Last update">
                    <?= date('M j', strtotime($p['last_update_date'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div>

<script>
function filterProjects() {
    const term = document.getElementById('projectSearch').value.toLowerCase();
    const items = document.querySelectorAll('.project-item');
    items.forEach(item => {
        const text = item.getAttribute('data-search');
        if (text.indexOf(term) > -1) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php portal_footer(); ?>
