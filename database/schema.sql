-- Create the database, tables and sample data.
CREATE DATABASE IF NOT EXISTS hospital_reservation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospital_reservation;
SET time_zone = '+03:00';

CREATE TABLE IF NOT EXISTS patients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    UNIQUE KEY unique_patient_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS doctors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    specialty VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    UNIQUE KEY unique_doctor_time (doctor_id, appointment_date, appointment_time),
    FOREIGN KEY (doctor_id) REFERENCES doctors (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    slot_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reserved_slot (slot_id),
    FOREIGN KEY (patient_id) REFERENCES patients (id),
    FOREIGN KEY (slot_id) REFERENCES slots (id)
) ENGINE=InnoDB;

-- Three fictional doctors. Existing IDs are left unchanged.
INSERT IGNORE INTO doctors (id, name, specialty) VALUES
(1, 'Dr. Amal Ahmed', 'General Medicine'),
(2, 'Dr. Noura Ali', 'Dermatology'),
(3, 'Dr. Khalid Omar', 'Cardiology');

-- Six slots for tomorrow in Riyadh time. Reimporting keeps existing slots.
-- Update expired sample times in Workbench before a later demonstration.
INSERT IGNORE INTO slots (id, doctor_id, appointment_date, appointment_time) VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00'),
(2, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00'),
(3, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:30:00'),
(4, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:30:00'),
(5, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:00:00'),
(6, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '12:00:00');
