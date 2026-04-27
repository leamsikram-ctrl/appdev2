-- ============================================
--  school_db  –  Student Management Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS school_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE school_db;

-- Courses Table
CREATE TABLE IF NOT EXISTS courses (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name       VARCHAR(120)    NOT NULL UNIQUE,
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name       VARCHAR(120)    NOT NULL,
    email      VARCHAR(180)    NOT NULL UNIQUE,
    course_id  INT UNSIGNED    NOT NULL,
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT,
    INDEX idx_course_id (course_id),
    INDEX idx_name  (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed courses
INSERT INTO courses (name) VALUES
  ('Computer Science'),
  ('Information Technology'),
  ('Business Administration');

-- Optional: seed data for quick testing
INSERT INTO students (name, email, course_id) VALUES
  ('Maria Santos',   'maria.santos@school.edu',   1),
  ('Juan dela Cruz', 'juan.delacruz@school.edu',  2),
  ('Ana Reyes',      'ana.reyes@school.edu',       3);