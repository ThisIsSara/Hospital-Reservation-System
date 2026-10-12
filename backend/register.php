<?php
// Aryam: validate and save the registration form.
require __DIR__ . '/common.php';
require __DIR__ . '/db.php';
require_post();
$name = input('full_name');
$email = strtolower(input('email'));
$password = $_POST['password'] ?? '';
$confirmation = $_POST['confirm_password'] ?? '';
if ($name === '' || strlen($name) > 120) {
    respond(['success' => false, 'message' => 'Enter your full name (maximum 120 UTF-8 bytes).'], 422);
}
if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['success' => false, 'message' => 'Enter a valid email address.'], 422);
}
if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
    respond(['success' => false, 'message' => 'Password must contain 8 to 72 bytes.'], 422);
}
if (!is_string($confirmation) || $password !== $confirmation) {
    respond(['success' => false, 'message' => 'Passwords do not match.'], 422);
}
try {
    $statement = database()->prepare('INSERT INTO patients (full_name, email, password_hash) VALUES (?, ?, ?)');
    $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    respond(['success' => true, 'message' => 'Account created successfully. You can now log in.'], 201);
} catch (PDOException $error) {
    if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
        respond(['success' => false, 'message' => 'An account with this email already exists.'], 409);
    }
    error_log('Registration failed: ' . $error->getMessage());
    respond(['success' => false, 'message' => 'Could not save your account. Please try again.'], 500);
}
