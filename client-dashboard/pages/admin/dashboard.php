<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('admin', 'super_admin');
$db = db();

$totalClients = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$activeProjects = (int) $db->query("SELECT COUNT(*) FROM projects WHERE status IN ('in_progress','on_hold')")->fetchColumn();
$completedProjects = (int) $db->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn();
$pendingPayments = (float) $db->query("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status IN ('pending','overdue')")->fetchColumn();
$pendingUpdates = $db->query("SELECT w.*, p.name as project_name FROM weekly_updates w JOIN projects p ON w.project_id = p.id WHERE w.status = 'pending_approval' ORDER BY w.created_at DESC LIMIT 5")->fetchAll();
$recentProjects = $db->query("SELECT p.*, u.name as client_name FROM projects p LEFT JOIN users u ON p.client_id = u.id ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

portal_header(['title' => 'Overview', 'tab' => 'admin-dashboard']);

$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
$firstName = explode(' ', $user['name'])[0];
?>

<div style="max-width:680px;margin:0 auto;">

<!-- GREETING -->
<div class="gh-greeting gh-animate">
    <div class="gh-greeting__h"><?= $greeting ?>, <?= htmlspecialchars($firstName) ?> 👋</div>
    <div class="gh-greeting__sub">System Administrator Overview</div>
</div>

<!-- QUICK STATS -->
<div class="gh-stats">
    <div class="gh-stat">
        <div class="gh-stat__num"><?= $activeProjects ?></div>
        <div class="gh-stat__label">Active</div>
    </div>
    <div class="gh-stat">
        <div class="gh-stat__num"><?= $completedProjects ?></div>
        <div class="gh-stat__label">Completed</div>
    </div>
    <div class="gh-stat">
        <div class="gh-stat__num"><?= $totalClients ?></div>
        <div class="gh-stat__label">Clients</div>
    </div>
    <div class="gh-stat">
        <div class="gh-stat__num" style="font-size:1.1rem;display:flex;align-items:center;justify-content:center;height:100%;"><?= format_currency($pendingPayments) ?></div>
        <div class="gh-stat__label" style="margin-top:-5px">Pending ₹</div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="gh-actions">
    <a href="/admin/projects/new" class="gh-action">
        <div class="gh-action__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </div>
        New Project
    </a>
    <a href="/admin/clients" class="gh-action">
        <div class="gh-action__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        Clients
    </a>
    <a href="/admin/payments" class="gh-action">
        <div class="gh-action__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 8h12"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 13h8.5l-5 8"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 13h3"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 13c6.667 0 6.667-10 0-10"/></svg>
        </div>
        Payments
    </a>
    <a href="/admin/notifications" class="gh-action">
        <div class="gh-action__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 17H2a3 3 0 0 0 3-3V9a7 7 0 0 1 14 0v5a3 3 0 0 0 3 3zm-8.27 4a2 2 0 0 1-3.46 0"/></svg>
        </div>
        Notify
    </a>
</div>

<!-- PENDING UPDATES (IF ANY) -->
<?php if (!empty($pendingUpdates)): ?>
<div class="gh-section">
    <span class="gh-section__title" style="color:#b45309">Action Required</span>
</div>
<div class="gh-list" style="margin-bottom:1rem; border-color:#fcd34d;">
    <?php foreach ($pendingUpdates as $u): ?>
    <a href="/admin/projects?id=<?= $u['project_id'] ?>&tab=milestones" class="gh-list-item">
        <div class="gh-avatar gh-avatar--md gh-avatar--amber">W<?= $u['week_number'] ?></div>
        <div class="gh-item-info">
            <div class="gh-item-title"><?= htmlspecialchars($u['project_name']) ?></div>
            <div class="gh-item-sub">Pending Review: <?= htmlspecialchars($u['title']) ?></div>
        </div>
        <div class="gh-item-meta">
            <div class="gh-item-time"><?= date('M j', strtotime($u['created_at'])) ?></div>
            <span class="pill pill-yellow">Review</span>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- RECENT PROJECTS -->
<div class="gh-section">
    <span class="gh-section__title">Recent Projects</span>
    <a href="/admin/projects" class="gh-section__link">View All →</a>
</div>
<div class="gh-list" style="margin-bottom:1rem;">
    <?php if (empty($recentProjects)): ?>
        <div class="gh-list-item" style="justify-content:center;color:var(--gh-text-muted);padding:2rem;">No projects found</div>
    <?php else: ?>
        <?php foreach ($recentProjects as $p): 
            $progress = (int)($p['progress_percentage'] ?? 0);
        ?>
        <a href="/admin/projects?id=<?= $p['id'] ?>" class="gh-list-item">
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
