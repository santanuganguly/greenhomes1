<?php
require_once __DIR__ . '/dashboard-layout.php';

$user = current_user();
$db = gh_db();

// Fetch active project
$stmt = $db->prepare("SELECT * FROM projects WHERE client_id = ? AND status != 'completed' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user['id']]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

$invoices = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM invoices WHERE project_id = ? ORDER BY due_date ASC");
    $stmt->execute([$project['id']]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

gh_dashboard_header(['title' => 'Payments', 'active' => '', 'tab' => 'payments']);
?>

<div class="d-card">
    <h1 class="d-title">Payments & Invoices</h1>
    <p style="color: #64748b; margin-bottom: 2rem;">Track your project milestones and construction payments.</p>

    <?php if (!$project): ?>
        <div style="text-align: center; padding: 3rem 0; color: #64748b;">
            <p>No active project found.</p>
        </div>
    <?php elseif (empty($invoices)): ?>
        <div style="text-align: center; padding: 3rem 0; color: #64748b;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem auto; opacity: 0.5;"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            <p>No invoices have been generated for your project yet.</p>
        </div>
    <?php else: ?>
        <table style="width: 100%; text-align: left; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 0.875rem;">
                    <th style="padding: 1rem 0.5rem; font-weight: 500;">Invoice Name</th>
                    <th style="padding: 1rem 0.5rem; font-weight: 500;">Amount</th>
                    <th style="padding: 1rem 0.5rem; font-weight: 500;">Due Date</th>
                    <th style="padding: 1rem 0.5rem; font-weight: 500;">Status</th>
                    <th style="padding: 1rem 0.5rem; font-weight: 500; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): 
                    $isPaid = $inv['status'] === 'paid';
                    $isOverdue = $inv['status'] === 'overdue';
                    
                    $statusColor = $isPaid ? '#166534' : ($isOverdue ? '#991b1b' : '#b45309');
                    $statusBg = $isPaid ? '#dcfce7' : ($isOverdue ? '#fee2e2' : '#fef3c7');
                ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 1rem 0.5rem; font-weight: 500; color: #0f172a;"><?= htmlspecialchars($inv['title']) ?></td>
                        <td style="padding: 1rem 0.5rem;">₹<?= number_format($inv['amount'], 2) ?></td>
                        <td style="padding: 1rem 0.5rem; color: #64748b;"><?= date('M j, Y', strtotime($inv['due_date'])) ?></td>
                        <td style="padding: 1rem 0.5rem;">
                            <span style="font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 999px; background: <?= $statusBg ?>; color: <?= $statusColor ?>; font-weight: 600; text-transform: uppercase;">
                                <?= htmlspecialchars($inv['status']) ?>
                            </span>
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right;">
                            <?php if ($inv['url']): ?>
                                <a href="<?= htmlspecialchars($inv['url']) ?>" target="_blank" class="d-btn" style="padding: 0.25rem 0.75rem; font-size: 0.875rem;">Pay / Download</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php gh_dashboard_footer(); ?>
