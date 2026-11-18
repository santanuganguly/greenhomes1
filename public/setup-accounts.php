<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';
gh_load_env(dirname(__DIR__));

try {
    $db = gh_db();

    // 1. Setup Admin Account
    $admin_email = 'official@greenhillgroup.in';
    $admin_hash = password_hash('1234', PASSWORD_DEFAULT);

    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$admin_email]);
    if (!$stmt->fetch()) {
        $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES ('Admin', ?, ?, 'admin')");
        $stmt->execute([$admin_email, $admin_hash]);
    } else {
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, role = 'admin' WHERE email = ?");
        $stmt->execute([$admin_hash, $admin_email]);
    }

    // 2. Setup Client Account
    $client_email = 'amit@gmail.com';
    $client_hash = password_hash('1234', PASSWORD_DEFAULT);

    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$client_email]);
    if (!$stmt->fetch()) {
        $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES ('Amit', ?, ?, 'user')");
        $stmt->execute([$client_email, $client_hash]);
    } else {
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmt->execute([$client_hash, $client_email]);
    }

    echo "✅ Success! Both accounts have been securely created/updated with the password '1234'. You can now log in!";
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage();
}
