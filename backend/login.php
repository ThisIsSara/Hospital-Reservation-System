<?php
// Aryam: authenticate a patient and start their signed-in session.
require __DIR__ . '/common.php';
require __DIR__ . '/db.php';
require_post();
$email = strtolower(input('email'));
$password = $_POST['password'] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password) || $password === '') {
    respond(['success' => false, 'message' => 'Enter a valid email address and password.'], 422);
}
try {
    $statement = database()->prepare('SELECT id, full_name, password_hash FROM patients WHERE email = ?');
    $statement->execute([$email]);
    $patient = $statement->fetch();
    if (!$patient || !password_verify($password, $patient['password_hash'])) {
        respond(['success' => false, 'message' => 'Incorrect email or password.'], 401);
    }
    session_regenerate_id(true);
    $_SESSION['patient_id'] = (int) $patient['id'];
    $_SESSION['patient_name'] = $patient['full_name'];
    unset($_SESSION['csrf_token']);
    respond(['success' => true, 'message' => 'Login successful.', 'redirect' => 'doctors.html']);
} catch (PDOException $error) {
    error_log('Login failed: ' . $error->getMessage());
    respond(['success' => false, 'message' => 'Could not log in. Please try again.'], 500);
}
