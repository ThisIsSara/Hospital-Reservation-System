# Sprint 1 prototype: actual test record

Tested on 12 October 2026 using PHP 8.3.6, PDO MySQL and MySQL Community Server
8.0.46 on Linux. No mocked database or SQLite replacement was used. HTTP requests
were sent to the real PHP development server. Database assertions queried MySQL.

## Results

`tests/integration.php` completed with exit code 0: **41 checks passed**.

| Area | Actual checks |
| --- | --- |
| Setup | Doctors and future slots read from MySQL; four doctors and 48 slots remain after importing the seed twice |
| Registration | Valid account saved; hash verifies the password; empty name, invalid email/mobile, short password and mismatched confirmation rejected; invalid submissions create no account; duplicate email rejected, including uppercase variation |
| Login | Wrong password rejected; correct credentials accepted; SQL injection input does not authenticate; session ID regenerated |
| Doctors/times | Page displays database doctors and available slots; booked slot disappears |
| Booking | Confirmation form loads; valid POST saves the session patient's reservation and displays success after redirect; submitted patient_id is ignored |
| Duplicate prevention | Repeated POST rejected; another patient cannot book an occupied slot; same patient cannot book another doctor at the same time; exactly one reservation remains after invalid attempts |
| Invalid slots | Past, nonexistent and malformed slot IDs rejected |
| Access and security | Anonymous page and booking POST access blocked; GET cannot create reservations; missing/incorrect CSRF token rejected; patient name escaped; another patient's appointments cannot be read using a query parameter; logout removes access |

After cleanup, MySQL contained four doctors, 48 seeded slots, zero test patients
and zero test reservations. The temporary slots and accounts were removed.

All 18 committed PHP files passed `php -l` syntax checks at this stage.

## Limits of the result

- Windows installation instructions were written but were not executed on Windows.
- The checks used HTTP and database assertions, not manual browser interaction or
  a screenshot-based visual review.
- Duplicate attempts in the HTTP suite were sequential. Simultaneous-request
  behavior is guarded by a transaction, row lock and MySQL unique constraints;
  this suite does not constitute a concurrency/load test.
- No email verification, password reset or production hosting was tested because
  those are outside the requested Sprint 1 scope.

## Manual demonstration

1. Create an account and log in using its actual password.
2. Open Doctors & Times. Select a time and confirm the reservation.
3. Verify the success message and reservation on My Appointments.
4. Return to Doctors & Times and verify the booked slot is absent.
5. Submit the earlier confirmation form again: a duplicate error should appear.
6. Log out, create a second account and verify the first patient's reservation is
   neither available for booking nor visible under My Appointments.

This manual checklist is provided for the team; it is not claimed as executed.
