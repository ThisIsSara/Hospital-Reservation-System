<?php
require __DIR__ . '/../backend/common.php';
$title = 'Create Patient Account';
$old = $_SESSION['old_registration'] ?? [];
unset($_SESSION['old_registration']);
require __DIR__ . '/header.php';
?>
<section class="form-card">
    <p class="eyebrow">Patient registration</p>
    <h1>Create your account</h1>
    <p>Register to view doctors and book an appointment.</p>
    <form action="../backend/register.php" method="post" id="registration-form">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <label for="full_name">Full name</label>
        <input id="full_name" name="full_name" autocomplete="name" maxlength="120" value="<?= escape($old['full_name'] ?? '') ?>" required>
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" autocomplete="email" maxlength="190" value="<?= escape($old['email'] ?? '') ?>" required>
        <label for="phone">Mobile number</label>
        <input id="phone" name="phone" type="tel" autocomplete="tel" pattern="05[0-9]{8}" maxlength="10" placeholder="05xxxxxxxx" value="<?= escape($old['phone'] ?? '') ?>" required>
        <small>Saudi mobile number, 10 digits starting with 05.</small>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
        <small>8–72 bytes. English letters, numbers and symbols are suitable.</small>
        <label for="confirm_password">Confirm password</label>
        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
        <button type="submit">Create Account</button>
    </form>
    <p class="form-link">Already registered? <a href="login.php">Log in</a></p>
</section>
<?php require __DIR__ . '/footer.php'; ?>
