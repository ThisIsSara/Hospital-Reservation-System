<?php
// Move one of the patient's appointments to another available slot.
require __DIR__ . '/common.php';
require __DIR__ . '/db.php';
require_post();
$patientId = require_patient();
$appointmentId = filter_var(input('appointment_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$newSlotId = filter_var(input('slot_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($appointmentId === false || $newSlotId === false) {
    respond(['success' => false, 'message' => 'Choose a valid appointment and time.'], 422);
}

$connection = database();
try {
    $connection->beginTransaction();
    $statement = $connection->prepare(
        'SELECT a.slot_id, s.appointment_date, s.appointment_time
         FROM appointments a
         JOIN slots s ON s.id = a.slot_id
         WHERE a.id = ? AND a.patient_id = ? FOR UPDATE'
    );
    $statement->execute([$appointmentId, $patientId]);
    $current = $statement->fetch();
    if (!$current) {
        $connection->rollBack();
        respond(['success' => false, 'message' => 'Appointment not found.'], 404);
    }
    if (strtotime($current['appointment_date'] . ' ' . $current['appointment_time']) <= time()) {
        $connection->rollBack();
        respond(['success' => false, 'message' => 'Past appointments cannot be rescheduled.'], 422);
    }
    if ((int) $current['slot_id'] === $newSlotId) {
        $connection->rollBack();
        respond(['success' => false, 'message' => 'Choose a different appointment time.'], 422);
    }

    $statement = $connection->prepare(
        'SELECT id FROM slots WHERE id = ?
         AND TIMESTAMP(appointment_date, appointment_time) > NOW() FOR UPDATE'
    );
    $statement->execute([$newSlotId]);
    if (!$statement->fetch()) {
        $connection->rollBack();
        respond(['success' => false, 'message' => 'The selected time is no longer available.'], 422);
    }
    $statement = $connection->prepare('UPDATE appointments SET slot_id = ? WHERE id = ? AND patient_id = ?');
    $statement->execute([$newSlotId, $appointmentId, $patientId]);
    $connection->commit();
    respond(['success' => true, 'message' => 'Appointment rescheduled successfully.']);
} catch (PDOException $error) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
        respond(['success' => false, 'message' => 'This time is already booked. Choose another time.'], 409);
    }
    error_log('Appointment rescheduling failed: ' . $error->getMessage());
    respond(['success' => false, 'message' => 'Could not reschedule appointment. Please try again.'], 500);
}
