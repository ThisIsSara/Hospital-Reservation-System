<?php
require __DIR__ . '/../backend/common.php';
require __DIR__ . '/../backend/database.php';
require __DIR__ . '/../backend/doctors.php';
require_patient();
$title = 'Doctors and Available Times';
try {
    $doctors = get_doctors(database());
    $slots = get_available_slots(database());
} catch (PDOException $error) {
    error_log('Doctors query failed: ' . $error->getMessage());
    http_response_code(503);
    exit('Could not load appointment times. Please try again later.');
}
require __DIR__ . '/header.php';
?>
<p class="eyebrow">Welcome, <?= escape($_SESSION['patient_name']) ?></p>
<h1>Doctors &amp; available times</h1>
<p>Select a time, then confirm your reservation. All times are in Riyadh time (UTC+3).</p>
<?php if (!$doctors): ?>
    <div class="empty-state">No doctors have been added yet.</div>
<?php endif; ?>
<div class="doctor-grid">
<?php foreach ($doctors as $doctor): ?>
    <?php $doctorSlots = array_filter($slots, fn($slot) => $slot['doctor_id'] === $doctor['id']); ?>
    <article class="doctor-card">
        <p class="eyebrow"><?= escape($doctor['specialty']) ?></p>
        <h2><?= escape($doctor['full_name']) ?></h2>
        <?php if (!$doctorSlots): ?>
            <p>No available appointment times.</p>
        <?php else: ?>
            <ul class="slot-list">
            <?php foreach ($doctorSlots as $slot): ?>
                <li>
                    <time datetime="<?= escape(str_replace(' ', 'T', $slot['starts_at'])) ?>+03:00"><?= escape(date('D, d M Y · H:i', strtotime($slot['starts_at']))) ?></time>
                    <a class="button small" href="booking.php?slot_id=<?= (int) $slot['id'] ?>">Select</a>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
