<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Home', 'tab' => 'overview']);

$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
$firstName = explode(' ', $user['name'])[0];

$pendingInvoices = [];
$latestUpdate = null;
$milestones = [];
$daysCompleted = 0;
$unreadMessages = 0;

if ($project) {
    $pid = (int) $project['id'];

    $stmt = $db->prepare("SELECT * FROM invoices WHERE project_id = ? AND status IN ('pending','overdue') ORDER BY due_date ASC LIMIT 1");
    $stmt->execute([$pid]);
    $pendingInvoices = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT * FROM weekly_updates WHERE project_id = ? AND status = 'published' ORDER BY published_at DESC LIMIT 1");
    $stmt->execute([$pid]);
    $latestUpdate = $stmt->fetch() ?: null;

    $stmt = $db->prepare("SELECT * FROM milestones WHERE project_id = ? ORDER BY order_index ASC LIMIT 6");
    $stmt->execute([$pid]);
    $milestones = $stmt->fetchAll();

    if ($project['start_date']) {
        $daysCompleted = (int) floor((time() - strtotime($project['start_date'])) / 86400);
        if ($daysCompleted < 0) $daysCompleted = 0;
    }

    if (function_exists('chat_get_unread_count')) {
        $unreadMessages = chat_get_unread_count((int) $user['id']);
    }
}

$progress = (int) ($project['progress_percentage'] ?? 0);
$totalMilestones = (int) ($project['total_milestones'] ?? 0);
$doneMilestones  = (int) ($project['completed_milestones'] ?? 0);
?>

<div style="max-width:680px;margin:0 auto;">

<!-- GREETING -->
<div class="gh-greeting gh-animate">
    <div class="gh-greeting__h"><?= $greeting ?>, <?= htmlspecialchars($firstName) ?> 👋</div>
    <div class="gh-greeting__sub">
        <?php if ($project): ?>
            <?= htmlspecialchars($project['name']) ?> · <?= $progress ?>% complete
        <?php else: ?>
            Welcome to Green Homes Portal
        <?php endif; ?>
    </div>
</div>

<?php if ($project): ?>

<!-- QUICK STATS -->
<div class="gh-stats">
    <div class="gh-stat">
        <div class="gh-stat__num"><?= $progress ?>%</div>
        <div class="gh-stat__label">Done</div>
    </div>
    <div class="gh-stat">
        <div class="gh-stat__num"><?= $doneMilestones ?>/<?= $totalMilestones ?></div>
        <div class="gh-stat__label">Milestones</div>
    </div>
    <div class="gh-stat">
        <div class="gh-stat__num"><?= $daysCompleted ?></div>
        <div class="gh-stat__label">Days In</div>
    </div>
    <div class="gh-stat">
        <div class="gh-stat__num" style="<?= count($pendingInvoices) > 0 ? 'color:#b45309' : '' ?>"><?= count($pendingInvoices) ?></div>
        <div class="gh-stat__label">Payments Due</div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="gh-actions">
    <a href="/dashboard/project-chats" class="gh-action">
        <div class="gh-action__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        Chat<?php if ($unreadMessages > 0): ?> <span style="background:#22c55e;color:#fff;border-radius:999px;padding:0 5px;font-size:0.65rem;"><?= $unreadMessages ?></span><?php endif; ?>
    </a>
    <a href="/dashboard/payments" class="gh-action">
        <div class="gh-action__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 8h12"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 13h8.5l-5 8"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 13h3"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 13c6.667 0 6.667-10 0-10"/></svg>
        </div>
        Payments
    </a>
    <a href="/dashboard/gallery" class="gh-action">
        <div class="gh-action__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </div>
        Photos
    </a>
    <a href="/dashboard/documents" class="gh-action">
        <div class="gh-action__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        Docs
    </a>
</div>

<!-- PROJECT PROGRESS CARD -->
<div class="gh-section">
    <span class="gh-section__title">Project Progress</span>
    <a href="/dashboard/progress" class="gh-section__link">Timeline →</a>
</div>
<div class="gh-list" style="margin-bottom:1rem;">
    <div class="gh-list-item" style="padding:1.125rem;">
        <div class="gh-avatar gh-avatar--lg gh-avatar--green">
            <?= mb_strtoupper(mb_substr($project['name'], 0, 2)) ?>
        </div>
        <div class="gh-item-info">
            <div class="gh-item-title"><?= htmlspecialchars($project['name']) ?></div>
            <?php if (!empty($project['location'])): ?>
            <div class="gh-item-sub">📍 <?= htmlspecialchars($project['location']) ?></div>
            <?php endif; ?>
            <div class="gh-progress-row" style="margin-top:8px;">
                <div class="gh-progress-track">
                    <div class="gh-progress-fill" style="width:<?= $progress ?>%"></div>
                </div>
                <div class="gh-progress-pct"><?= $progress ?>%</div>
            </div>
        </div>
        <div class="gh-item-meta">
            <span class="pill <?= $project['status'] === 'completed' ? 'pill-green' : ($project['status'] === 'on_hold' ? 'pill-yellow' : 'pill-blue') ?>">
                <?= ucfirst(str_replace('_', ' ', $project['status'] ?? '')) ?>
            </span>
            <?php if ($project['expected_completion_date']): ?>
            <div class="gh-item-time" style="margin-top:4px;">
                Est. <?= date('M Y', strtotime($project['expected_completion_date'])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($pendingInvoices)): ?>
<!-- PENDING PAYMENT ALERT -->
<?php $inv = $pendingInvoices[0]; ?>
<div class="gh-section"><span class="gh-section__title">Payment Due</span></div>
<a href="/dashboard/payments" class="gh-list" style="display:block;margin-bottom:1rem;text-decoration:none;">
    <div class="gh-list-item">
        <div class="gh-avatar gh-avatar--md" style="background:linear-gradient(135deg,#d97706,#b45309);">₹</div>
        <div class="gh-item-info">
            <div class="gh-item-title"><?= htmlspecialchars($inv['title']) ?></div>
            <div class="gh-item-sub">Due <?= date('M j, Y', strtotime($inv['due_date'])) ?></div>
        </div>
        <div class="gh-item-meta">
            <div style="font-size:1rem;font-weight:700;color:#b45309;"><?= format_currency((float)$inv['amount']) ?></div>
            <div class="gh-item-time">Pending</div>
        </div>
    </div>
</a>
<?php endif; ?>

<!-- LATEST UPDATE -->
<?php if ($latestUpdate): ?>
<div class="gh-section">
    <span class="gh-section__title">Latest Update</span>
    <a href="/dashboard/weekly" class="gh-section__link">All Updates →</a>
</div>
<div class="gh-list" style="margin-bottom:1rem;">
    <a href="/dashboard/weekly" class="gh-list-item" style="align-items:flex-start;padding:1.125rem;">
        <div class="gh-avatar gh-avatar--md" style="background:linear-gradient(135deg,#0f766e,#134e4a);margin-top:2px;">W<?= (int)$latestUpdate['week_number'] ?></div>
        <div class="gh-item-info">
            <div class="gh-item-title"><?= htmlspecialchars($latestUpdate['title']) ?></div>
            <div class="gh-item-sub" style="white-space:normal;-webkit-line-clamp:2;display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden;">
                <?= htmlspecialchars(mb_substr($latestUpdate['work_completed'] ?? '', 0, 120)) ?><?= mb_strlen($latestUpdate['work_completed'] ?? '') > 120 ? '…' : '' ?>
            </div>
            <div class="gh-progress-row" style="margin-top:8px;">
                <div class="gh-progress-track"><div class="gh-progress-fill" style="width:<?= (int)$latestUpdate['progress_after'] ?>%"></div></div>
                <div class="gh-progress-pct"><?= (int)$latestUpdate['progress_after'] ?>%</div>
            </div>
        </div>
        <div class="gh-item-meta">
            <div class="gh-item-time"><?= $latestUpdate['published_at'] ? date('M j', strtotime($latestUpdate['published_at'])) : '' ?></div>
        </div>
    </a>
</div>
<?php endif; ?>

<!-- MILESTONES -->
<?php if (!empty($milestones)): ?>
<div class="gh-section">
    <span class="gh-section__title">Milestones</span>
</div>
<div class="gh-list" style="margin-bottom:1rem;">
    <?php foreach ($milestones as $m):
        $icon  = $m['status'] === 'completed' ? '✅' : ($m['status'] === 'in_progress' ? '🔄' : '⬜');
        $color = $m['status'] === 'completed' ? 'pill-green' : ($m['status'] === 'in_progress' ? 'pill-blue' : 'pill-gray');
    ?>
    <div class="gh-list-item">
        <div style="font-size:1.4rem;flex-shrink:0;width:36px;text-align:center;"><?= $icon ?></div>
        <div class="gh-item-info">
            <div class="gh-item-title"><?= htmlspecialchars($m['title']) ?></div>
            <?php if ($m['expected_date']): ?>
            <div class="gh-item-sub"><?= date('M j, Y', strtotime($m['expected_date'])) ?></div>
            <?php endif; ?>
        </div>
        <div class="gh-item-meta">
            <span class="pill <?= $color ?>"><?= ucfirst(str_replace('_', ' ', $m['status'])) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php else: ?>
<!-- NO PROJECT -->
<div class="gh-list" style="text-align:center;padding:3rem 1rem;">
    <div style="font-size:3rem;margin-bottom:0.75rem;">🏗️</div>
    <div style="font-weight:600;color:var(--gh-text);margin-bottom:0.5rem;">No Active Project</div>
    <div style="font-size:0.9rem;color:var(--gh-text-muted);">Your project details will appear here once assigned. Contact your project manager.</div>
</div>
<?php endif; ?>

</div>

<?php portal_footer(); ?>
