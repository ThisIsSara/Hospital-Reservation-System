# Hospital Reservation System

CS388T - Scrum Course Project

## Project Overview
A web-based hospital appointment reservation system that allows patients to register, log in, view doctors and available appointment times, and book appointments.

## Sprint 1
October 7 - October 21, 2026

## Project Structure
- `frontend/` - User interface
- `backend/` - Application logic
- `database/` - Database scripts
- `docs/sprint-1/` - Scrum documentation

## Team Workflow
1. Work on your assigned feature branch.
2. Commit and push your changes.
3. Open a Pull Request to merge into main.
4. Review and test before merging.

## Technologies
HTML, CSS, JavaScript, plain PHP and MySQL. No framework or third-party application library.

## Experimental Sprint 1 Prototype

This implementation belongs to `experiment/sprint1-prototype` only. It has not
been merged into `main`. The team workflow above describes the general process;
do not merge this experiment without a separate team decision.

Implemented: patient account creation, patient login, doctors and available times
from MySQL, and saved appointment reservations with duplicate prevention.
The confirmation page and My Appointments page show the outcome of a reservation.
Logout is provided to end the patient session and test with another account.
No cancellation, admin dashboard, payments, reminders or later-sprint features are included.

### Repository review and assumptions

At inspection on 12 October 2026, `main` contained six text files: the root README,
`.gitignore`, and README files in `frontend`, `backend`, `database` and
`docs/sprint-1`. All four team feature branches pointed to the same earlier
commit and contained README files only. No HTML, PHP, SQL, requirements attachments
or `AGENTS.md` were present. There was no existing application code to replace.

The feature descriptions in `frontend/README.md` and `backend/README.md` match
the four requested functions. The root README previously left technologies
undecided; this experiment uses the explicitly approved PHP/MySQL stack.
`docs/sprint-1/README.md` lists Scrum deliverables and report responsibilities,
not detailed acceptance criteria. That file is unchanged; no meeting records or
Scrum reports have been fabricated.

For the missing detailed criteria, this prototype assumes:

- Registration needs full name, email, Saudi mobile number (`05` plus eight digits),
  password and password confirmation. Email is the unique login identifier and is
  normalized to lower case. Mobile numbers need not be unique.
- Names must be nonempty and at most 120 UTF-8 bytes; passwords must contain 8–72
  bytes and match the confirmation. Byte limits are checked by PHP, including with
  JavaScript disabled. No email verification or password reset is included.
- Patients must log in before viewing or reserving available times.
- Doctors and discrete available slots are maintained through SQL in Sprint 1.
  A slot represents one appointment. Times use Riyadh time (UTC+3).
- Past and occupied slots cannot be reserved. A patient cannot book two doctors at
  the same time. Database uniqueness constraints and a transaction protect these
  rules even when requests overlap.
- The prototype uses its own database, `hospital_sprint1_prototype`, and fictional
  doctors. It does not alter an existing team database.

### Run on Windows: PHP and MySQL

Use PHP 8.2 or later and MySQL 8.0 or later. The tests here used PHP 8.3.6 and
MySQL 8.0.46 on Linux. Windows instructions are provided below but have not been
executed on a Windows computer in this session.

1. Install MySQL Community Server and MySQL's command-line client from
   [MySQL Downloads](https://dev.mysql.com/downloads/mysql/). Start the MySQL Windows
   service and note its port, username and password. This guide assumes port 3306.
2. Download PHP for Windows from [PHP Windows Downloads](https://windows.php.net/download/)
   and extract it into `C:\php`. Follow the official page's runtime requirements.
   Copy `php.ini-development` to `php.ini`; set `extension_dir = "ext"` and enable
   `extension=pdo_mysql`. Ensure PHP's `allow_url_fopen` is enabled to run the tests.
   If PHP is already installed, use that installation instead.
3. In GitHub, choose `experiment/sprint1-prototype` before downloading **Code →
   Download ZIP**. Extract the project into `C:\projects\hospital-reservation-system`.
   Alternatively, from a terminal with Git access to the private repository:

   ```powershell
   git clone --branch experiment/sprint1-prototype https://github.com/ThisIsSara/hospital-reservation-system.git
   ```

   For an existing clean local clone, fetch and switch to the experiment branch.
   Preserve any uncommitted team work before switching. Never reset or delete it.

4. Open the MySQL command-line client and log in. Run:

   ```sql
   SOURCE C:/projects/hospital-reservation-system/database/schema.sql;
   SOURCE C:/projects/hospital-reservation-system/database/seed.sql;
   ```

   The scripts create four tables, four fictional doctors and 48 future slots.
   They do not drop tables or delete records. Reimporting the seed on the same day
   does not duplicate doctors or slots. Reimport it on a later date when new future
   slots are needed. The schema file is initial setup, not a schema migration tool.

5. Copy `backend/config.example.php` to `backend/config.local.php`. Enter your
   MySQL host, port, username and password. Keep database name
   `hospital_sprint1_prototype`. `config.local.php` is ignored by Git; do not upload
   credentials. Using local MySQL root simplifies initial setup; a dedicated local
   account with SELECT/INSERT/UPDATE/DELETE on this database is also supported.
6. Open PowerShell in the project folder and run:

   ```powershell
   C:\php\php.exe -m
   C:\php\php.exe -S 127.0.0.1:8000 -t .
   ```

   Confirm `PDO` and `pdo_mysql` appear in the module list. Keep this terminal open.
   Open [Patient Login](http://127.0.0.1:8000/frontend/login.php), create an account,
   log in, select a doctor/time and click **Confirm Booking**.
7. After a successful reservation, My Appointments shows a success message and
   the saved reservation. The reserved slot disappears from Doctors & Times.

If you already run PHP and an HTTP server locally, serve this project from that
server instead. MySQL must still be running and configured. Opening PHP files by
double-clicking or using a static Live Server does not execute PHP.

### Files and team ownership

| Responsibility | Files |
| --- | --- |
| Ghaliah — registration, login, doctors frontend | `frontend/register.php`, `frontend/login.php`, `frontend/doctors.php` |
| Tala — MySQL database | `database/schema.sql`, `database/seed.sql` |
| Aryam — registration, login, doctors backend | `backend/register.php`, `backend/login.php`, `backend/doctors.php` |
| Sarah — booking frontend/backend, integration and testing | `frontend/booking.php`, `frontend/appointments.php`, `backend/booking.php`, `backend/appointments.php`, `tests/integration.php` |
| Shared integration files | `index.php`, `backend/common.php`, `backend/database.php`, `backend/config.example.php`, `backend/logout.php`, `frontend/header.php`, `frontend/footer.php`, `frontend/style.css`, `frontend/script.js` |

Modified original files: `README.md` (this guide) and `.gitignore` (ignore local
database credentials). Every application, SQL and test file above is newly added.
`docs/sprint-1/TESTING.md` is the new test record. The four original folder README
files, existing team branches and `main` remain unchanged.

The frontend pages include shared PHP helpers, render HTML forms and send POST
requests to the backend. PHP functions for doctor/appointment queries live in
`backend`, making frontend and backend responsibilities easy to separate.

### Security and tests

The prototype uses password hashing/verification, PDO prepared statements,
server-side validation, escaped HTML output, CSRF tokens, session ID regeneration,
HttpOnly/SameSite cookies, session-based patient identification and database
constraints. Database errors are logged instead of displayed to patients.
PHP enables Secure session cookies when the request uses HTTPS.
This is a local university demonstration, not a production deployment.

With the server running, open a second PowerShell terminal in the project folder:

```powershell
C:\php\php.exe tests\integration.php http://127.0.0.1:8000
```

The script uses PHP and MySQL only, creates temporary test accounts/slots and
removes them in its cleanup step. It refuses a database name other than
`hospital_sprint1_prototype` and only accepts local HTTP URLs. Run it against a
freshly seeded local prototype. See [the actual test record](docs/sprint-1/TESTING.md).

Official implementation references:
[PHP password_hash](https://www.php.net/manual/en/function.password-hash.php),
[PHP PDO prepared statements](https://www.php.net/manual/en/pdo.prepared-statements.php),
[PHP development server](https://www.php.net/manual/en/features.commandline.webserver.php),
[MySQL InnoDB locking reads](https://dev.mysql.com/doc/refman/8.0/en/innodb-locking-reads.html).
