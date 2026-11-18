<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('admin', 'super_admin');
$db = db();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: clients.php');
    exit;
}

$success = $error = '';
$isSuper = $user['role'] === 'super_admin';

// Process password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $new_pass = $_POST['new_password'] ?? '';
    
    // Check permission
    $target = $db->prepare("SELECT role FROM users WHERE id = ?");
    $target->execute([$id]);
    $targetRole = $target->fetchColumn();
    
    if (!$targetRole) {
        $error = 'User not found.';
    } elseif ($targetRole === 'super_admin' && !$isSuper) {
        $error = 'You do not have permission to change a super admin\'s password.';
    } elseif (strlen($new_pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $id]);
        audit_log('password_reset', 'user', $id, "Admin reset password for user ID $id");
        $success = 'Password successfully updated.';
    }
}

// Fetch User
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    header('Location: clients.php');
    exit;
}

// Fetch Associated Projects
$projects = $db->prepare(
    "SELECT p.*, u.name as client_name, m.name as manager_name,
            (SELECT w.published_at FROM weekly_updates w WHERE w.project_id = p.id AND w.status = 'published' ORDER BY w.published_at DESC LIMIT 1) as last_update_date
     FROM projects p
     LEFT JOIN users u ON p.client_id = u.id
     LEFT JOIN users m ON p.manager_id = m.id
     WHERE p.client_id = ? OR p.manager_id = ? OR p.engineer_id = ?
     ORDER BY p.created_at DESC"
);
$projects->execute([$id, $id, $id]);
$assocProjects = $projects->fetchAll();

$initials = mb_strtoupper(mb_substr($targetUser['name'], 0, 2));
$roleColor = $targetUser['role'] === 'admin' || $targetUser['role'] === 'super_admin' ? 'red' : ($targetUser['role'] === 'head_engineer' ? 'blue' : ($targetUser['role'] === 'site_engineer' ? 'yellow' : 'gray'));

portal_header(['title' => 'User Details - ' . $targetUser['name'], 'active' => 'clients', 'tab' => 'admin-clients']);
?>

<div class="p-header-row">
    <div class="p-header-title">
        <h1>User Details</h1>
        <p>Viewing profile for <?= htmlspecialchars($targetUser['name']) ?></p>
    </div>
    <div class="p-header-actions">
        <a href="clients.php" class="btn btn-secondary">Back to Clients</a>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-green"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-red"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="grid grid-cols-layout">
    <!-- Left Column: Profile Card & Password Reset -->
    <div>
        <div class="card" style="text-align:center; padding:2rem 1.5rem;">
            <div style="width:80px; height:80px; border-radius:50%; background:var(--gh-primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:bold; margin:0 auto 1rem auto;">
                <?= $initials ?>
            </div>
            <h2 style="margin:0 0 0.5rem 0; font-size:1.4rem; color:#1e293b;"><?= htmlspecialchars($targetUser['name']) ?></h2>
            <p style="margin:0 0 1rem 0; color:#64748b;"><?= htmlspecialchars($targetUser['email']) ?></p>
            <span class="pill pill-<?= $roleColor ?>" style="font-size:0.9rem; padding:4px 12px;"><?= get_role_label($targetUser['role']) ?></span>
            
            <div style="margin-top:2rem; text-align:left; border-top:1px solid #f1f5f9; padding-top:1.5rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom:0.75rem;">
                    <span style="color:#64748b; font-size:0.9rem;">Phone</span>
                    <strong style="color:#1e293b;"><?= htmlspecialchars($targetUser['phone'] ?: '—') ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:0.75rem;">
                    <span style="color:#64748b; font-size:0.9rem;">Joined</span>
                    <strong style="color:#1e293b;"><?= date('F j, Y', strtotime($targetUser['created_at'])) ?></strong>
                </div>
            </div>
        </div>
        
        <?php 
        $canChangePass = $isSuper || !in_array($targetUser['role'], ['super_admin', 'admin']);
        if ($canChangePass): 
        ?>
        <div class="card" style="margin-top:1.5rem;">
            <div class="card-title">Change Password</div>
            <form method="post">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required minlength="6" class="form-control" placeholder="Enter new password">
                </div>
                <button type="submit" class="btn" style="width:100%;">Update Password</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Right Column: Associated Projects -->
    <div class="card">
        <div class="card-title">Associated Projects (<?= count($assocProjects) ?>)</div>
        <div class="gh-list">
            <?php if (empty($assocProjects)): ?>
                <div class="empty">This user is not assigned to any projects.</div>
            <?php else: ?>
                <?php foreach ($assocProjects as $p): 
                    $progress = (int)($p['progress_percentage'] ?? 0);
                    $initials = mb_strtoupper(mb_substr($p['name'], 0, 2));
                    
                    $statusColor = 'pill-blue';
                    if ($p['status'] === 'completed') $statusColor = 'pill-green';
                    if ($p['status'] === 'on_hold') $statusColor = 'pill-yellow';
                ?>
                <a href="/admin/projects?id=<?= $p['id'] ?>" class="gh-list-item">
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
</div>

<?php portal_footer(); ?>
