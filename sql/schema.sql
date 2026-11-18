-- Green Homes — schema + seed admin user.
-- Import with:  mysql -u root -p < sql/schema.sql

CREATE DATABASE IF NOT EXISTS green_homes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE green_homes;

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  phone         VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  fcm_token     VARCHAR(255) DEFAULT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quote_requests (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NULL,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL,
  phone         VARCHAR(20) NOT NULL,
  city          VARCHAR(80) NOT NULL,
  service_type  ENUM('home','renovation','architecture','commercial') NOT NULL,
  plot_size     INT UNSIGNED,
  bhk_type      ENUM('1BHK','2BHK','3BHK','4BHK+','villa','custom'),
  budget_band   ENUM('<25L','25-50L','50-75L','75L-1Cr','1Cr+'),
  package       ENUM('essential','comfort','elegance','signature') NULL,
  estimate_low  INT UNSIGNED,
  estimate_high INT UNSIGNED,
  notes         TEXT,
  status        ENUM('new','contacted','won','lost') NOT NULL DEFAULT 'new',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_status (status),
  INDEX idx_created (created_at),
  CONSTRAINT fk_quote_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  email       VARCHAR(190) NOT NULL,
  phone       VARCHAR(20),
  subject     VARCHAR(160),
  body        TEXT NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Optional: create your first admin. Run AFTER signing up the admin through the
-- /signup form, then promote them with:
--
--   UPDATE users SET role = 'admin' WHERE email = 'you@example.com';
--
-- (Keeping the admin out of SQL means the password hash is something you
-- generated, not something pasted in. Avoid stale default credentials.)

-- ==========================================
-- DASHBOARD ENTITIES
-- ==========================================

CREATE TABLE IF NOT EXISTS projects (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id     INT UNSIGNED NOT NULL,
  name          VARCHAR(255) NOT NULL,
  location      VARCHAR(255) NOT NULL,
  status        ENUM('planning','foundation','framing','exterior','interior','finishing','completed') NOT NULL DEFAULT 'planning',
  start_date    DATE,
  expected_completion_date DATE,
  budget        DECIMAL(12,2),
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS milestones (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  title         VARCHAR(255) NOT NULL,
  description   TEXT,
  status        ENUM('upcoming','in_progress','completed') NOT NULL DEFAULT 'upcoming',
  order_index   INT NOT NULL DEFAULT 0,
  expected_date DATE,
  completed_date DATE,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS photos (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  title         VARCHAR(255) NOT NULL,
  url           VARCHAR(500) NOT NULL,
  category      VARCHAR(100),
  uploaded_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS documents (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  title         VARCHAR(255) NOT NULL,
  url           VARCHAR(500) NOT NULL,
  type          VARCHAR(100) NOT NULL,
  uploaded_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoices (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  title         VARCHAR(255) NOT NULL,
  amount        DECIMAL(12,2) NOT NULL,
  status        ENUM('pending','paid','overdue') NOT NULL DEFAULT 'pending',
  due_date      DATE NOT NULL,
  paid_date     DATE,
  url           VARCHAR(500),
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dashboard_tokens (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL UNIQUE,
  token         VARCHAR(64) NOT NULL,
  expires_at    DATETIME NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS suggestions (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  title         VARCHAR(255) NOT NULL,
  description   TEXT NOT NULL,
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  admin_response TEXT,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Portal tables (migrated from migration-portal.sql)
CREATE TABLE IF NOT EXISTS project_stages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL,
  status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending', progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0, completed_date DATE, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS weekly_updates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, week_number INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL, work_completed TEXT, remarks TEXT, percentage_increased DECIMAL(5,2) DEFAULT 0,
  progress_after DECIMAL(5,2) DEFAULT 0, status ENUM('draft','pending_approval','approved','published') NOT NULL DEFAULT 'draft',
  created_by INT UNSIGNED, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, published_at DATETIME,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE, FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS photo_albums (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL, description TEXT, sort_order INT DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS before_after (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, stage_name VARCHAR(120) NOT NULL, before_url VARCHAR(500) NOT NULL, after_url VARCHAR(500) NOT NULL, description TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS upcoming_works (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, title VARCHAR(255) NOT NULL, description TEXT, week_commencing DATE, status ENUM('planned','in_progress','completed') NOT NULL DEFAULT 'planned', sort_order INT DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payment_history (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, invoice_id INT UNSIGNED NOT NULL, amount DECIMAL(12,2) NOT NULL, payment_date DATE NOT NULL, payment_method VARCHAR(60), reference_no VARCHAR(100), receipt_url VARCHAR(500), notes TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS site_expenses (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, category VARCHAR(60) NOT NULL, item VARCHAR(120) NOT NULL, quantity DECIMAL(10,2), unit VARCHAR(30), rate DECIMAL(10,2), amount DECIMAL(12,2) NOT NULL, expense_date DATE NOT NULL, vendor VARCHAR(120), receipt_url VARCHAR(500), notes TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS material_tracking (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, material_name VARCHAR(120) NOT NULL, quantity_ordered DECIMAL(10,2) DEFAULT 0, quantity_delivered DECIMAL(10,2) DEFAULT 0, unit VARCHAR(30) NOT NULL DEFAULT 'kg', status ENUM('pending','partial','received') NOT NULL DEFAULT 'pending', expected_date DATE, received_date DATE, notes TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS engineer_notes (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, note_date DATE NOT NULL, content TEXT NOT NULL, created_by INT UNSIGNED, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE, FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS project_issues (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, title VARCHAR(255) NOT NULL, description TEXT, impact TEXT, expected_delay VARCHAR(100), status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open', severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium', reported_by INT UNSIGNED, resolved_by INT UNSIGNED, resolution TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, resolved_at DATETIME, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE, FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL, FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS client_approvals (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, title VARCHAR(255) NOT NULL, description TEXT, options_json TEXT, deadline DATE NOT NULL, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', client_comment TEXT, decided_at DATETIME, created_by INT UNSIGNED, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE, FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, project_id INT UNSIGNED, type VARCHAR(60) NOT NULL, title VARCHAR(255) NOT NULL, message TEXT, link VARCHAR(500), is_read TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL, INDEX idx_user_read (user_id, is_read), INDEX idx_created (created_at)) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chat_messages (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, sender_id INT UNSIGNED NOT NULL, message TEXT NOT NULL, attachment_url VARCHAR(500), read_at DATETIME, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE, FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS global_chat_messages (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, sender_id INT UNSIGNED NOT NULL, message TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE, INDEX idx_created (created_at)) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, client_id INT UNSIGNED NOT NULL, title VARCHAR(255), purpose TEXT, appointment_date DATE NOT NULL, appointment_time TIME, status ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending', notes TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE, FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS milestone_feedback (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, milestone_id INT UNSIGNED, client_id INT UNSIGNED NOT NULL, rating TINYINT UNSIGNED NOT NULL, comment TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE, FOREIGN KEY (milestone_id) REFERENCES milestones(id) ON DELETE SET NULL, FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED, action VARCHAR(120) NOT NULL, entity_type VARCHAR(60), entity_id INT UNSIGNED, details TEXT, ip_address VARCHAR(45), created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL, INDEX idx_action (action), INDEX idx_created (created_at)) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS project_chat_rooms (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL UNIQUE, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS project_chat_members (room_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL, joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (room_id, user_id), FOREIGN KEY (room_id) REFERENCES project_chat_rooms(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS project_chat_messages (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, room_id INT UNSIGNED NOT NULL, sender_id INT UNSIGNED NOT NULL, message TEXT NOT NULL, reply_to_id INT UNSIGNED NULL, is_pinned TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (room_id) REFERENCES project_chat_rooms(id) ON DELETE CASCADE, FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (reply_to_id) REFERENCES project_chat_messages(id) ON DELETE SET NULL) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS project_chat_files (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, message_id INT UNSIGNED NOT NULL, room_id INT UNSIGNED NOT NULL, sender_id INT UNSIGNED NOT NULL, file_name VARCHAR(255) NOT NULL, file_path VARCHAR(500) NOT NULL, file_type VARCHAR(100) NOT NULL, file_size INT UNSIGNED NOT NULL DEFAULT 0, uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (message_id) REFERENCES project_chat_messages(id) ON DELETE CASCADE, FOREIGN KEY (room_id) REFERENCES project_chat_rooms(id) ON DELETE CASCADE, FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS message_read_status (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, message_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL, read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY unique_read (message_id, user_id), FOREIGN KEY (message_id) REFERENCES project_chat_messages(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB;
