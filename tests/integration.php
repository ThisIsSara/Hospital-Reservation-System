<?php
// Run with PHP CLI against a running local server and the prototype database.
// No testing library is needed. Fixtures created here are removed in finally.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../backend/common.php';
require __DIR__ . '/../backend/db.php';
$config = require __DIR__ . '/../backend/config.local.php';
if ($config['database'] !== 'hospital_reservation') {
    fwrite(STDERR, "Tests only run against hospital_reservation.\n");
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
        
        return ['status' => $status, 'body' => $body, 'location' => $location, 'json' => json_decode($body, true)];
    }

    public function session(): array
    {
        $response = $this->request('/backend/session.php');
        $this->token = $response['json']['csrf_token'] ?? '';
        return $response['json'];
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
$failed = false;
try {
    check((int) $connection->query('SELECT COUNT(*) FROM doctors')->fetchColumn() >= 3, 'Three sample doctors loaded from MySQL');
    check((int) $connection->query('SELECT COUNT(*) FROM slots WHERE TIMESTAMP(appointment_date, appointment_time) > NOW()')->fetchColumn() >= 6, 'Six sample times loaded from MySQL');
    $columns = $connection->query('SHOW COLUMNS FROM patients')->fetchAll(PDO::FETCH_COLUMN);
    check($columns === ['id', 'full_name', 'email', 'password_hash'], 'Patient table matches the team schema, with no phone field');
    foreach (['register', 'login', 'doctors', 'booking'] as $page) {
        $browser = new TestBrowser();
        $response = $browser->request('/frontend/' . $page . '.html');
        check($response['status'] === 200 && str_contains($response['body'], 'script.js'), $page . '.html loads with the shared JavaScript');
    }
    $anonymous = new TestBrowser();
    $session = $anonymous->session();
    check(!$session['logged_in'] && $anonymous->token !== '', 'Session endpoint supplies a CSRF token without authenticating');
    check($anonymous->request('/backend/doctors.php')['status'] === 401, 'Anonymous doctor API access rejected');
    check($anonymous->request('/backend/book.php')['status'] === 405, 'GET cannot create a reservation');
    check($anonymous->request('/backend/register.php', ['email' => $email1])['status'] === 403, 'Missing CSRF token rejected');
    check($anonymous->request('/backend/login.php', ['csrf_token' => 'wrong'])['status'] === 403, 'Invalid CSRF token rejected');
    check($anonymous->post('/backend/book.php', ['slot_id' => 1])['status'] === 401, 'Anonymous booking POST rejected');
    $first = new TestBrowser();
    $first->session();
    $registration = ['full_name' => 'Test Patient', 'email' => $email1,
        'password' => $password, 'confirm_password' => $password];
    foreach ([['full_name', ''], ['email', 'bad-email'], ['password', 'short'],
              ['confirm_password', 'different'], ['password', '']] as [$field, $badValue]) {
        $invalid = $registration;
        $invalid[$field] = $badValue;
        $response = $first->post('/backend/register.php', $invalid);
        check($response['status'] === 422 && !$response['json']['success'], 'Server rejects invalid ' . $field);
    }
    $findPatient = $connection->prepare('SELECT * FROM patients WHERE email = ?');
    $findPatient->execute([$email1]);
    check($findPatient->fetch() === false, 'Invalid registrations did not create a patient');
    $response = $first->post('/backend/register.php', $registration);
    check($response['status'] === 201 && $response['json']['success'], 'Valid registration returns success JSON');
    $findPatient->execute([$email1]);
    $patient = $findPatient->fetch();
    check($patient && $patient['full_name'] === 'Test Patient', 'Patient actually saved in MySQL');
    check($patient['password_hash'] !== $password && password_verify($password, $patient['password_hash']), 'Password saved as a verifiable hash');
    $registration['email'] = strtoupper($email1);
    check($first->post('/backend/register.php', $registration)['status'] === 409, 'Duplicate email rejected, including different case');
    $countEmail = $connection->prepare('SELECT COUNT(*) FROM patients WHERE email = ?');
    $countEmail->execute([$email1]);
    check((int) $countEmail->fetchColumn() === 1, 'No duplicate patient row saved');
    check($first->post('/backend/login.php', ['email' => $email1, 'password' => 'wrong'])['status'] === 401, 'Incorrect password rejected');
    check(in_array($first->post('/backend/login.php', ['email' => "x' OR 1=1 --@example.com", 'password' => $password])['status'], [401, 422], true), 'SQL injection does not authenticate');
    check(!$first->session()['logged_in'], 'Invalid login leaves the session anonymous');
    $oldCookie = $first->cookie;
    $response = $first->post('/backend/login.php', ['email' => strtoupper($email1), 'password' => $password]);
    check($response['status'] === 200 && $response['json']['redirect'] === 'doctors.html', 'Correct login directs the frontend to doctors.html');
    check($first->cookie !== $oldCookie, 'Login regenerates the session ID');
    check($first->session()['logged_in'], 'Authenticated session available to HTML pages');
    $response = $first->request('/backend/doctors.php');
    $doctorName = $connection->query('SELECT name FROM doctors ORDER BY name LIMIT 1')->fetchColumn();
    check($response['status'] === 200 && in_array($doctorName, array_column($response['json']['doctors'], 'name'), true), 'Doctor API returns actual MySQL names');
    check(count($response['json']['slots']) >= 6, 'Doctor API returns available database slots');
    $doctorId = $connection->query('SELECT id FROM doctors ORDER BY id LIMIT 1')->fetchColumn();
    $insertSlot = $connection->prepare('INSERT INTO slots (doctor_id, appointment_date, appointment_time) VALUES (?, ?, ?)');
    $date = $connection->query('SELECT DATE_ADD(CURDATE(), INTERVAL 30 DAY)')->fetchColumn();
    $past = $connection->query('SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY)')->fetchColumn();
    foreach ([[$doctorId, $date, '14:00:00'], [$doctorId, $past, '14:00:00']] as $fixture) {
        $insertSlot->execute($fixture);
        $slotIds[] = (int) $connection->lastInsertId();
    }
    $response = $first->request('/backend/doctors.php?slot_id=' . $slotIds[0]);
    check($response['status'] === 200 && (int) $response['json']['slot']['id'] === $slotIds[0], 'Booking detail API returns the correct selected slot');
    $response = $first->post('/backend/book.php', ['slot_id' => $slotIds[0], 'patient_id' => 999999]);
    check($response['status'] === 201 && $response['json']['success'] && $response['json']['appointment_id'] > 0, 'Booking returns success and a reservation number');
    $findBooking = $connection->prepare('SELECT * FROM appointments WHERE slot_id = ?');
    $findBooking->execute([$slotIds[0]]);
    $booking = $findBooking->fetch();
    check($booking && (int) $booking['patient_id'] === (int) $patient['id'], 'Reservation saved for the session patient, ignoring forged patient_id');
    $response = $first->request('/backend/doctors.php');
    check(!in_array($slotIds[0], array_map('intval', array_column($response['json']['slots'], 'id')), true), 'Booked slot removed from available times');
    check($first->request('/backend/doctors.php?slot_id=' . $slotIds[0])['status'] === 404, 'Occupied slot cannot be loaded for new confirmation');
    check($first->post('/backend/book.php', ['slot_id' => $slotIds[0]])['status'] === 409, 'Repeated booking POST rejected');
    check($first->post('/backend/book.php', ['slot_id' => $slotIds[1]])['status'] === 422, 'Past appointment rejected');
    check($first->post('/backend/book.php', ['slot_id' => 999999999])['status'] === 422, 'Nonexistent appointment rejected');
    check($first->post('/backend/book.php', ['slot_id' => '1 OR 1=1'])['status'] === 422, 'Malformed appointment ID rejected');
    $second = new TestBrowser();
    $second->session();
    $registration['email'] = $email2;
    $registration['full_name'] = 'Second Test Patient';
    check($second->post('/backend/register.php', $registration)['status'] === 201, 'Second patient registered');
    $second->post('/backend/login.php', ['email' => $email2, 'password' => $password]);
    $second->session();
    check($second->post('/backend/book.php', ['slot_id' => $slotIds[0]])['status'] === 409, 'Another patient cannot book the occupied slot');
    $findBooking->execute([$slotIds[0]]);
    check(count($findBooking->fetchAll()) === 1, 'Duplicate requests left exactly one reservation');
    try {
        $connection->prepare('INSERT INTO appointments (patient_id, slot_id) VALUES (?, ?)')->execute([$patient['id'], $slotIds[0]]);
        throw new RuntimeException('Database unexpectedly accepted a duplicate reservation');
    } catch (PDOException $error) {
        check((int) ($error->errorInfo[1] ?? 0) === 1062, 'MySQL UNIQUE(slot_id) independently rejects a duplicate');
    }
    try {
        $connection->prepare('INSERT INTO appointments (patient_id, slot_id) VALUES (?, ?)')->execute([999999999, $slotIds[1]]);
        throw new RuntimeException('Database unexpectedly accepted a missing patient');
    } catch (PDOException $error) {
        check((int) ($error->errorInfo[1] ?? 0) === 1452, 'Patient foreign key independently rejects an invalid reference');
    }
    $first->post('/backend/logout.php', []);
    check(!$first->session()['logged_in'] && $first->request('/backend/doctors.php')['status'] === 401, 'Logout removes authenticated access');
} catch (Throwable $error) {
    $failed = true;
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
} finally {
    $findIds = $connection->prepare('SELECT id FROM patients WHERE email IN (?, ?)');
    $findIds->execute([$email1, $email2]);
    foreach ($findIds->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $connection->prepare('DELETE FROM appointments WHERE patient_id = ?')->execute([$id]);
        $connection->prepare('DELETE FROM patients WHERE id = ?')->execute([$id]);
    }
    foreach ($slotIds as $id) {
        $connection->prepare('DELETE FROM slots WHERE id = ?')->execute([$id]);
    }
}
echo $passed . ' checks passed.' . PHP_EOL;
exit($failed ? 1 : 0);
