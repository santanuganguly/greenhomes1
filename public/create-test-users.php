<?php
require_once __DIR__ . '/../src/config.php';
gh_load_env(dirname(__DIR__));

$db = gh_db();

$users = [
    ['Admin User', 'admin@greenhomes.com', '1234', 'admin'],
    ['Super Admin', 'superadmin@greenhomes.com', '1234', 'super_admin'],
    ['Test Client', 'client@greenhomes.com', '1234', 'client'],
    ['Head Engineer', 'pm@greenhomes.com', '1234', 'head_engineer'],
    ['Site Engineer', 'engineer@greenhomes.com', '1234', 'site_engineer'],
];

foreach ($users as [$name, $email, $pw, $role]) {
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE role=VALUES(role)");
    $stmt->execute([$name, $email, $hash, $role]);
    echo "  ✓ $role: $email / $pw\n";
}

// Create a test project
$stmt = $db->prepare("SELECT id FROM users WHERE email='client@greenhomes.com'");
$stmt->execute();
$client = $stmt->fetch();
$clientId = $client['id'];

$stmt = $db->prepare("SELECT id FROM users WHERE email='pm@greenhomes.com'");
$stmt->execute();
$pm = $stmt->fetch();

$stmt = $db->prepare("SELECT id FROM users WHERE email='engineer@greenhomes.com'");
$stmt->execute();
$eng = $stmt->fetch();

$db->exec("INSERT INTO projects (client_id, manager_id, engineer_id, name, location, status, progress_percentage, description) VALUES ($clientId, {$pm['id']}, {$eng['id']}, 'Dream Home - Phase 1', 'Sevoke Road, Siliguri', 'foundation', 25.00, '2BHK residential construction project')");
echo "\n  ✓ Test project created\n";
