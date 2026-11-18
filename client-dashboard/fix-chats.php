<?php
$root = dirname(__DIR__);
if (!file_exists($root . '/src/config.php')) {
    $root = dirname(__DIR__, 2);
}
require_once $root . '/src/config.php';
gh_load_env($root);
require_once $root . '/src/chat.php';

try {
    $db = gh_db();

    // 1. Create tables if they don't exist
    $sql = "
    CREATE TABLE IF NOT EXISTS project_chat_rooms (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      project_id INT UNSIGNED NOT NULL UNIQUE,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS project_chat_members (
      room_id INT UNSIGNED NOT NULL,
      user_id INT UNSIGNED NOT NULL,
      joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (room_id, user_id),
      FOREIGN KEY (room_id) REFERENCES project_chat_rooms(id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS project_chat_messages (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      room_id INT UNSIGNED NOT NULL,
      sender_id INT UNSIGNED NOT NULL,
      message TEXT,
      reply_to_id INT UNSIGNED NULL,
      is_pinned TINYINT(1) NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (room_id) REFERENCES project_chat_rooms(id) ON DELETE CASCADE,
      FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (reply_to_id) REFERENCES project_chat_messages(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS project_chat_files (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      message_id INT UNSIGNED DEFAULT NULL,
      room_id INT UNSIGNED NOT NULL,
      sender_id INT UNSIGNED NOT NULL,
      file_name VARCHAR(255) NOT NULL,
      file_path VARCHAR(500) NOT NULL,
      file_type VARCHAR(100) NOT NULL,
      file_size INT UNSIGNED NOT NULL DEFAULT 0,
      uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (message_id) REFERENCES project_chat_messages(id) ON DELETE CASCADE,
      FOREIGN KEY (room_id) REFERENCES project_chat_rooms(id) ON DELETE CASCADE,
      FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS message_read_status (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      message_id INT UNSIGNED NOT NULL,
      user_id INT UNSIGNED NOT NULL,
      read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY unique_read (message_id, user_id),
      FOREIGN KEY (message_id) REFERENCES project_chat_messages(id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ";
    $db->exec($sql);
    echo "Tables created.\n";

    // 2. Ensure chat rooms exist for all projects
    $projects = $db->query("SELECT id FROM projects")->fetchAll(PDO::FETCH_COLUMN);
    $count = 0;
    foreach ($projects as $pid) {
        chat_ensure_room((int)$pid);
        $count++;
    }
    echo "Chat rooms ensured for $count projects.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
