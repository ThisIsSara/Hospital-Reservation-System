<?php
// Sarah: save a reservation; UNIQUE(slot_id) rejects repeated/concurrent bookings.
require __DIR__ . '/common.php';
require __DIR__ . '/db.php';
require_post();
$patientId = require_patient();
$slotId = filter_var(input('slot_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($slotId === false) {
    respond(['success' => false, 'message' => 'Choose a valid appointment time.'], 422);
}
$connection = database();
try {
    $connection->beginTransaction();
    $statement = $connection->prepare(
        'SELECT id FROM slots WHERE id = ?
         AND TIMESTAMP(appointment_date, appointment_time) > NOW() FOR UPDATE'
    );
    $statement->execute([$slotId]);
    if (!$statement->fetch()) {
        $connection->rollBack();
        respond(['success' => false, 'message' => 'This appointment is invalid or has already passed.'], 422);
    }
    $statement = $connection->prepare('INSERT INTO appointments (patient_id, slot_id) VALUES (?, ?)');
    $statement->execute([$patientId, $slotId]);
    $appointmentId = (int) $connection->lastInsertId();
    $connection->commit();
    respond(['success' => true, 'message' => 'Appointment booked successfully.', 'appointment_id' => $appointmentId], 201);
} catch (PDOException $error) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
        respond(['success' => false, 'message' => 'This appointment is already booked. Please choose another time.'], 409);
    }
    error_log('Booking failed: ' . $error->getMessage());
    respond(['success' => false, 'message' => 'Could not book this appointment. Please try again.'], 500);
}
