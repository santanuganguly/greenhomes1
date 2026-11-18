<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
if (!file_exists($root . '/.env')) { $root = dirname(__DIR__, 3); }
if (!file_exists($root . '/.env')) { $root = '/home/u495244335/domains/thegreen.homes/public_html'; }

require_once $root . '/src/config.php';
gh_load_env($root);
require_once $root . '/src/auth.php';
require_once $root . '/src/push.php';

$user = current_user();
if (!$user) {
    echo "<h1>Error: Unauthenticated</h1><p>Please log in to your portal account first.</p>";
    exit;
}

$db = db();
$stmt = $db->prepare("SELECT fcm_token FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$token = $stmt->fetchColumn();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<title>FCM Push Notification Diagnostic Test</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body { font-family: sans-serif; padding: 1.5rem; background: #0f172a; color: #f8fafc; line-height: 1.5; }
.card { background: #1e293b; padding: 1.5rem; border-radius: 12px; border: 1px solid #334155; max-width: 600px; margin: 0 auto; }
.btn { background: #166534; color: white; padding: 0.75rem 1.25rem; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; margin-top: 1rem; border: none; cursor: pointer; font-size: 0.95rem; }
.btn-blue { background: #2563eb; }
.badge-green { background: #166534; color: #dcfce7; padding: 4px 10px; border-radius: 99px; font-size: 0.85rem; font-weight: bold; }
.badge-red { background: #991b1b; color: #fef2f2; padding: 4px 10px; border-radius: 99px; font-size: 0.85rem; font-weight: bold; }
code { background: #0f172a; padding: 2px 6px; border-radius: 4px; font-family: monospace; color: #38bdf8; word-break: break-all; }
</style>
</head>
<body>
<div class="card">
    <h2>📱 Push Notification Diagnostic Test</h2>
    <p><strong>User:</strong> <?= htmlspecialchars($user['name'] . ' (' . $user['email'] . ')') ?></p>
    <p><strong>Role:</strong> <?= htmlspecialchars($user['role']) ?></p>

    <?php if (empty($token)): ?>
        <p><span class="badge-red">No Token Registered</span></p>
        <p>No FCM token is currently registered for your account in the database.</p>
        <p><strong>To fix:</strong> Open the Green Homes Android app on your phone while logged into this account (`<?= htmlspecialchars($user['email']) ?>`). The app will request notification permissions and register your device token automatically.</p>
    <?php else: ?>
        <p><span class="badge-green">✅ FCM Token Registered</span></p>
        <p>Token preview: <code><?= htmlspecialchars(substr((string)$token, 0, 40) . '...' . substr((string)$token, -20)) ?></code></p>

        <?php
        if (isset($_GET['send'])) {
            $sent = send_push_notification((string)$token, "Green Homes Test Push 🚀", "Hello " . $user['name'] . "! Push notifications are working perfectly on your device.", [
                'link' => '/dashboard',
                'type' => 'test_push'
            ]);
            if ($sent) {
                echo "<div style='background:#14532d; padding:1rem; border-radius:8px; margin-top:1rem;'>✅ <strong>SUCCESS!</strong> FCM push notification dispatched to your device! Check your phone's notification bar.</div>";
            } else {
                echo "<div style='background:#7f1d1d; padding:1rem; border-radius:8px; margin-top:1rem;'>❌ <strong>FAILED!</strong> FCM server rejected the notification or access token error. Check server logs.</div>";
            }
        }
        ?>

        <br>
        <a href="?send=1" class="btn">Send Live Test Push Notification Now</a>
    <?php endif; ?>

    <br><br>
    <button type="button" class="btn btn-blue" onclick="triggerTokenRegister()">🔄 Tap to Register / Refresh Mobile Token</button>
</div>

<script>
function triggerTokenRegister() {
    const cap = window.Capacitor || (window.parent && window.parent.Capacitor);
    if (cap && cap.Plugins && cap.Plugins.PushNotifications) {
        const PushNotifications = cap.Plugins.PushNotifications;
        PushNotifications.requestPermissions().then(res => {
            PushNotifications.register();
            alert('Permissions requested! Device token registering now. Refreshing in 3 seconds...');
            setTimeout(() => location.reload(), 3000);
        }).catch(err => alert('Capacitor error: ' + (err.message || err)));
    } else {
        alert('Notice: You are opening this page on a desktop web browser. Please open the Green Homes Android app on your phone!');
    }
}
</script>
</body>
</html>
