<?php
// Sarah: save one reservation. Database constraints protect against concurrent requests.
require __DIR__ . '/common.php';
require __DIR__ . '/database.php';
require_post();
$patientId = require_patient();
$slotId = filter_var(input('slot_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($slotId === false) {
    flash('Choose a valid appointment time.');
    redirect_to('doctors.php');
}
$connection = database();
try {
    $connection->beginTransaction();
    // Lock the selected slot until this request commits or rolls back.
    $statement = $connection->prepare(
        'SELECT id, starts_at FROM appointment_slots WHERE id = ? AND starts_at > NOW() FOR UPDATE'
    );
    $statement->execute([$slotId]);
    $slot = $statement->fetch();
    if (!$slot) {
        $connection->rollBack();
        flash('This appointment time is invalid or has already passed.');
        redirect_to('doctors.php');
    }
    $statement = $connection->prepare(
        'INSERT INTO appointments (patient_id, slot_id, starts_at) VALUES (?, ?, ?)'
    );
    $statement->execute([$patientId, $slotId, $slot['starts_at']]);
    $appointmentId = $connection->lastInsertId();
    $connection->commit();
    flash('Appointment booked successfully. Reservation #' . $appointmentId . '.', 'success');
} catch (PDOException $error) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
        flash('This time is already booked, or you already have an appointment at the same time.');
    } else {
        error_log('Booking failed: ' . $error->getMessage());
        flash('Could not book this appointment. Please try again.');
    }
}
redirect_to('appointments.php');
