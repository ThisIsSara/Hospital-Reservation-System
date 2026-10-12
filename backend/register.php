<?php
require __DIR__ . '/common.php';
require __DIR__ . '/database.php';
require_post();

$name = input('full_name');
$email = strtolower(input('email'));
$phone = input('phone');
$password = $_POST['password'] ?? '';
$confirmation = $_POST['confirm_password'] ?? '';
$_SESSION['old_registration'] = ['full_name' => $name, 'email' => $email, 'phone' => $phone];

if ($name === '' || strlen($name) > 120) {
    flash('Enter a name of 1 to 120 bytes.');
} elseif (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('Enter a valid email address.');
} elseif (!preg_match('/^05[0-9]{8}$/', $phone)) {
    flash('Enter a Saudi mobile number: 05 followed by 8 digits.');
} elseif (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
    flash('Password must contain 8 to 72 bytes.');
} elseif (!is_string($confirmation) || $password !== $confirmation) {
    flash('Passwords do not match.');
} else {
    try {
        $statement = database()->prepare(
            'INSERT INTO patients (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
        unset($_SESSION['old_registration']);
        flash('Account created successfully. Please log in.', 'success');
        redirect_to('login.php');
    } catch (PDOException $error) {
        if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
            flash('An account with this email already exists.');
        } else {
            error_log('Registration failed: ' . $error->getMessage());
            flash('Could not save your account. Please try again.');
        }
    }
}
redirect_to('register.php');
