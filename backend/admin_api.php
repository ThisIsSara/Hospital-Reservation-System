<?php
// Simple admin dashboard API. All actions require an administrator session.
require __DIR__ . '/common.php';
require __DIR__ . '/db.php';
require_admin();
$db = database();
$action = input('action');
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $doctors = $db->query('SELECT id, name, specialty FROM doctors ORDER BY name')->fetchAll();
        $specialties = $db->query('SELECT name FROM specialties ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
        $slots = $db->query('SELECT s.id, s.doctor_id, s.appointment_date, s.appointment_time, d.name AS doctor_name,
            a.id AS appointment_id FROM slots s JOIN doctors d ON d.id = s.doctor_id
            LEFT JOIN appointments a ON a.slot_id = s.id ORDER BY s.appointment_date, s.appointment_time')->fetchAll();
        $patients = $db->query('SELECT id, full_name, email FROM patients ORDER BY id')->fetchAll();
        $appointments = $db->query('SELECT a.id, p.full_name AS patient_name, d.name AS doctor_name,
            s.appointment_date, s.appointment_time FROM appointments a
            JOIN patients p ON p.id = a.patient_id JOIN slots s ON s.id = a.slot_id
            JOIN doctors d ON d.id = s.doctor_id ORDER BY s.appointment_date DESC, s.appointment_time DESC')->fetchAll();
        respond(['success' => true, 'doctors' => $doctors, 'specialties' => $specialties,
            'slots' => $slots, 'patients' => $patients, 'appointments' => $appointments,
            'statistics' => ['doctors' => count($doctors), 'patients' => count($patients),
                'appointments' => count($appointments), 'available_slots' => count(array_filter($slots,
                    fn($slot) => $slot['appointment_id'] === null &&
                        strtotime($slot['appointment_date'] . ' ' . $slot['appointment_time']) > time()))]]);
    } catch (PDOException $error) {
        error_log('Admin dashboard failed: ' . $error->getMessage());
        respond(['success' => false, 'message' => 'Cannot load admin data. Run database/sprint2.sql first.'], 500);
    }
}
require_post();
$action = input('action');
$id = filter_var(input('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$name = input('name');
$specialty = input('specialty');
try {
    switch ($action) {
        case 'add_specialty':
            if ($name === '' || strlen($name) > 100) respond(['success' => false, 'message' => 'Enter a specialty name (max 100 characters).'], 422);
            $stmt = $db->prepare('INSERT INTO specialties (name) VALUES (?)');
            $stmt->execute([$name]);
            break;
        case 'rename_specialty':
            $oldName = input('old_name');
            if ($oldName === '' || $name === '' || strlen($name) > 100) respond(['success' => false, 'message' => 'Enter a valid specialty.'], 422);
            $db->beginTransaction();
            $stmt = $db->prepare('UPDATE specialties SET name = ? WHERE name = ?');
            $stmt->execute([$name, $oldName]);
            if (!$stmt->rowCount()) { $db->rollBack(); respond(['success' => false, 'message' => 'Specialty not found.'], 404); }
            $stmt = $db->prepare('UPDATE doctors SET specialty = ? WHERE specialty = ?');
            $stmt->execute([$name, $oldName]);
            $db->commit();
            break;
        case 'delete_specialty':
            $oldName = input('old_name');
            $stmt = $db->prepare('SELECT COUNT(*) FROM doctors WHERE specialty = ?');
            $stmt->execute([$oldName]);
            if ((int) $stmt->fetchColumn() > 0) respond(['success' => false, 'message' => 'This specialty is used by doctors. Change their specialty first.'], 409);
            $stmt = $db->prepare('DELETE FROM specialties WHERE name = ?');
            $stmt->execute([$oldName]);
            break;
        case 'add_doctor':
            if ($name === '' || strlen($name) > 120 || $specialty === '') respond(['success' => false, 'message' => 'Enter a doctor name and specialty.'], 422);
            $stmt = $db->prepare('SELECT name FROM specialties WHERE name = ?');
            $stmt->execute([$specialty]);
            if (!$stmt->fetch()) respond(['success' => false, 'message' => 'Choose an existing specialty.'], 422);
            $stmt = $db->prepare('INSERT INTO doctors (name, specialty) VALUES (?, ?)');
            $stmt->execute([$name, $specialty]);
            break;
        case 'update_doctor':
            if ($id === false || $name === '' || strlen($name) > 120 || $specialty === '') respond(['success' => false, 'message' => 'Enter valid doctor details.'], 422);
            $stmt = $db->prepare('SELECT name FROM specialties WHERE name = ?');
            $stmt->execute([$specialty]);
            if (!$stmt->fetch()) respond(['success' => false, 'message' => 'Choose an existing specialty.'], 422);
            $stmt = $db->prepare('UPDATE doctors SET name = ?, specialty = ? WHERE id = ?');
            $stmt->execute([$name, $specialty, $id]);
            break;
        case 'delete_doctor':
            if ($id === false) respond(['success' => false, 'message' => 'Choose a doctor.'], 422);
            $stmt = $db->prepare('SELECT COUNT(*) FROM slots WHERE doctor_id = ?');
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0) respond(['success' => false, 'message' => 'Remove this doctor\'s empty slots first. Existing reservations cannot be deleted this way.'], 409);
            $stmt = $db->prepare('DELETE FROM doctors WHERE id = ?');
            $stmt->execute([$id]);
            break;
        case 'add_slot':
            $doctorId = filter_var(input('doctor_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $date = input('appointment_date');
            $time = input('appointment_time');
            $dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, new DateTimeZone('Asia/Riyadh'));
            if ($doctorId === false || !$dt || $dt->format('Y-m-d H:i') !== $date . ' ' . $time ||
                $dt->getTimestamp() <= time()) {
                respond(['success' => false, 'message' => 'Choose a doctor and a future date/time.'], 422);
            }
            $stmt = $db->prepare('INSERT INTO slots (doctor_id, appointment_date, appointment_time) VALUES (?, ?, ?)');
            $stmt->execute([$doctorId, $date, $time . ':00']);
            break;
        case 'delete_slot':
            if ($id === false) respond(['success' => false, 'message' => 'Choose an appointment time.'], 422);
            $stmt = $db->prepare('SELECT COUNT(*) FROM appointments WHERE slot_id = ?');
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0) respond(['success' => false, 'message' => 'This time is booked. Cancel the reservation first.'], 409);
            $stmt = $db->prepare('DELETE FROM slots WHERE id = ?');
            $stmt->execute([$id]);
            break;
        case 'reschedule_appointment':
            $newSlotId = filter_var(input('slot_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false || $newSlotId === false) respond(['success' => false, 'message' => 'Choose a reservation and available time.'], 422);
            $db->beginTransaction();
            $stmt = $db->prepare('SELECT a.slot_id FROM appointments a WHERE a.id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $current = $stmt->fetch();
            if (!$current) {
                $db->rollBack();
                respond(['success' => false, 'message' => 'Reservation not found.'], 404);
            }
            if ((int) $current['slot_id'] === $newSlotId) {
                $db->rollBack();
                respond(['success' => false, 'message' => 'Choose a different appointment time.'], 422);
            }
            $stmt = $db->prepare('SELECT id FROM slots WHERE id = ? AND TIMESTAMP(appointment_date, appointment_time) > NOW() FOR UPDATE');
            $stmt->execute([$newSlotId]);
            if (!$stmt->fetch()) {
                $db->rollBack();
                respond(['success' => false, 'message' => 'The selected appointment time is not available.'], 422);
            }
            $stmt = $db->prepare('UPDATE appointments SET slot_id = ? WHERE id = ?');
            $stmt->execute([$newSlotId, $id]);
            $db->commit();
            break;
        case 'cancel_appointment':
            if ($id === false) respond(['success' => false, 'message' => 'Choose a reservation.'], 422);
            $stmt = $db->prepare('DELETE FROM appointments WHERE id = ?');
            $stmt->execute([$id]);
            break;
        case 'add_patient':
            $email = strtolower(input('email'));
            $password = $_POST['password'] ?? '';
            if ($name === '' || strlen($name) > 120 || strlen($email) > 190 ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password) ||
                strlen($password) < 8 || strlen($password) > 72) {
                respond(['success' => false, 'message' => 'Enter valid patient name, email and password (8–72 characters).'], 422);
            }
            $stmt = $db->prepare('INSERT INTO patients (full_name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            break;
        case 'update_patient':
            $email = strtolower(input('email'));
            if ($id === false || $name === '' || strlen($name) > 120 || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                respond(['success' => false, 'message' => 'Enter valid patient name and email.'], 422);
            }
            $stmt = $db->prepare('UPDATE patients SET full_name = ?, email = ? WHERE id = ?');
            $stmt->execute([$name, $email, $id]);
            break;
        case 'delete_patient':
            if ($id === false) respond(['success' => false, 'message' => 'Choose a patient.'], 422);
            $db->beginTransaction();
            $stmt = $db->prepare('DELETE FROM appointments WHERE patient_id = ?');
            $stmt->execute([$id]);
            $stmt = $db->prepare('DELETE FROM patients WHERE id = ?');
            $stmt->execute([$id]);
            $db->commit();
            break;
        default:
            respond(['success' => false, 'message' => 'Unknown action.'], 422);
    }
    respond(['success' => true, 'message' => 'Changes saved successfully.']);
} catch (PDOException $error) {
    if ($db->inTransaction()) $db->rollBack();
    if ((int) ($error->errorInfo[1] ?? 0) === 1062) respond(['success' => false, 'message' => 'This record already exists.'], 409);
    if ((int) ($error->errorInfo[1] ?? 0) === 1451 || (int) ($error->errorInfo[1] ?? 0) === 1452)
        respond(['success' => false, 'message' => 'This record is linked to other data.'], 409);
    error_log('Admin update failed: ' . $error->getMessage());
    respond(['success' => false, 'message' => 'Could not save changes.'], 500);
}
