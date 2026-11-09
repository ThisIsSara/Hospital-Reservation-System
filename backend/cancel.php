<?php
// Cancel one appointment owned by the logged-in patient.
require __DIR__ . '/common.php';
require __DIR__ . '/db.php';
require_post();
$patientId = require_patient();
$appointmentId = filter_var(input('appointment_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($appointmentId === false) {
    respond(['success' => false, 'message' => 'Choose a valid appointment.'], 422);
}
try {
    $statement = database()->prepare('DELETE FROM appointments WHERE id = ? AND patient_id = ?');
    $statement->execute([$appointmentId, $patientId]);
    if ($statement->rowCount() === 0) {
        respond(['success' => false, 'message' => 'Appointment not found.'], 404);
    }
    respond(['success' => true, 'message' => 'Appointment cancelled successfully.']);
} catch (PDOException $error) {
    error_log('Appointment cancellation failed: ' . $error->getMessage());
    respond(['success' => false, 'message' => 'Could not cancel appointment. Please try again.'], 500);
}
