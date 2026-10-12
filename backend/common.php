<?php
// Shared session and JSON helpers. No framework is used.
date_default_timezone_set('Asia/Riyadh');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        respond(['success' => false, 'message' => 'This action requires POST.'], 405);
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        respond(['success' => false, 'message' => 'Invalid form token. Refresh the page and try again.'], 403);
    }
}

function input(string $name): string
{
    $value = $_POST[$name] ?? '';
    return is_string($value) ? trim($value) : '';
}

function require_patient(): int
{
    if (empty($_SESSION['patient_id'])) {
        respond(['success' => false, 'message' => 'Please log in first.'], 401);
    }
    return (int) $_SESSION['patient_id'];
}
