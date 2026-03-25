-- ================================================================
-- EduStar Database Schema
-- MySQL 8.0+
-- ================================================================

CREATE DATABASE IF NOT EXISTS edustar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE edustar;

-- ── USERS ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120)  NOT NULL,
  email         VARCHAR(180)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  country       CHAR(3)       NOT NULL DEFAULT 'KE',
  grade         VARCHAR(30)   NOT NULL DEFAULT 'Grade 7',
  avatar        VARCHAR(255)  DEFAULT NULL,
  points        INT UNSIGNED  NOT NULL DEFAULT 0,
  level         TINYINT UNSIGNED NOT NULL DEFAULT 1,
  quizzes_taken INT UNSIGNED  NOT NULL DEFAULT 0,
  is_admin      TINYINT(1)    NOT NULL DEFAULT 0,
  is_active     TINYINT(1)    NOT NULL DEFAULT 1,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login    DATETIME      DEFAULT NULL,
  INDEX idx_email (email),
  INDEX idx_country (country)
) ENGINE=InnoDB;

-- ── COMPLETED LESSONS ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS completed_lessons (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  lesson_id  VARCHAR(60)  NOT NULL,
  subject_id VARCHAR(40)  NOT NULL,
  points_earned SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  completed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_lesson (user_id, lesson_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ── QUIZ SCORES ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS quiz_scores (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  subject_id  VARCHAR(40)  NOT NULL,
  subject_name VARCHAR(80) NOT NULL,
  score_pct   TINYINT UNSIGNED NOT NULL,
  points_earned SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  correct     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  total       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  time_secs   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  taken_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user (user_id),
  INDEX idx_subject (subject_id)
) ENGINE=InnoDB;

-- ── BOOKS (uploaded PDFs / metadata) ────────────────────────────
CREATE TABLE IF NOT EXISTS books (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  book_key     VARCHAR(60)  NOT NULL UNIQUE,   -- e.g. ke-m7
  title        VARCHAR(220) NOT NULL,
  subject      VARCHAR(40)  NOT NULL,
  country      VARCHAR(20)  NOT NULL DEFAULT 'continental',
  grade_range  VARCHAR(30)  NOT NULL,          -- lower_primary etc.
  publisher    VARCHAR(180) DEFAULT NULL,
  curriculum   VARCHAR(80)  DEFAULT NULL,
  year         SMALLINT     DEFAULT NULL,
  icon         VARCHAR(10)  DEFAULT '📚',
  color        VARCHAR(10)  DEFAULT '#FF6B2B',
  file_path    VARCHAR(255) DEFAULT NULL,      -- path to uploaded PDF
  file_size    INT UNSIGNED DEFAULT NULL,      -- bytes
  download_count INT UNSIGNED NOT NULL DEFAULT 0,
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  uploaded_by  INT UNSIGNED DEFAULT NULL,
  uploaded_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_subject (subject),
  INDEX idx_country (country),
  INDEX idx_grade (grade_range)
) ENGINE=InnoDB;

-- ── BOOK CHAPTERS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS book_chapters (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  book_id    INT UNSIGNED NOT NULL,
  chapter_num TINYINT UNSIGNED NOT NULL,
  title      VARCHAR(200) NOT NULL,
  topics     TEXT         DEFAULT NULL,  -- JSON array of topic strings
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  INDEX idx_book (book_id)
) ENGINE=InnoDB;

-- ── SESSIONS ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sessions (
  token      VARCHAR(64)  NOT NULL PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  expires_at DATETIME     NOT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ── BOOK DOWNLOADS LOG ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS book_downloads (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  book_id   INT UNSIGNED NOT NULL,
  user_id   INT UNSIGNED DEFAULT NULL,
  ip        VARCHAR(45)  DEFAULT NULL,
  downloaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  INDEX idx_book (book_id)
) ENGINE=InnoDB;
