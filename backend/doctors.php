<?php
// Aryam: database data for doctors.html and the selected slot on booking.html.
require __DIR__ . '/common.php';
require __DIR__ . '/db.php';
require_patient();
try {
    if (isset($_GET['slot_id'])) {
        $value = $_GET['slot_id'];
        $slotId = is_string($value) ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
        if ($slotId === false) {
            respond(['success' => false, 'message' => 'Choose a valid appointment.'], 422);
        }
        $statement = database()->prepare(
            'SELECT s.id, s.appointment_date, s.appointment_time, d.name, d.specialty
             FROM slots s JOIN doctors d ON d.id = s.doctor_id
             LEFT JOIN appointments a ON a.slot_id = s.id
             WHERE s.id = ? AND TIMESTAMP(s.appointment_date, s.appointment_time) > NOW() AND a.id IS NULL'
        );
        $statement->execute([$slotId]);
        $slot = $statement->fetch();
        if (!$slot) {
            respond(['success' => false, 'message' => 'This appointment is unavailable. Choose another time.'], 404);
        }
        respond(['success' => true, 'slot' => $slot]);
    }
    $doctors = database()->query('SELECT id, name, specialty FROM doctors ORDER BY name')->fetchAll();
    $slots = database()->query(
        'SELECT s.id, s.doctor_id, s.appointment_date, s.appointment_time FROM slots s
         LEFT JOIN appointments a ON a.slot_id = s.id
         WHERE TIMESTAMP(s.appointment_date, s.appointment_time) > NOW() AND a.id IS NULL
         ORDER BY s.appointment_date, s.appointment_time'
    )->fetchAll();
    respond(['success' => true, 'doctors' => $doctors, 'slots' => $slots]);
} catch (PDOException $error) {
    error_log('Doctor listing failed: ' . $error->getMessage());
    respond(['success' => false, 'message' => 'Could not load doctors and times. Please try again.'], 500);
}
