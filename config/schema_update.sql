-- Run this in InfinityFree phpMyAdmin to add new settings columns
-- Only run once! These alter the existing users table.

ALTER TABLE users 
  ADD COLUMN IF NOT EXISTS avatar VARCHAR(10) DEFAULT '🎓',
  ADD COLUMN IF NOT EXISTS theme VARCHAR(20) DEFAULT 'dark',
  ADD COLUMN IF NOT EXISTS notifications TINYINT(1) DEFAULT 1;
