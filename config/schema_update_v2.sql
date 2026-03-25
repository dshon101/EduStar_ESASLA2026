-- ================================================================
-- EduStar v2 Schema Update
-- Run this once in your InfinityFree phpMyAdmin
-- ================================================================

-- ── DEVICE SESSIONS (for new-device login detection) ────────────
CREATE TABLE IF NOT EXISTS device_sessions (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  device_hash   VARCHAR(64)  NOT NULL,
  user_agent    VARCHAR(255) DEFAULT NULL,
  first_seen    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_device (user_id, device_hash),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── SYSTEM LOGS ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS system_logs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_id    INT UNSIGNED DEFAULT NULL,
  actor_name  VARCHAR(120) DEFAULT NULL,
  action      VARCHAR(80)  NOT NULL,
  target_type VARCHAR(40)  DEFAULT NULL,
  target_id   VARCHAR(40)  DEFAULT NULL,
  detail      TEXT         DEFAULT NULL,
  ip          VARCHAR(45)  DEFAULT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_actor (actor_id),
  INDEX idx_action (action),
  INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ── SUPPORT TICKETS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS support_tickets (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED DEFAULT NULL,
  name        VARCHAR(120) NOT NULL,
  email       VARCHAR(180) NOT NULL,
  subject     VARCHAR(220) NOT NULL,
  category    VARCHAR(60)  NOT NULL DEFAULT 'general',
  message     TEXT         NOT NULL,
  status      ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  admin_reply TEXT         DEFAULT NULL,
  replied_at  DATETIME     DEFAULT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user (user_id),
  INDEX idx_status (status)
) ENGINE=InnoDB;

-- ── COMMUNITY POSTS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS community_posts (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  title       VARCHAR(220) NOT NULL,
  body        TEXT         NOT NULL,
  category    VARCHAR(60)  NOT NULL DEFAULT 'general',
  likes       INT UNSIGNED NOT NULL DEFAULT 0,
  is_pinned   TINYINT(1)   NOT NULL DEFAULT 0,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user (user_id),
  INDEX idx_category (category),
  INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ── COMMUNITY REPLIES ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS community_replies (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id     INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  body        TEXT         NOT NULL,
  likes       INT UNSIGNED NOT NULL DEFAULT 0,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_post (post_id)
) ENGINE=InnoDB;

-- ── COMMUNITY POST LIKES (prevent double-liking) ─────────────────
CREATE TABLE IF NOT EXISTS community_likes (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id  INT UNSIGNED NOT NULL,
  post_id  INT UNSIGNED DEFAULT NULL,
  reply_id INT UNSIGNED DEFAULT NULL,
  UNIQUE KEY uq_user_post (user_id, post_id),
  UNIQUE KEY uq_user_reply (user_id, reply_id)
) ENGINE=InnoDB;

-- ── Add theme/notifications columns if not already there ─────────
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS theme VARCHAR(20) DEFAULT 'dark',
  ADD COLUMN IF NOT EXISTS notifications TINYINT(1) DEFAULT 1;
