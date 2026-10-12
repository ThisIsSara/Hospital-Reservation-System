<?php
// Run with PHP CLI against a running local server and the prototype database.
// No testing library is needed. Fixtures created here are removed in finally.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../backend/database.php';
$config = require __DIR__ . '/../backend/config.local.php';
if ($config['database'] !== 'hospital_sprint1_prototype') {
    fwrite(STDERR, "Tests only run against hospital_sprint1_prototype.\n");
    exit(1);
}
$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
if (!preg_match('~^http://(127\.0\.0\.1|localhost)(:[0-9]+)?(/[^?#]*)?$~', $baseUrl)) {
    fwrite(STDERR, "Use a local HTTP URL, optionally including the project folder.\n");
    exit(1);
}
$passed = 0;
function check(bool $condition, string $label): void
{
    global $passed;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $passed++;
    echo 'PASS: ' . $label . PHP_EOL;
}

class TestBrowser
{
    public string $cookie = '';
    public string $token = '';

    public function request(string $path, ?array $data = null): array
    {
        global $baseUrl;
        $headers = ['Cookie: ' . $this->cookie];
        if ($data !== null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $context = stream_context_create(['http' => [
            'method' => $data === null ? 'GET' : 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $data === null ? '' : http_build_query($data),
            'ignore_errors' => true,
            'follow_location' => 0,
            'timeout' => 10,
        ]]);
        $body = file_get_contents($baseUrl . $path, false, $context);
        if ($body === false) {
            throw new RuntimeException('Cannot reach local PHP server.');
        }
        $status = 0;
        $location = '';
        foreach ($http_response_header as $header) {
            if (preg_match('#^HTTP/\S+ (\d+)#', $header, $match)) {
                $status = (int) $match[1];
            }
            if (preg_match('/^Set-Cookie: (PHPSESSID=[^;]+)/i', $header, $match)) {
                $this->cookie = $match[1];
            }
            if (stripos($header, 'Location: ') === 0) {
                $location = substr($header, 10);
            }
        }
        if (preg_match('/name="csrf_token" value="([^"]+)"/', $body, $match)) {
            $this->token = $match[1];
        }
        return ['status' => $status, 'body' => $body, 'location' => $location];
    }

    public function post(string $path, array $data): array
    {
        return $this->request($path, array_merge($data, ['csrf_token' => $this->token]));
    }
}

$connection = database();
$suffix = bin2hex(random_bytes(8));
$email1 = 'sprint1-test-' . $suffix . '@example.com';
$email2 = 'sprint1-test-' . $suffix . '-2@example.com';
$password = 'StudentTest123!';
$slotIds = [];
$patientIds = [];
$failed = false;
try {
    check((int) $connection->query('SELECT COUNT(*) FROM doctors')->fetchColumn() >= 4, 'Doctors loaded from MySQL seed');
    check((int) $connection->query('SELECT COUNT(*) FROM appointment_slots WHERE starts_at > NOW()')->fetchColumn() >= 48, 'Future sample times loaded from MySQL seed');

    $anonymous = new TestBrowser();
    $response = $anonymous->request('/frontend/doctors.php');
    check($response['status'] === 303 && str_contains($response['location'], 'login.php'), 'Anonymous doctor access redirects to login');
    $response = $anonymous->request('/frontend/booking.php?slot_id=1');
    check($response['status'] === 303, 'Anonymous booking page blocked');
    $response = $anonymous->request('/frontend/login.php');
    check($response['status'] === 200 && $anonymous->token !== '', 'Login page and CSRF form token load');
    check($anonymous->request('/backend/booking.php')['status'] === 405, 'GET cannot create a booking');
    check($anonymous->request('/backend/register.php', ['email' => $email1])['status'] === 403, 'Missing CSRF token rejected');
    check($anonymous->request('/backend/login.php', ['csrf_token' => 'wrong'])['status'] === 403, 'Invalid CSRF token rejected');
    check($anonymous->post('/backend/booking.php', ['slot_id' => 1])['status'] === 303, 'Anonymous booking POST rejected');

    $first = new TestBrowser();
    $first->request('/frontend/register.php');
    $registration = ['full_name' => 'Test Patient', 'email' => $email1, 'phone' => '0501234567',
        'password' => $password, 'confirm_password' => $password];
    foreach ([['full_name', ''], ['email', 'bad-email'], ['phone', '123'],
              ['password', 'short'], ['confirm_password', 'different']] as [$field, $badValue]) {
        $invalid = $registration;
        $invalid[$field] = $badValue;
        $response = $first->post('/backend/register.php', $invalid);
        check($response['status'] === 303 && str_contains($response['location'], 'register.php'), 'Server rejects invalid ' . $field);
        $first->request('/frontend/register.php');
    }
    $findPatient = $connection->prepare('SELECT * FROM patients WHERE email = ?');
    $findPatient->execute([$email1]);
    check($findPatient->fetch() === false, 'Invalid submissions did not save a patient');
    $response = $first->post('/backend/register.php', $registration);
    check($response['status'] === 303 && str_contains($response['location'], 'login.php'), 'Valid registration redirects to login');
    $findPatient->execute([$email1]);
    $patient = $findPatient->fetch();
    check($patient && $patient['full_name'] === 'Test Patient', 'Registration actually persisted in MySQL');
    $patientIds[] = (int) $patient['id'];
    check($patient['password_hash'] !== $password && password_verify($password, $patient['password_hash']), 'Password stored as a verifiable hash');
    $first->request('/frontend/register.php');
    $registration['email'] = strtoupper($email1);
    $first->post('/backend/register.php', $registration);
    $response = $first->request('/frontend/register.php');
    check(str_contains($response['body'], 'already exists'), 'Duplicate email rejected, including different case');
    $countEmail = $connection->prepare('SELECT COUNT(*) FROM patients WHERE email = ?');
    $countEmail->execute([$email1]);
    check((int) $countEmail->fetchColumn() === 1, 'Duplicate registration did not add a second row');

    $first->request('/frontend/login.php');
    $first->post('/backend/login.php', ['email' => $email1, 'password' => 'wrong-password']);
    check(str_contains($first->request('/frontend/login.php')['body'], 'Incorrect email or password'), 'Incorrect password rejected');
    $first->post('/backend/login.php', ['email' => "x' OR 1=1 --@example.com", 'password' => $password]);
    check($first->request('/frontend/doctors.php')['status'] === 303, 'SQL injection input did not authenticate');
    $first->request('/frontend/login.php');
    $oldCookie = $first->cookie;
    $response = $first->post('/backend/login.php', ['email' => strtoupper($email1), 'password' => $password]);
    check($response['status'] === 303 && str_contains($response['location'], 'doctors.php'), 'Correct credentials accepted');
    check($first->cookie !== $oldCookie, 'Login regenerates the session ID');
    $response = $first->request('/frontend/doctors.php');
    $doctorName = $connection->query('SELECT full_name FROM doctors ORDER BY full_name LIMIT 1')->fetchColumn();
    check($response['status'] === 200 && str_contains($response['body'], $doctorName) && str_contains($response['body'], 'booking.php?slot_id='), 'Doctor page displays database doctors and available slots');

    // Three dedicated slots: two simultaneous doctors and one past time.
    $doctorIds = $connection->query('SELECT id FROM doctors ORDER BY id LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);
    $time = $connection->query('SELECT DATE_ADD(NOW(), INTERVAL 30 DAY)')->fetchColumn();
    $past = $connection->query('SELECT DATE_SUB(NOW(), INTERVAL 1 DAY)')->fetchColumn();
    $insertSlot = $connection->prepare('INSERT INTO appointment_slots (doctor_id, starts_at) VALUES (?, ?)');
    foreach ([[$doctorIds[0], $time], [$doctorIds[1], $time], [$doctorIds[0], $past]] as $fixture) {
        $insertSlot->execute($fixture);
        $slotIds[] = (int) $connection->lastInsertId();
    }
    $response = $first->request('/frontend/booking.php?slot_id=' . $slotIds[0]);
    check($response['status'] === 200 && str_contains($response['body'], 'Confirm Booking'), 'Selected slot opens a confirmation form');
    $response = $first->post('/backend/booking.php', ['slot_id' => $slotIds[0], 'patient_id' => 999999]);
    check($response['status'] === 303 && str_contains($response['location'], 'appointments.php'), 'Successful booking redirects to saved appointments');
    $response = $first->request('/frontend/appointments.php');
    check(str_contains($response['body'], 'Appointment booked successfully.'), 'Booking success message displayed');
    $findBooking = $connection->prepare('SELECT * FROM appointments WHERE slot_id = ?');
    $findBooking->execute([$slotIds[0]]);
    $booking = $findBooking->fetch();
    check($booking && (int) $booking['patient_id'] === $patientIds[0], 'Booking persisted for session patient, ignoring forged patient_id');
    $response = $first->request('/frontend/doctors.php');
    check(!str_contains($response['body'], 'booking.php?slot_id=' . $slotIds[0] . '"'), 'Booked time removed from available times');
    $first->post('/backend/booking.php', ['slot_id' => $slotIds[0]]);
    check(str_contains($first->request('/frontend/appointments.php')['body'], 'already booked'), 'Repeated booking POST rejected');
    $first->post('/backend/booking.php', ['slot_id' => $slotIds[1]]);
    check(str_contains($first->request('/frontend/appointments.php')['body'], 'already booked'), 'Same patient cannot reserve two doctors at the same time');
    $first->post('/backend/booking.php', ['slot_id' => $slotIds[2]]);
    check(str_contains($first->request('/frontend/doctors.php')['body'], 'already passed'), 'Past appointment rejected');
    $first->post('/backend/booking.php', ['slot_id' => '999999999']);
    check(str_contains($first->request('/frontend/doctors.php')['body'], 'invalid or'), 'Nonexistent appointment rejected');
    $first->post('/backend/booking.php', ['slot_id' => '1 OR 1=1']);
    check(str_contains($first->request('/frontend/doctors.php')['body'], 'valid appointment'), 'Malformed appointment ID rejected');

    $second = new TestBrowser();
    $second->request('/frontend/register.php');
    $registration['email'] = $email2;
    $registration['full_name'] = '<script>alert(1)</script>';
    $second->post('/backend/register.php', $registration);
    $findPatient->execute([$email2]);
    $patient2 = $findPatient->fetch();
    if ($patient2) {
        $patientIds[] = (int) $patient2['id'];
    }
    check((bool) $patient2, 'Second test patient created');
    $second->request('/frontend/login.php');
    $second->post('/backend/login.php', ['email' => $email2, 'password' => $password]);
    $response = $second->request('/frontend/doctors.php');
    check(str_contains($response['body'], '&lt;script&gt;') && !str_contains($response['body'], '<script>alert(1)</script>'), 'Patient name escaped in HTML');
    $second->post('/backend/booking.php', ['slot_id' => $slotIds[0]]);
    check(str_contains($second->request('/frontend/appointments.php')['body'], 'already booked'), 'Another patient cannot reserve an occupied slot');
    $response = $second->request('/frontend/appointments.php?patient_id=' . $patientIds[0]);
    check(str_contains($response['body'], 'no booked appointments'), 'Patient cannot read another patient reservations via query parameter');
    $countBookings = $connection->prepare('SELECT COUNT(*) FROM appointments WHERE patient_id = ?');
    $countBookings->execute([$patientIds[0]]);
    check((int) $countBookings->fetchColumn() === 1, 'All duplicate/invalid attempts left exactly one saved booking');
    $first->request('/frontend/appointments.php');
    $first->post('/backend/logout.php', []);
    check($first->request('/frontend/appointments.php')['status'] === 303, 'Logout removes authenticated access');
} catch (Throwable $error) {
    $failed = true;
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
} finally {
    // Clean only the two uniquely named accounts and slots created by this run.
    $findIds = $connection->prepare('SELECT id FROM patients WHERE email IN (?, ?)');
    $findIds->execute([$email1, $email2]);
    $patientIds = $findIds->fetchAll(PDO::FETCH_COLUMN);
    foreach ($patientIds as $id) {
        $connection->prepare('DELETE FROM appointments WHERE patient_id = ?')->execute([$id]);
        $connection->prepare('DELETE FROM patients WHERE id = ?')->execute([$id]);
    }
    foreach ($slotIds as $id) {
        $connection->prepare('DELETE FROM appointment_slots WHERE id = ?')->execute([$id]);
    }
}
echo $passed . ' checks passed.' . PHP_EOL;
exit($failed ? 1 : 0);
