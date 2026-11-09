<?php
// List appointments belonging to the logged-in patient.
require __DIR__ . '/common.php';
require __DIR__ . '/db.php';
$patientId = require_patient();
try {
    $statement = database()->prepare(
        'SELECT a.id, d.name AS doctor_name, d.specialty, s.appointment_date, s.appointment_time
         FROM appointments a
         JOIN slots s ON s.id = a.slot_id
         JOIN doctors d ON d.id = s.doctor_id
         WHERE a.patient_id = ?
         ORDER BY s.appointment_date DESC, s.appointment_time DESC'
    );
    $statement->execute([$patientId]);
    respond(['success' => true, 'appointments' => $statement->fetchAll()]);
} catch (PDOException $error) {
    error_log('Appointment listing failed: ' . $error->getMessage());
    respond(['success' => false, 'message' => 'Could not load appointments. Please try again.'], 500);
}
