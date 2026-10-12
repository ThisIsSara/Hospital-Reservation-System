-- Dedicated prototype database. Existing team databases are not altered.
CREATE DATABASE IF NOT EXISTS hospital_sprint1_prototype CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospital_sprint1_prototype;

CREATE TABLE IF NOT EXISTS patients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(10) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_patient_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS doctors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    specialty VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointment_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    UNIQUE KEY unique_doctor_time (doctor_id, starts_at),
    UNIQUE KEY unique_slot_time (id, starts_at),
    FOREIGN KEY (doctor_id) REFERENCES doctors (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    slot_id INT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reserved_slot (slot_id),
    UNIQUE KEY unique_patient_time (patient_id, starts_at),
    FOREIGN KEY (patient_id) REFERENCES patients (id),
    -- The stored time must match its slot, so the patient/time constraint is reliable.
    FOREIGN KEY (slot_id, starts_at) REFERENCES appointment_slots (id, starts_at)
) ENGINE=InnoDB;
