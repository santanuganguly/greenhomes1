<?php
require_once __DIR__ . '/dashboard-layout.php';

$user = current_user();
if (($user['role'] ?? '') !== 'admin') {
    gh_404();
    exit;
}

$db = gh_db();

$stmt = $db->query("
    SELECT p.*, u.name as client_name, u.email as client_email 
    FROM projects p 
    JOIN users u ON p.client_id = u.id 
    ORDER BY p.created_at DESC
");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

gh_dashboard_header(['title' => 'Manage Projects', 'active' => '', 'tab' => 'admin-projects']);
?>

<div class="d-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 class="d-title" style="margin-bottom: 0;">Manage Projects</h1>
            <p style="color: #64748b;">View and manage all active client construction projects.</p>
        </div>
        <a href="/admin/projects/new" class="d-btn">Create New Project</a>
    </div>

    <?php if (empty($projects)): ?>
        <div style="text-align: center; padding: 3rem 0; color: #64748b; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1;">
            <p>No projects have been created yet.</p>
        </div>
    <?php else: ?>
        <table style="width: 100%; text-align: left; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 0.875rem;">
                    <th style="padding: 1rem 0.5rem; font-weight: 500;">Project Name</th>
                    <th style="padding: 1rem 0.5rem; font-weight: 500;">Client</th>
                    <th style="padding: 1rem 0.5rem; font-weight: 500;">Location</th>
                    <th style="padding: 1rem 0.5rem; font-weight: 500;">Status</th>
                    <th style="padding: 1rem 0.5rem; font-weight: 500;">Start Date</th>
                    <th style="padding: 1rem 0.5rem; font-weight: 500; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $p): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 1rem 0.5rem; font-weight: 500; color: #0f172a;">
                            <a href="/admin/projects/detail?id=<?= (int) $p['id'] ?>" style="color: var(--gh-green-700); text-decoration: none; font-weight: 600;">
                                <?= htmlspecialchars($p['name']) ?>
                            </a>
                        </td>
                        <td style="padding: 1rem 0.5rem;">
                            <?= htmlspecialchars($p['client_name']) ?><br>
                            <span style="font-size: 0.75rem; color: #64748b;"><?= htmlspecialchars($p['client_email']) ?></span>
                        </td>
                        <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($p['location']) ?></td>
                        <td style="padding: 1rem 0.5rem;">
                            <span style="font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 999px; background: #f1f5f9; color: #475569; font-weight: 600; text-transform: uppercase;">
                                <?= htmlspecialchars(str_replace('_', ' ', $p['status'])) ?>
                            </span>
                        </td>
                        <td style="padding: 1rem 0.5rem; color: #64748b;"><?= $p['start_date'] ? date('M j, Y', strtotime($p['start_date'])) : '—' ?></td>
                        <td style="padding: 1rem 0.5rem;">
                            <a href="/admin/projects/detail?id=<?= (int) $p['id'] ?>" class="d-btn" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;">Manage</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php gh_dashboard_footer(); ?>
