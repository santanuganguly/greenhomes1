<?php
$root = dirname(__DIR__, 2);
if (!file_exists($root . '/src/config.php')) { $root = dirname(__DIR__, 3); }
require_once $root . '/src/config.php';
gh_load_env($root);
require_once $root . '/src/auth.php';
require_once $root . '/src/portal.php';

$user = current_user();
if ($user) {
    header('Location: ' . get_role_dashboard($user['role']));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once $root . '/src/csrf.php';
    csrf_verify($_POST['_csrf'] ?? null);

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $userRow = $stmt->fetch();

    if ($userRow && password_verify($password, $userRow['password_hash'])) {
        login_user((int) $userRow['id']);
        audit_log('login', 'users', (int) $userRow['id']);
        header('Location: ' . get_role_dashboard($userRow['role']));
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — Green Homes Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/portal.css?v=mobile9">
</head>
<body class="login-page">
<div class="login-box">
    <div style="text-align: center; margin-bottom: 0.5rem;">
        <img src="/assets/img/logo-new.png" alt="Green Homes Logo" style="height: 60px; width: auto; display: inline-block;">
    </div>
    <h1 style="display:none;">Green Homes</h1>
    <p class="sub">Client Experience Portal</p>
    <?php if ($error): ?><div class="alert alert-red"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($msg = $_GET['msg'] ?? null): ?><div class="alert alert-green"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post">
        <?php require_once $root . '/src/csrf.php'; echo csrf_field(); ?>
        <label>Email</label>
        <input type="email" name="email" required autofocus>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit" class="btn" style="width:100%;justify-content:center;margin-top:0.5rem;">Sign In</button>
    </form>
    <p style="text-align:center;margin-top:1rem;font-size:0.85rem;color:var(--ink-muted);">
        Don't have an account? <a href="/signup" style="color:var(--green-800);font-weight:600;">Sign Up</a>
    </p>
    <p style="text-align:center;margin-top:0.5rem;font-size:0.8rem;color:var(--ink-light);">
        <a href="https://thegreen.homes" style="color:var(--ink-light);">← Back to main website</a>
    </p>
</div>
</body>
</html>
