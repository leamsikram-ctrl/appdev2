-- ============================================
--  school_db  –  Student Management Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS school_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE school_db;

CREATE TABLE IF NOT EXISTS students (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name       VARCHAR(120)    NOT NULL,
    email      VARCHAR(180)    NOT NULL UNIQUE,
    course     VARCHAR(120)    NOT NULL,
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_course (course),
    INDEX idx_name  (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: seed data for quick testing
INSERT INTO students (name, email, course) VALUES
  ('Maria Santos',   'maria.santos@school.edu',   'Computer Science'),
  ('Juan dela Cruz', 'juan.delacruz@school.edu',  'Information Technology'),
  ('Ana Reyes',      'ana.reyes@school.edu',       'Business Administration');