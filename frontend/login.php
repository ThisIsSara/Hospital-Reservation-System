<?php
require __DIR__ . '/../backend/common.php';
$title = 'Patient Login';
require __DIR__ . '/header.php';
?>
<section class="form-card">
    <p class="eyebrow">Patient portal</p>
    <h1>Welcome back</h1>
    <p>Log in to choose a doctor and an available time.</p>
    <form action="../backend/login.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" autocomplete="username" maxlength="190" required>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button type="submit">Log In</button>
    </form>
    <p class="form-link">New patient? <a href="register.php">Create an account</a></p>
</section>
<?php require __DIR__ . '/footer.php'; ?>
