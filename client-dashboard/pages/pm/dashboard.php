<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('head_engineer');
$db = db();

$myProjects = $db->prepare("SELECT COUNT(*) FROM projects WHERE manager_id = ?");
$myProjects->execute([$user['id']]);
$myProjects = (int) $myProjects->fetchColumn();

$activeProjects = $db->prepare("SELECT COUNT(*) FROM projects WHERE manager_id = ? AND status = 'in_progress'");
$activeProjects->execute([$user['id']]);
$activeProjects = (int) $activeProjects->fetchColumn();

$pendingApprovals = $db->prepare("SELECT COUNT(*) FROM weekly_updates w JOIN projects p ON w.project_id = p.id WHERE p.manager_id = ? AND w.status = 'pending_approval'");
$pendingApprovals->execute([$user['id']]);
$pendingApprovals = (int) $pendingApprovals->fetchColumn();

$recentProjects = $db->prepare(
    "SELECT p.*, u.name as client_name FROM projects p LEFT JOIN users u ON p.client_id = u.id WHERE p.manager_id = ? ORDER BY p.created_at DESC LIMIT 5"
);
$recentProjects->execute([$user['id']]);
$recentProjects = $recentProjects->fetchAll();

portal_header(['title' => 'Overview', 'tab' => 'pm-dashboard']);

$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
$firstName = explode(' ', $user['name'])[0];
?>

<div style="max-width:680px;margin:0 auto;">

<!-- GREETING -->
<div class="gh-greeting gh-animate">
    <div class="gh-greeting__h"><?= $greeting ?>, <?= htmlspecialchars($firstName) ?> 👋</div>
    <div class="gh-greeting__sub">Project Manager Overview</div>
</div>

<!-- QUICK STATS -->
<div class="gh-stats">
    <div class="gh-stat">
        <div class="gh-stat__num"><?= $activeProjects ?></div>
        <div class="gh-stat__label">Active</div>
    </div>
    <div class="gh-stat">
        <div class="gh-stat__num"><?= $myProjects ?></div>
        <div class="gh-stat__label">Total Projects</div>
    </div>
    <div class="gh-stat">
        <div class="gh-stat__num" style="<?= $pendingApprovals > 0 ? 'color:#b45309' : '' ?>"><?= $pendingApprovals ?></div>
        <div class="gh-stat__label">Approvals</div>
    </div>
    <div class="gh-stat">
        <div class="gh-stat__num">--</div>
        <div class="gh-stat__label">Schedule</div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="gh-actions">
    <a href="/pm/projects" class="gh-action">
        <div class="gh-action__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        </div>
        Projects
    </a>
    <a href="/pm/approvals" class="gh-action">
        <div class="gh-action__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        Approvals
    </a>
    <a href="/pm/schedule" class="gh-action">
        <div class="gh-action__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        Schedule
    </a>
    <a href="/pm/clients" class="gh-action">
        <div class="gh-action__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        Clients
    </a>
</div>

<!-- ACTION REQUIRED -->
<?php if ($pendingApprovals > 0): ?>
<div class="gh-section">
    <span class="gh-section__title" style="color:#b45309">Action Required</span>
</div>
<a href="/pm/approvals" class="gh-list" style="display:block;margin-bottom:1rem;text-decoration:none;">
    <div class="gh-list-item">
        <div class="gh-avatar gh-avatar--md gh-avatar--amber">!</div>
        <div class="gh-item-info">
            <div class="gh-item-title"><?= $pendingApprovals ?> Updates Pending Approval</div>
            <div class="gh-item-sub">Review weekly updates from site engineers</div>
        </div>
        <div class="gh-item-meta">
            <span class="pill pill-yellow">Review</span>
        </div>
    </div>
</a>
<?php endif; ?>

<!-- RECENT PROJECTS -->
<div class="gh-section">
    <span class="gh-section__title">Recent Projects</span>
    <a href="/pm/projects" class="gh-section__link">View All →</a>
</div>
<div class="gh-list" style="margin-bottom:1rem;">
    <?php if (empty($recentProjects)): ?>
        <div class="gh-list-item" style="justify-content:center;color:var(--gh-text-muted);padding:2rem;">No projects found</div>
    <?php else: ?>
        <?php foreach ($recentProjects as $p): 
            $progress = (int)($p['progress_percentage'] ?? 0);
        ?>
        <a href="/pm/projects?id=<?= $p['id'] ?>" class="gh-list-item">
            <div class="gh-avatar gh-avatar--md gh-avatar--green">
                <?= mb_strtoupper(mb_substr($p['name'], 0, 2)) ?>
            </div>
            <div class="gh-item-info">
                <div class="gh-item-title"><?= htmlspecialchars($p['name']) ?></div>
                <div class="gh-item-sub"><?= htmlspecialchars($p['client_name'] ?? 'No Client Assigned') ?></div>
                <div class="gh-progress-row" style="margin-top:6px;">
                    <div class="gh-progress-track">
                        <div class="gh-progress-fill" style="width:<?= $progress ?>%"></div>
                    </div>
                    <div class="gh-progress-pct"><?= $progress ?>%</div>
                </div>
            </div>
            <div class="gh-item-meta">
                <span class="pill <?= $p['status'] === 'completed' ? 'pill-green' : ($p['status'] === 'on_hold' ? 'pill-yellow' : 'pill-blue') ?>">
                    <?= ucfirst(str_replace('_', ' ', $p['status'])) ?>
                </span>
            </div>
        </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div>

<?php portal_footer(); ?>
