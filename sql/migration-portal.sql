-- Green Homes Portal — Migration
-- Run after schema.sql. Adds all tables for the Client Experience Portal.
-- 
--   mysql -u root -p green_homes < sql/migration-portal.sql

-- ==========================================
-- 1. EXTEND USERS TABLE ROLE
-- ==========================================
ALTER TABLE users MODIFY COLUMN role ENUM('user','admin','super_admin','head_engineer','site_engineer','client') NOT NULL DEFAULT 'client';

-- ==========================================
-- 2. EXTEND PROJECTS TABLE
-- ==========================================
ALTER TABLE projects ADD COLUMN IF NOT EXISTS manager_id INT UNSIGNED NULL AFTER client_id;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS engineer_id INT UNSIGNED NULL AFTER manager_id;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS progress_percentage DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER status;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS description TEXT AFTER location;
ALTER TABLE projects ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE projects ADD FOREIGN KEY IF NOT EXISTS (manager_id) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE projects ADD FOREIGN KEY IF NOT EXISTS (engineer_id) REFERENCES users(id) ON DELETE SET NULL;

-- ==========================================
-- 3. PROJECT STAGES (for the progress timeline)
-- ==========================================
CREATE TABLE IF NOT EXISTS project_stages (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  name          VARCHAR(120) NOT NULL,
  status        ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  progress      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  sort_order    INT NOT NULL DEFAULT 0,
  completed_date DATE,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 4. WEEKLY UPDATES
-- ==========================================
CREATE TABLE IF NOT EXISTS weekly_updates (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id        INT UNSIGNED NOT NULL,
  week_number       INT UNSIGNED NOT NULL,
  title             VARCHAR(255) NOT NULL,
  work_completed    TEXT,
  remarks           TEXT,
  percentage_increased DECIMAL(5,2) DEFAULT 0,
  progress_after    DECIMAL(5,2) DEFAULT 0,
  status            ENUM('draft','pending_approval','approved','published') NOT NULL DEFAULT 'draft',
  created_by        INT UNSIGNED,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  published_at      DATETIME,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS weekly_update_photos (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  weekly_update_id INT UNSIGNED NOT NULL,
  url             VARCHAR(500) NOT NULL,
  caption         VARCHAR(255),
  sort_order      INT DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (weekly_update_id) REFERENCES weekly_updates(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS weekly_update_videos (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  weekly_update_id INT UNSIGNED NOT NULL,
  url             VARCHAR(500) NOT NULL,
  caption         VARCHAR(255),
  duration        VARCHAR(20),
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (weekly_update_id) REFERENCES weekly_updates(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 5. GALLERY / ALBUMS
-- ==========================================
CREATE TABLE IF NOT EXISTS photo_albums (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  name          VARCHAR(120) NOT NULL,
  description   TEXT,
  sort_order    INT DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE photos ADD COLUMN IF NOT EXISTS album_id INT UNSIGNED NULL AFTER project_id;
ALTER TABLE photos ADD COLUMN IF NOT EXISTS is_video TINYINT(1) NOT NULL DEFAULT 0 AFTER url;
ALTER TABLE photos ADD COLUMN IF NOT EXISTS video_url VARCHAR(500) AFTER url;
ALTER TABLE photos ADD FOREIGN KEY IF NOT EXISTS (album_id) REFERENCES photo_albums(id) ON DELETE SET NULL;

-- ==========================================
-- 6. BEFORE / AFTER
-- ==========================================
CREATE TABLE IF NOT EXISTS before_after (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  stage_name    VARCHAR(120) NOT NULL,
  before_url    VARCHAR(500) NOT NULL,
  after_url     VARCHAR(500) NOT NULL,
  description   TEXT,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 7. UPCOMING WORK
-- ==========================================
CREATE TABLE IF NOT EXISTS upcoming_works (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  title         VARCHAR(255) NOT NULL,
  description   TEXT,
  week_commencing DATE,
  status        ENUM('planned','in_progress','completed') NOT NULL DEFAULT 'planned',
  sort_order    INT DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 8. PAYMENTS / INVOICES (extend existing)
-- ==========================================
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS invoice_no VARCHAR(50) AFTER id;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS description TEXT AFTER title;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS receipt_url VARCHAR(500) AFTER url;

-- Payment history
CREATE TABLE IF NOT EXISTS payment_history (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id    INT UNSIGNED NOT NULL,
  amount        DECIMAL(12,2) NOT NULL,
  payment_date  DATE NOT NULL,
  payment_method VARCHAR(60),
  reference_no  VARCHAR(100),
  receipt_url   VARCHAR(500),
  notes         TEXT,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 9. SITE EXPENSES
-- ==========================================
CREATE TABLE IF NOT EXISTS site_expenses (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  category      VARCHAR(60) NOT NULL,
  item          VARCHAR(120) NOT NULL,
  quantity      DECIMAL(10,2),
  unit          VARCHAR(30),
  rate          DECIMAL(10,2),
  amount        DECIMAL(12,2) NOT NULL,
  expense_date  DATE NOT NULL,
  vendor        VARCHAR(120),
  receipt_url   VARCHAR(500),
  notes         TEXT,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 10. MATERIAL TRACKING
-- ==========================================
CREATE TABLE IF NOT EXISTS material_tracking (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  material_name VARCHAR(120) NOT NULL,
  quantity_ordered DECIMAL(10,2) DEFAULT 0,
  quantity_delivered DECIMAL(10,2) DEFAULT 0,
  unit          VARCHAR(30) NOT NULL DEFAULT 'kg',
  status        ENUM('pending','partial','received') NOT NULL DEFAULT 'pending',
  expected_date DATE,
  received_date DATE,
  notes         TEXT,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 11. ENGINEER NOTES (daily)
-- ==========================================
CREATE TABLE IF NOT EXISTS engineer_notes (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  note_date     DATE NOT NULL,
  content       TEXT NOT NULL,
  created_by    INT UNSIGNED,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ==========================================
-- 12. ISSUE TRACKER
-- ==========================================
CREATE TABLE IF NOT EXISTS project_issues (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  title         VARCHAR(255) NOT NULL,
  description   TEXT,
  impact        TEXT,
  expected_delay VARCHAR(100),
  status        ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  severity      ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  reported_by   INT UNSIGNED,
  resolved_by   INT UNSIGNED,
  resolution    TEXT,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolved_at   DATETIME,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ==========================================
-- 13. CLIENT APPROVALS
-- ==========================================
CREATE TABLE IF NOT EXISTS client_approvals (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  title         VARCHAR(255) NOT NULL,
  description   TEXT,
  options_json  TEXT,
  deadline      DATE NOT NULL,
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  client_comment TEXT,
  decided_at    DATETIME,
  created_by    INT UNSIGNED,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ==========================================
-- 14. NOTIFICATIONS
-- ==========================================
CREATE TABLE IF NOT EXISTS notifications (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  project_id    INT UNSIGNED,
  type          VARCHAR(60) NOT NULL,
  title         VARCHAR(255) NOT NULL,
  message       TEXT,
  link          VARCHAR(500),
  is_read       TINYINT(1) NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  INDEX idx_user_read (user_id, is_read),
  INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ==========================================
-- 15. CHAT / MESSAGES
-- ==========================================
CREATE TABLE IF NOT EXISTS chat_messages (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  sender_id     INT UNSIGNED NOT NULL,
  message       TEXT NOT NULL,
  attachment_url VARCHAR(500),
  read_at       DATETIME,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 15.5 GLOBAL CHAT
-- ==========================================
CREATE TABLE IF NOT EXISTS global_chat_messages (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_id     INT UNSIGNED NOT NULL,
  message       TEXT NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ==========================================
-- 16. APPOINTMENTS
-- ==========================================
CREATE TABLE IF NOT EXISTS appointments (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  client_id     INT UNSIGNED NOT NULL,
  title         VARCHAR(255),
  purpose       TEXT,
  appointment_date DATE NOT NULL,
  appointment_time TIME,
  status        ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  notes         TEXT,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 17. FEEDBACK
-- ==========================================
CREATE TABLE IF NOT EXISTS milestone_feedback (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id    INT UNSIGNED NOT NULL,
  milestone_id  INT UNSIGNED,
  client_id     INT UNSIGNED NOT NULL,
  rating        TINYINT UNSIGNED NOT NULL,
  comment       TEXT,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (milestone_id) REFERENCES milestones(id) ON DELETE SET NULL,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 18. AUDIT LOGS
-- ==========================================
CREATE TABLE IF NOT EXISTS audit_logs (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED,
  action        VARCHAR(120) NOT NULL,
  entity_type   VARCHAR(60),
  entity_id     INT UNSIGNED,
  details       TEXT,
  ip_address    VARCHAR(45),
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_action (action),
  INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ==========================================
-- 19. PROJECT CHAT ROOMS
-- ==========================================
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
  message TEXT NOT NULL,
  reply_to_id INT UNSIGNED NULL,
  is_pinned TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES project_chat_rooms(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reply_to_id) REFERENCES project_chat_messages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS project_chat_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  message_id INT UNSIGNED NOT NULL,
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
