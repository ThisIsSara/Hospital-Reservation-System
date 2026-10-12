# Sprint 1 Testing

## Executed checks

Rerun on 12 October 2026 using Linux, PHP 8.3.6 with PDO MySQL, and MySQL
Community Server 8.0.46. `tests/integration.php` completed with exit code 0:
**47 checks passed**.

The suite sent HTTP requests to the PHP development server and checked saved
rows in MySQL. It covered:

- Registration validation, password hashing and duplicate email rejection.
- Valid/invalid login and session ID regeneration.
- Database doctor listings, available times and selected slot details.
- Booking persistence, duplicate rejection and invalid/past slot rejection.
- Anonymous access, CSRF tokens, session-based patient IDs and logout.
- MySQL uniqueness/foreign-key constraints and loading the four HTML pages.

Reimporting the schema kept three doctors and six sample slots. Test accounts,
bookings and temporary slots were removed after the run. All 11 PHP files passed
`php -l`; `frontend/script.js` passed `node --check`.

## Not tested

Windows installation, real-browser interaction, screenshots and simultaneous
requests were not tested. Duplicate HTTP requests were tested sequentially.

## Manual checklist — pending

1. Follow the Windows setup in README and open `register.html` in a browser.
2. Register, verify the MySQL patient row, and log in through `login.html`.
3. Check doctors/times and choose a slot; verify its details on `booking.html`.
4. Confirm the booking; check the success message and saved MySQL reservation.
5. Return to the doctor page and verify the booked time is no longer available.
6. Retry the same booking and check that only one reservation exists.
7. Try duplicate email, wrong password, empty fields and booking without login.
8. Record genuine results and screenshots for the Sprint Review.

## Run the automated suite

Start the PHP server, then run in a second terminal from the project folder:

```powershell
C:\php\php.exe tests\integration.php http://127.0.0.1:8000
```

Use a freshly seeded local database and enable PHP's `allow_url_fopen`.
