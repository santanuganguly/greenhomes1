<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
portal_header(['title' => 'Settings', 'tab' => 'settings']);
?>

<div class="p-content">
    <h1 class="p-title">Account Settings</h1>

    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-title">Your Information</div>
        <div class="grid grid-2">
            <div>
                <div style="font-size:.8rem;color:#888;">Name</div>
                <div style="font-weight:600;"><?= htmlspecialchars($user['name']) ?></div>
            </div>
            <div>
                <div style="font-size:.8rem;color:#888;">Email</div>
                <div style="font-weight:600;"><?= htmlspecialchars($user['email']) ?></div>
            </div>
            <div>
                <div style="font-size:.8rem;color:#888;">Phone</div>
                <div style="font-weight:600;"><?= htmlspecialchars($user['phone'] ?? '—') ?></div>
            </div>
            <div>
                <div style="font-size:.8rem;color:#888;">Role</div>
                <div style="font-weight:600;"><?= htmlspecialchars(ucfirst($user['role'])) ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Change Password</div>
        <form method="POST" action="/api/portal" style="max-width:400px;">
            <input type="hidden" name="action" value="change_password">
            <div style="margin-bottom:.75rem;">
                <label style="font-size:.85rem;font-weight:600;">Current Password</label>
                <input type="password" name="current_password" required class="input" style="width:100%;margin-top:.25rem;">
            </div>
            <div style="margin-bottom:.75rem;">
                <label style="font-size:.85rem;font-weight:600;">New Password</label>
                <input type="password" name="new_password" required minlength="8" class="input" style="width:100%;margin-top:.25rem;">
            </div>
            <div style="margin-bottom:.75rem;">
                <label style="font-size:.85rem;font-weight:600;">Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="8" class="input" style="width:100%;margin-top:.25rem;">
            </div>
            <button type="submit" class="btn">Update Password</button>
        </form>
    </div>
</div>

<?php portal_footer(); ?>
