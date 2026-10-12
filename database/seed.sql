USE hospital_sprint1_prototype;
SET time_zone = '+03:00';

-- Fictional doctors for the university demonstration, not real medical providers.
INSERT IGNORE INTO doctors (id, full_name, specialty) VALUES
(1, 'Dr. Amal Ahmed', 'General Medicine'),
(2, 'Dr. Noura Ali', 'Dermatology'),
(3, 'Dr. Khalid Omar', 'Cardiology'),
(4, 'Dr. Sara Hassan', 'Pediatrics');

-- Tomorrow and the following three days, with three slots per doctor each day.
-- Reimporting on the same day will not create duplicate slots.
INSERT IGNORE INTO appointment_slots (doctor_id, starts_at)
SELECT d.id, TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL days.day_offset DAY), times.slot_time)
FROM doctors d
CROSS JOIN (SELECT 1 AS day_offset UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) days
CROSS JOIN (SELECT '09:00:00' AS slot_time UNION ALL SELECT '10:00:00' UNION ALL SELECT '11:00:00') times
WHERE d.id IN (1, 2, 3, 4);
