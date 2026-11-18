<?php
require_once __DIR__ . '/dashboard-layout.php';

$user = current_user();
$db = gh_db();

// Fetch active project
$stmt = $db->prepare("SELECT * FROM projects WHERE client_id = ? AND status != 'completed' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user['id']]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

gh_dashboard_header(['title' => 'Settings', 'active' => '', 'tab' => 'settings']);
?>

<div class="d-card" style="max-width: 600px;">
    <h1 class="d-title">Account Settings</h1>
    <p style="color: #64748b; margin-bottom: 2rem;">Manage your account preferences and security.</p>

    <div style="margin-bottom: 2rem;">
        <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Email Address</label>
        <input type="email" class="d-input" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background: #f1f5f9; color: #64748b;" />
        <p style="font-size: 0.875rem; color: #64748b;">To change your email, please contact your project manager.</p>
    </div>

    <form action="/api/dashboard-action" method="POST">
        <?= gh_csrf_field() ?>
        <input type="hidden" name="action" value="update_password">
        
        <h2 class="d-title" style="font-size: 1.25rem;">Update Password</h2>
        
        <?php if (isset($_GET['msg'])): ?>
            <div style="padding: 1rem; background: #dcfce7; color: #166534; border-radius: 4px; margin-bottom: 1rem;">
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['err'])): ?>
            <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 4px; margin-bottom: 1rem;">
                <?= htmlspecialchars($_GET['err']) ?>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">New Password</label>
            <input type="password" name="password" class="d-input" required minlength="6" />
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Confirm Password</label>
            <input type="password" name="confirm_password" class="d-input" required minlength="6" />
        </div>
        <button type="submit" class="d-btn">Save Password</button>
    </form>
</div>

<?php
gh_dashboard_footer();
