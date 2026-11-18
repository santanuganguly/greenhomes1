<?php
require_once __DIR__ . '/../../src/config.php';
$db = gh_db();

echo "Projects:\n";
$projects = $db->query("SELECT id, name FROM projects")->fetchAll(PDO::FETCH_ASSOC);
print_r($projects);

echo "\nRooms:\n";
$rooms = $db->query("SELECT * FROM project_chat_rooms")->fetchAll(PDO::FETCH_ASSOC);
print_r($rooms);

echo "\nMembers:\n";
$members = $db->query("SELECT * FROM project_chat_members")->fetchAll(PDO::FETCH_ASSOC);
print_r($members);

echo "\nUsers:\n";
$users = $db->query("SELECT id, name, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
