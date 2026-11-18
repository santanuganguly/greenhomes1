<?php
$pdo = new PDO('sqlite:database/database.sqlite');
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
$result = [];
foreach ($tables as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    $result[$table] = $count;
}
echo json_encode($result, JSON_PRETTY_PRINT);
