<?php
// Administrator login (independent from patient accounts).
require __DIR__ . '/common.php';
require __DIR__ . '/db.php';
require_post();
$username = input('username');
$password = $_POST['password'] ?? '';
if ($username === '' || !is_string($password) || $password === '') {
    respond(['success' => false, 'message' => 'Enter username and password.'], 422);
}
try {
    $statement = database()->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
    $statement->execute([$username]);
    $admin = $statement->fetch();
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        respond(['success' => false, 'message' => 'Incorrect username or password.'], 401);
    }
    session_regenerate_id(true);
    unset($_SESSION['patient_id'], $_SESSION['patient_name']);
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = $username;
    unset($_SESSION['csrf_token']);
    respond(['success' => true, 'message' => 'Login successful.', 'redirect' => 'admin.html']);
} catch (PDOException $error) {
    error_log('Admin login failed: ' . $error->getMessage());
    respond(['success' => false, 'message' => 'Could not log in. Check that Sprint 2 database setup was completed.'], 500);
}
