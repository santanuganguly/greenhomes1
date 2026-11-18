<?php
/**
 * Production Migration Script
 * 
 * Visit: https://thegreen.homes/client-dashboard/migrate.php
 * 
 * This runs the database migration for the client portal.
 * DELETE THIS FILE after running it on production.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
if (!file_exists($root . '/src/config.php')) { $root = dirname(__DIR__, 2); }
require_once $root . '/src/config.php';
gh_load_env($root);

header('Content-Type: text/plain');

echo "=== Green Homes Portal Migration ===\n\n";

try {
    $db = gh_db();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    function columnExists(PDO $db, string $table, string $column): bool {
        $stmt = $db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table' AND COLUMN_NAME='$column'");
        return (int)$stmt->fetchColumn() > 0;
    }

    function tableExists(PDO $db, string $table): bool {
        $stmt = $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table'");
        return (int)$stmt->fetchColumn() > 0;
    }

    // 1. Extend users table role ENUM
    $db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user','admin','super_admin','head_engineer','site_engineer','client') NOT NULL DEFAULT 'client'");
    echo "✓ users.role extended\n";

    // 2. Extend projects table
    $projCols = [
        ['manager_id', 'INT UNSIGNED NULL AFTER client_id'],
        ['engineer_id', 'INT UNSIGNED NULL AFTER manager_id'],
        ['progress_percentage', 'DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER status'],
        ['description', 'TEXT AFTER location'],
        ['updated_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
    ];
    foreach ($projCols as [$col, $def]) {
        if (!columnExists($db, 'projects', $col)) {
            $db->exec("ALTER TABLE projects ADD COLUMN `$col` $def");
            echo "  ✓ projects.$col added\n";
        }
    }

    // Foreign keys
    $fkCheck = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='projects' AND CONSTRAINT_TYPE='FOREIGN KEY'")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('projects_ibfk_2', $fkCheck, true) && columnExists($db, 'projects', 'manager_id')) {
        $db->exec("ALTER TABLE projects ADD CONSTRAINT projects_ibfk_2 FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "  ✓ projects.manager_id FK added\n";
    }
    if (!in_array('projects_ibfk_3', $fkCheck, true) && columnExists($db, 'projects', 'engineer_id')) {
        $db->exec("ALTER TABLE projects ADD CONSTRAINT projects_ibfk_3 FOREIGN KEY (engineer_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "  ✓ projects.engineer_id FK added\n";
    }

    // 3-18: CREATE TABLE IF NOT EXISTS
    $tables = [
        "CREATE TABLE IF NOT EXISTS project_stages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            name VARCHAR(120) NOT NULL,
            status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
            progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            completed_date DATE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS weekly_updates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            week_number INT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            work_completed TEXT,
            remarks TEXT,
            percentage_increased DECIMAL(5,2) DEFAULT 0,
            progress_after DECIMAL(5,2) DEFAULT 0,
            status ENUM('draft','pending_approval','approved','published') NOT NULL DEFAULT 'draft',
            created_by INT UNSIGNED,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            published_at DATETIME,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS weekly_update_photos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            weekly_update_id INT UNSIGNED NOT NULL,
            url VARCHAR(500) NOT NULL,
            caption VARCHAR(255),
            sort_order INT DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (weekly_update_id) REFERENCES weekly_updates(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS weekly_update_videos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            weekly_update_id INT UNSIGNED NOT NULL,
            url VARCHAR(500) NOT NULL,
            caption VARCHAR(255),
            duration VARCHAR(20),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (weekly_update_id) REFERENCES weekly_updates(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS photo_albums (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            name VARCHAR(120) NOT NULL,
            description TEXT,
            sort_order INT DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS before_after (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            stage_name VARCHAR(120) NOT NULL,
            before_url VARCHAR(500) NOT NULL,
            after_url VARCHAR(500) NOT NULL,
            description TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS upcoming_works (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            week_commencing DATE,
            status ENUM('planned','in_progress','completed') NOT NULL DEFAULT 'planned',
            sort_order INT DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS payment_history (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            payment_date DATE NOT NULL,
            payment_method VARCHAR(60),
            reference_no VARCHAR(100),
            receipt_url VARCHAR(500),
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS site_expenses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            category VARCHAR(60) NOT NULL,
            item VARCHAR(120) NOT NULL,
            quantity DECIMAL(10,2),
            unit VARCHAR(30),
            rate DECIMAL(10,2),
            amount DECIMAL(12,2) NOT NULL,
            expense_date DATE NOT NULL,
            vendor VARCHAR(120),
            receipt_url VARCHAR(500),
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS material_tracking (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            material_name VARCHAR(120) NOT NULL,
            quantity_ordered DECIMAL(10,2) DEFAULT 0,
            quantity_delivered DECIMAL(10,2) DEFAULT 0,
            unit VARCHAR(30) NOT NULL DEFAULT 'kg',
            status ENUM('pending','partial','received') NOT NULL DEFAULT 'pending',
            expected_date DATE,
            received_date DATE,
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS engineer_notes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            note_date DATE NOT NULL,
            content TEXT NOT NULL,
            created_by INT UNSIGNED,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS project_issues (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            impact TEXT,
            expected_delay VARCHAR(100),
            status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
            severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
            reported_by INT UNSIGNED,
            resolved_by INT UNSIGNED,
            resolution TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS client_approvals (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            options_json TEXT,
            deadline DATE NOT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            client_comment TEXT,
            decided_at DATETIME,
            created_by INT UNSIGNED,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            project_id INT UNSIGNED,
            type VARCHAR(60) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            link VARCHAR(500),
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
            INDEX idx_user_read (user_id, is_read),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS chat_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            sender_id INT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            attachment_url VARCHAR(500),
            read_at DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS global_chat_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sender_id INT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS project_chat_rooms (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS project_chat_members (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            room_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_member (room_id, user_id),
            FOREIGN KEY (room_id) REFERENCES project_chat_rooms(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS project_chat_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            room_id INT UNSIGNED NOT NULL,
            sender_id INT UNSIGNED NOT NULL,
            message TEXT,
            reply_to_id INT UNSIGNED DEFAULT NULL,
            is_pinned TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (room_id) REFERENCES project_chat_rooms(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reply_to_id) REFERENCES project_chat_messages(id) ON DELETE SET NULL,
            INDEX idx_room_created (room_id, created_at)
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS project_chat_files (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            message_id INT UNSIGNED DEFAULT NULL,
            room_id INT UNSIGNED NOT NULL,
            sender_id INT UNSIGNED NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type VARCHAR(100) NOT NULL,
            file_size INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (message_id) REFERENCES project_chat_messages(id) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES project_chat_rooms(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS message_read_status (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            message_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_read (message_id, user_id),
            FOREIGN KEY (message_id) REFERENCES project_chat_messages(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS appointments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            client_id INT UNSIGNED NOT NULL,
            title VARCHAR(255),
            purpose TEXT,
            appointment_date DATE NOT NULL,
            appointment_time TIME,
            status ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS milestone_feedback (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            milestone_id INT UNSIGNED,
            client_id INT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            comment TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (milestone_id) REFERENCES milestones(id) ON DELETE SET NULL,
            FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",

        "CREATE TABLE IF NOT EXISTS audit_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED,
            action VARCHAR(120) NOT NULL,
            entity_type VARCHAR(60),
            entity_id INT UNSIGNED,
            details TEXT,
            ip_address VARCHAR(45),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_action (action),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB",
    ];

    foreach ($tables as $sql) {
        $db->exec($sql);
    }
    echo "✓ All portal tables created\n";

    // Extend photos table
    $photoCols = [
        ['album_id', 'INT UNSIGNED NULL AFTER project_id'],
        ['is_video', "TINYINT(1) NOT NULL DEFAULT 0 AFTER url"],
        ['video_url', 'VARCHAR(500) AFTER url'],
    ];
    foreach ($photoCols as [$col, $def]) {
        if (tableExists($db, 'photos') && !columnExists($db, 'photos', $col)) {
            $db->exec("ALTER TABLE photos ADD COLUMN `$col` $def");
            echo "  ✓ photos.$col added\n";
        }
    }

    // Extend invoices table
    $invCols = [
        ['invoice_no', 'VARCHAR(50) AFTER id'],
        ['description', 'TEXT AFTER title'],
        ['receipt_url', 'VARCHAR(500) AFTER url'],
    ];
    foreach ($invCols as [$col, $def]) {
        if (tableExists($db, 'invoices') && !columnExists($db, 'invoices', $col)) {
            $db->exec("ALTER TABLE invoices ADD COLUMN `$col` $def");
            echo "  ✓ invoices.$col added\n";
        }
    }

    // Extend projects.status ENUM
    $db->exec("ALTER TABLE projects MODIFY COLUMN status ENUM('planning','not_started','in_progress','on_hold','foundation','framing','exterior','interior','finishing','completed') NOT NULL DEFAULT 'planning'");
    echo "  ✓ projects.status ENUM extended\n";

    // Ensure chat rooms exist for all projects
    require_once $root . '/src/chat.php';
    $projects = $db->query("SELECT id FROM projects")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($projects as $pid) {
        chat_ensure_room((int)$pid);
    }
    echo "  ✓ Chat rooms ensured for all existing projects\n";

    echo "\n✅ Migration complete!\n";
    echo "\n⚠️  DELETE this file after running: client-dashboard/migrate.php\n";

} catch (Throwable $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
