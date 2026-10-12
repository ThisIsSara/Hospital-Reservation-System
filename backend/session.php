<?php
// HTML pages use this endpoint to obtain a CSRF token and current session details.
require __DIR__ . '/common.php';
respond([
    'success' => true,
    'csrf_token' => csrf_token(),
    'logged_in' => !empty($_SESSION['patient_id']),
    'patient_name' => $_SESSION['patient_name'] ?? '',
]);
