// Browser checks improve usability; PHP repeats all required validation.
const registrationForm = document.getElementById('registration-form');
if (registrationForm) {
    const password = document.getElementById('password');
    const confirmation = document.getElementById('confirm_password');
    function checkPasswords() {
        confirmation.setCustomValidity(
            password.value === confirmation.value ? '' : 'Passwords do not match.'
        );
    }
    password.addEventListener('input', checkPasswords);
    confirmation.addEventListener('input', checkPasswords);
}
