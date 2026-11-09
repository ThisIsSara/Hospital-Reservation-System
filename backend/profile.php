<?php
// View or update the logged-in patient's name and email.
require __DIR__ . '/common.php';
require __DIR__ . '/db.php';
$patientId = require_patient();
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $statement = database()->prepare('SELECT full_name, email FROM patients WHERE id = ?');
    $statement->execute([$patientId]);
    $patient = $statement->fetch();
    if (!$patient) {
        respond(['success' => false, 'message' => 'Patient account not found.'], 404);
    }
    respond(['success' => true, 'patient' => $patient]);
}
require_post();
$name = input('full_name');
$email = strtolower(input('email'));
if ($name === '' || strlen($name) > 120) {
    respond(['success' => false, 'message' => 'Enter a valid full name.'], 422);
}
if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['success' => false, 'message' => 'Enter a valid email address.'], 422);
}
try {
    $statement = database()->prepare('UPDATE patients SET full_name = ?, email = ? WHERE id = ?');
    $statement->execute([$name, $email, $patientId]);
    $_SESSION['patient_name'] = $name;
    respond(['success' => true, 'message' => 'Personal information updated successfully.']);
} catch (PDOException $error) {
    if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
        respond(['success' => false, 'message' => 'This email is already used by another account.'], 409);
    }
    error_log('Profile update failed: ' . $error->getMessage());
    respond(['success' => false, 'message' => 'Could not update your profile. Please try again.'], 500);
}
