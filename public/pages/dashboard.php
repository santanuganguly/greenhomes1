<?php
require_once __DIR__ . '/dashboard-layout.php';

$user = current_user();
$db = gh_db();

// Fetch active project
$stmt = $db->prepare("SELECT * FROM projects WHERE client_id = ? AND status != 'completed' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user['id']]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

gh_dashboard_header(['title' => 'Dashboard', 'active' => '', 'tab' => 'overview']);
?>

<div class="d-card">
    <h1 class="d-title">Welcome back, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?> 👋</h1>
    <?php if ($project): ?>
        <p style="color: #64748b; margin-top: 0.5rem;">
            <?= htmlspecialchars($project['name']) ?> — <?= htmlspecialchars($project['location']) ?>
        </p>
    <?php else: ?>
        <p style="color: #64748b; margin-top: 0.5rem;">No active projects found. We are setting up your dashboard.</p>
    <?php endif; ?>
</div>

<?php if ($project): ?>
<div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
    <div class="d-card">
        <h2 class="d-title" style="font-size: 1.25rem;">Project Status</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-top: 1rem;">
            <div>
                <strong style="display: block; font-size: 0.875rem; color: #64748b;">Stage</strong>
                <span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $project['status']))) ?></span>
            </div>
            <div>
                <strong style="display: block; font-size: 0.875rem; color: #64748b;">Start Date</strong>
                <span><?= $project['start_date'] ? date('M j, Y', strtotime($project['start_date'])) : 'TBD' ?></span>
            </div>
            <div>
                <strong style="display: block; font-size: 0.875rem; color: #64748b;">Expected Completion</strong>
                <span><?= $project['expected_completion_date'] ? date('M j, Y', strtotime($project['expected_completion_date'])) : 'TBD' ?></span>
            </div>
        </div>
    </div>
    <div class="d-card">
        <h2 class="d-title" style="font-size: 1.25rem;">Quick Actions</h2>
        <div style="display: flex; flex-direction: row; gap: 1rem; flex-wrap: wrap;">
            <a href="/dashboard/gallery" class="d-btn">View Latest Photos</a>
            <a href="/dashboard/payments" class="d-btn" style="background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1;">Check Pending Invoices</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Also fetch their quote requests if they have any? The old dashboard had this.
// Let's restore the old quote list below the main project overview.
require_once dirname(__DIR__, 2) . '/src/repo/quotes.php';
$quotes = [];
try {
    $quotes = quotes_for_user((int) $user['id']);
} catch (PDOException $e) {}

if (!empty($quotes)):
?>
<div class="d-card" style="margin-top: 2rem;">
    <h2 class="d-title" style="font-size: 1.25rem;">Your Quote Requests</h2>
    <table style="width: 100%; text-align: left; border-collapse: collapse; margin-top: 1rem;">
        <thead>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <th style="padding: 0.75rem 0;">Service</th>
                <th>City</th>
                <th>Status</th>
                <th>Submitted</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($quotes as $q): ?>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 0.75rem 0;"><?= htmlspecialchars(ucfirst($q['service_type'])) ?></td>
                <td><?= htmlspecialchars($q['city']) ?></td>
                <td><?= htmlspecialchars($q['status']) ?></td>
                <td><?= htmlspecialchars(date('d M Y', strtotime($q['created_at']))) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php gh_dashboard_footer(); ?>
