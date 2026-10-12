<?php
// Each page loads backend/common.php before including this template.
$notification = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape($title) ?> | Hospital Reservation System</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?= empty($_SESSION['patient_id']) ? 'login.php' : 'doctors.php' ?>">Hospital Reservation System</a>
        <nav aria-label="Main navigation">
        <?php if (!empty($_SESSION['patient_id'])): ?>
            <a href="doctors.php">Doctors &amp; Times</a>
            <a href="appointments.php">My Appointments</a>
            <form action="../backend/logout.php" method="post" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                <button type="submit" class="nav-button">Log out</button>
            </form>
        <?php else: ?>
            <a href="login.php">Log in</a>
            <a href="register.php">Create Account</a>
        <?php endif; ?>
        </nav>
    </div>
</header>
<main>
<?php if ($notification): ?>
    <div role="status" class="alert <?= $notification['type'] === 'success' ? 'success' : 'error' ?>"><?= escape($notification['message']) ?></div>
<?php endif; ?>
