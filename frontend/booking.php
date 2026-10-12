<?php
// Sarah: confirmation page before saving a reservation.
require __DIR__ . '/../backend/common.php';
require __DIR__ . '/../backend/database.php';
require_patient();
$value = $_GET['slot_id'] ?? '';
$slotId = is_string($value) ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
$slot = false;
try {
    if ($slotId !== false) {
        $statement = database()->prepare(
            'SELECT s.id, s.starts_at, d.full_name, d.specialty
             FROM appointment_slots s JOIN doctors d ON d.id = s.doctor_id
             LEFT JOIN appointments a ON a.slot_id = s.id
             WHERE s.id = ? AND s.starts_at > NOW() AND a.id IS NULL'
        );
        $statement->execute([$slotId]);
        $slot = $statement->fetch();
    }
} catch (PDOException $error) {
    error_log('Booking page failed: ' . $error->getMessage());
    http_response_code(503);
    exit('Could not load this appointment. Please try again later.');
}
if (!$slot) {
    flash('This appointment time is unavailable. Please select another time.');
    redirect_to('doctors.php');
}
$title = 'Confirm Appointment';
require __DIR__ . '/header.php';
?>
<section class="form-card">
    <p class="eyebrow">Appointment reservation</p>
    <h1>Confirm your appointment</h1>
    <dl class="booking-details">
        <dt>Doctor</dt><dd><?= escape($slot['full_name']) ?></dd>
        <dt>Specialty</dt><dd><?= escape($slot['specialty']) ?></dd>
        <dt>Date &amp; time</dt><dd><?= escape(date('d M Y · H:i', strtotime($slot['starts_at']))) ?> (Riyadh)</dd>
        <dt>Patient</dt><dd><?= escape($_SESSION['patient_name']) ?></dd>
    </dl>
    <form action="../backend/booking.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <input type="hidden" name="slot_id" value="<?= (int) $slot['id'] ?>">
        <button type="submit">Confirm Booking</button>
    </form>
    <p class="form-link"><a href="doctors.php">Back to available times</a></p>
</section>
<?php require __DIR__ . '/footer.php'; ?>
