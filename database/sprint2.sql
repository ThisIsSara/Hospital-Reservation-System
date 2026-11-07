-- Run once in MySQL Workbench after Sprint 1 schema.sql.
-- Adds only the tables needed for Sprint 2 administration.
USE hospital_reservation;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS specialties (
    name VARCHAR(100) PRIMARY KEY
) ENGINE=InnoDB;

INSERT IGNORE INTO specialties (name)
SELECT DISTINCT specialty FROM doctors WHERE specialty <> '';
