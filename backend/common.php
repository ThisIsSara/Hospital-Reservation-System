<?php
// Shared session, validation and form helpers. No framework is used.
date_default_timezone_set('Asia/Riyadh');
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
        http_response_code(405);
        header('Allow: POST');
        exit('This action requires a submitted form.');
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Invalid form token. Return to the page and try again.');
    }
}

function input(string $name): string
{
    $value = $_POST[$name] ?? '';
    return is_string($value) ? trim($value) : '';
}

function redirect_to(string $page): void
{
    header('Location: ../frontend/' . $page, true, 303);
    exit;
}

function flash(string $message, string $type = 'error'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function require_patient(): int
{
    if (empty($_SESSION['patient_id'])) {
        flash('Please log in before viewing or booking appointments.');
        redirect_to('login.php');
    }
    return (int) $_SESSION['patient_id'];
}
