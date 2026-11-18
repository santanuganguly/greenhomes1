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
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once $root . '/src/csrf.php';
    csrf_verify($_POST['_csrf'] ?? null);

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$password) {
        $error = 'Name, email, and password are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'client')");
            $stmt->execute([$name, $email, $phone ?: null, $hash]);
            $success = 'Account created! You can now log in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign Up — Green Homes Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/portal.css?v=mobile9">
</head>
<body class="login-page">
<div class="login-box">
    <h1>Create Account</h1>
    <p class="sub">Join the Green Homes Client Portal</p>
    <?php if ($error): ?><div class="alert alert-red"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-green"><?= htmlspecialchars($success) ?></div>
    <p style="text-align:center;"><a href="/login" class="btn" style="justify-content:center;">Log In</a></p>
    <?php else: ?>
    <form method="post">
        <?php require_once $root . '/src/csrf.php'; echo csrf_field(); ?>
        <label>Full Name</label>
        <input type="text" name="name" required>
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Phone (optional)</label>
        <input type="tel" name="phone">
        <label>Password</label>
        <input type="password" name="password" minlength="6" required>
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" minlength="6" required>
        <button type="submit" class="btn" style="width:100%;justify-content:center;margin-top:0.5rem;">Create Account</button>
    </form>
    <p style="text-align:center;margin-top:1rem;font-size:0.85rem;color:var(--ink-muted);">
        Already have an account? <a href="/login" style="color:var(--green-800);font-weight:600;">Sign In</a>
    </p>
    <?php endif; ?>
</div>
</body>
</html>
