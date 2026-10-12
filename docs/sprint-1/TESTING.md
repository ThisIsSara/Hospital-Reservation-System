# Sprint 1 prototype: current test record

Test date: 12 October 2026. Runtime: PHP 8.3.6, PDO MySQL and MySQL Community Server
8.0.46 on Linux. The current HTML/Fetch version replaces the earlier PHP-rendered
experiment. The old 41-check result does not describe this revision.

## Executed tests

`tests/integration.php` completed with exit code 0: **47 checks passed**.
The suite sent actual HTTP requests to the PHP development server and verified
saved rows directly in MySQL. No mocked database or replacement database was used.

| Area | Actual checks |
| --- | --- |
| Setup | Three doctors, six future slots, exact patient columns without phone; schema reimport adds no duplicates |
| HTML | All four .html pages return HTTP 200 and include shared script.js |
| Registration | Valid patient saved; password hash verified; empty name, invalid email, short/empty password and mismatched confirmation rejected; duplicate email rejected including uppercase variation; no invalid/duplicate rows saved |
| Login | Wrong password and SQL injection input rejected; correct credentials accepted; response points to doctors.html; session ID regenerated; signed-in session retrieved |
| Doctors | API returns actual MySQL doctor names and available slots; selected slot details returned; booked slot removed and cannot be loaded for new confirmation |
| Booking | Success JSON and reservation number returned; database row belongs to session patient; forged patient_id ignored; repeated and second-patient booking rejected; past, missing and malformed slot IDs rejected |
| Database rules | Exactly one booking remains after repeated attempts; direct duplicate INSERT rejected by UNIQUE(slot_id); nonexistent patient rejected by foreign key |
| Session/CSRF | Token endpoint works; anonymous doctor/booking API requests blocked; GET booking blocked; missing/incorrect tokens blocked; logout removes authentication |

After cleanup: three doctors, six sample slots, zero test accounts and zero test
reservations. Temporary slots were also removed.

## Limits

- These were HTTP/API/database integration checks. Browser clicking, screenshots
  and Windows installation were not tested. A headless browser download was
  attempted but failed, so no browser test success is claimed.
- JavaScript syntax was checked with `node --check`; PHP files passed `php -l`.
- Duplicate HTTP requests were sequential; this was not a concurrency/load test.
- The code's frontend Fetch rendering and textContent use were reviewed, but a
  real browser demonstration is still required by the team before submission.

## Team demonstration checklist (not claimed as executed)

1. Open register.html and register using the four required fields.
2. Verify the patient in MySQL and log in through login.html.
3. Verify doctors and six initial sample times in doctors.html.
4. Choose a slot; booking.html must display the correct doctor/date/time.
5. Confirm Booking; verify the success message and saved appointments row.
6. Return to doctors.html; the reserved slot must be absent.
7. Repeat the original booking request; verify the duplicate error and one row.
8. Test duplicate email, wrong password, empty fields and anonymous booking.
9. Capture genuine screenshots and test outcomes for the Sprint Review.
