<?php
require __DIR__ . '/common.php';
require __DIR__ . '/database.php';
require_post();
$email = strtolower(input('email'));
$password = $_POST['password'] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password) || $password === '') {
    flash('Enter a valid email address and password.');
    redirect_to('login.php');
}
try {
    $statement = database()->prepare('SELECT id, full_name, password_hash FROM patients WHERE email = ?');
    $statement->execute([$email]);
    $patient = $statement->fetch();
    if (!$patient || !password_verify($password, $patient['password_hash'])) {
        flash('Incorrect email or password.');
        redirect_to('login.php');
    }
    session_regenerate_id(true);
    $_SESSION['patient_id'] = (int) $patient['id'];
    $_SESSION['patient_name'] = $patient['full_name'];
    unset($_SESSION['csrf_token']);
    flash('You are now logged in.', 'success');
    redirect_to('doctors.php');
} catch (PDOException $error) {
    error_log('Login failed: ' . $error->getMessage());
    flash('Could not log in. Please try again.');
    redirect_to('login.php');
}
