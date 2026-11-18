<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('admin', 'super_admin');
$db = db();

$totalRevenue = (float) $db->query("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status = 'paid'")->fetchColumn();
$totalProjects = (int) $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$delayedProjects = (int) $db->query("SELECT COUNT(*) FROM projects WHERE expected_completion_date < CURDATE() AND status != 'completed'")->fetchColumn();
$avgProgress = (float) $db->query("SELECT COALESCE(AVG(progress_percentage),0) FROM projects WHERE status = 'in_progress'")->fetchColumn();
$totalClients = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$completedProjects = (int) $db->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn();

portal_header(['title' => 'Reports', 'active' => 'reports', 'tab' => 'admin-reports']);
?>

<div class="hero">
    <h1>Reports</h1>
    <p>Business overview and analytics</p>
</div>

<div class="grid grid-3">
    <div class="stat">
        <div class="stat__label">Total Revenue</div>
        <div class="stat__num"><?= format_currency($totalRevenue) ?></div>
    </div>
    <div class="stat">
        <div class="stat__label">Total Projects</div>
        <div class="stat__num"><?= $totalProjects ?></div>
    </div>
    <div class="stat">
        <div class="stat__label">Completed</div>
        <div class="stat__num"><?= $completedProjects ?></div>
    </div>
    <div class="stat">
        <div class="stat__label">Delayed Projects</div>
        <div class="stat__num" style="color: <?= $delayedProjects > 0 ? '#991b1b' : '#166534' ?>"><?= $delayedProjects ?></div>
    </div>
    <div class="stat">
        <div class="stat__label">Avg Progress</div>
        <div class="stat__num"><?= round($avgProgress) ?>%</div>
    </div>
    <div class="stat">
        <div class="stat__label">Total Clients</div>
        <div class="stat__num"><?= $totalClients ?></div>
    </div>
</div>

<div class="card" style="margin-top:1.5rem">
    <div class="card-title">Export Reports</div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <button class="btn btn-outline" disabled>Export Revenue (CSV)</button>
        <button class="btn btn-outline" disabled>Export Projects (CSV)</button>
        <button class="btn btn-outline" disabled>Export Progress (PDF)</button>
    </div>
    <p style="margin-top:.5rem;font-size:.85rem;color:#64748b">Export functionality coming soon.</p>
</div>

<?php portal_footer(); ?>
