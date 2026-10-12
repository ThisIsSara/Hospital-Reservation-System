<?php
require __DIR__ . '/../backend/common.php';
require __DIR__ . '/../backend/database.php';
require __DIR__ . '/../backend/appointments.php';
$patientId = require_patient();
$title = 'My Appointments';
try {
    $appointments = get_patient_appointments(database(), $patientId);
} catch (PDOException $error) {
    error_log('Appointments query failed: ' . $error->getMessage());
    http_response_code(503);
    exit('Could not load your appointments. Please try again later.');
}
require __DIR__ . '/header.php';
?>
<h1>My appointments</h1>
<p>Your saved reservations. Times are shown in Riyadh time (UTC+3).</p>
<?php if (!$appointments): ?>
    <div class="empty-state">You have no booked appointments. <a href="doctors.php">Choose a doctor and time</a>.</div>
<?php else: ?>
    <div class="table-wrapper">
    <table>
        <caption class="visually-hidden">Your appointment reservations</caption>
        <thead><tr><th scope="col">Reservation</th><th scope="col">Doctor</th><th scope="col">Specialty</th><th scope="col">Date &amp; time</th></tr></thead>
        <tbody>
        <?php foreach ($appointments as $appointment): ?>
            <tr><td>#<?= (int) $appointment['id'] ?></td><td><?= escape($appointment['full_name']) ?></td><td><?= escape($appointment['specialty']) ?></td><td><?= escape(date('d M Y · H:i', strtotime($appointment['starts_at']))) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>
<p><a class="button" href="doctors.php">View Available Times</a></p>
<?php require __DIR__ . '/footer.php'; ?>
