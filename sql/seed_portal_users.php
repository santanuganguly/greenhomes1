<?php
require_once 'C:/Users/91963/OneDrive/Documents/claudeee/green-homes/src/config.php';
gh_load_env('C:/Users/91963/OneDrive/Documents/claudeee/green-homes');

$users = [
  ['name'=>'Super Admin',   'email'=>'superadmin@thegreen.homes',   'password'=>'1234', 'role'=>'super_admin'],
  ['name'=>'Admin',         'email'=>'admin@thegreen.homes',         'password'=>'1234', 'role'=>'admin'],
  ['name'=>'Client',        'email'=>'client@thegreen.homes',        'password'=>'1234', 'role'=>'client'],
  ['name'=>'Head Engineer', 'email'=>'headengineer@thegreen.homes',  'password'=>'1234', 'role'=>'head_engineer'],
  ['name'=>'Site Engineer', 'email'=>'siteengineer@thegreen.homes',  'password'=>'1234', 'role'=>'site_engineer'],
];

$db = db();
$count = 0;

foreach ($users as $u) {
  $hash = password_hash($u['password'], PASSWORD_DEFAULT);
  $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
  $stmt->execute([$u['email']]);
  $existing = $stmt->fetch();

  if ($existing) {
    $upd = $db->prepare('UPDATE users SET name=?, password_hash=?, role=? WHERE id=?');
    $upd->execute([$u['name'], $hash, $u['role'], (int)$existing['id']]);
    echo "UPDATED  {$u['email']}  (role={$u['role']})\n";
  } else {
    $ins = $db->prepare('INSERT INTO users (name, email, phone, password_hash, role) VALUES (?,?,?,?,?)');
    $ins->execute([$u['name'], $u['email'], '', $hash, $u['role']]);
    echo "CREATED  {$u['email']}  (role={$u['role']}, id={$db->lastInsertId()})\n";
  }
  $count++;
}

echo "Done. {$count} users processed.\n";
