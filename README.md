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

This implementation is on `experiment/sprint1-prototype` only. No changes have
been merged into `main` or the team's feature branches. The team workflow above
is the normal process; the experiment has no pull request or merge.

### Scope and current team agreement

The four Sprint 1 functions are:

1. Create a patient account with input validation and a hashed password.
2. Log in with email and password using a PHP session.
3. View doctors and unbooked future times from MySQL.
4. Confirm and save an appointment, rejecting a repeated reservation.

The four HTML pages use JavaScript Fetch to call the plain PHP backend. Booking
success/failure appears on `booking.html`; a successful response includes the
reservation number. There is no additional My Appointments page, phone field,
rescheduling, cancellation, specialty search, admin dashboard or later-sprint work.
A small session endpoint supplies CSRF tokens, and logout ends the patient session.

The latest team instructions take precedence over earlier assumptions:

| Owner | Files |
| --- | --- |
| Ghaliah: four frontend pages and styling | `frontend/register.html`, `frontend/login.html`, `frontend/doctors.html`, `frontend/booking.html`, `frontend/style.css`, frontend form/display portions of `frontend/script.js` |
| Tala: MySQL setup and sample data | `database/schema.sql` |
| Aryam: database connection, registration, login, doctor data | `backend/db.php`, `backend/register.php`, `backend/login.php`, `backend/doctors.php` |
| Sarah: booking and integration/testing | `backend/book.php`, booking Fetch portions of `frontend/script.js`, `tests/integration.php` |
| Shared integration helpers | `backend/common.php`, `backend/session.php`, `backend/logout.php`, `backend/config.example.php`, `index.php` |

Database name: `hospital_reservation`. Tables and columns:

| Table | Columns |
| --- | --- |
| `patients` | `id`, `full_name`, `email`, `password_hash` |
| `doctors` | `id`, `name`, `specialty` |
| `slots` | `id`, `doctor_id`, `appointment_date`, `appointment_time` |
| `appointments` | `id`, `patient_id`, `slot_id`, `created_at` |

`patients.email` is unique. `appointments.slot_id` is unique. Foreign keys link
slots to doctors and appointments to patients/slots. A doctor's date/time is also
unique to avoid inserting the same availability twice. The booking handler uses
a transaction and locks the chosen future slot; duplicate-key errors roll back.
The signed-in patient's ID comes from the session, not submitted form data.

A fresh setup creates **three fictional doctors and six slots**, two slots per
doctor tomorrow in Riyadh time (UTC+3). Running `schema.sql` again does not clear
records, replace existing IDs or add duplicate sample slots. Once sample dates
expire, deliberately update those unbooked sample times in MySQL Workbench. The
file is initial setup, not a migration for an incompatible existing schema.

Registration accepts full name, valid email, password and confirmation. Email is
normalized to lower case. The server checks a nonempty name of at most 120 UTF-8
bytes and matching passwords of 8–72 bytes. Plain English password characters
keep the browser character limits and PHP byte limits consistent.

### Review of project sources

At initial inspection on 12 October 2026, the connected repository contained
README files and `.gitignore`, with no existing application files. The current
experiment therefore did not overwrite team HTML/PHP/SQL files. The original
four folder README files are unchanged.

The attached Sprint Backlog goal agrees with the four functions above. The
Part 2 assignment asks for working software and seven Scrum deliverables; it
does not set a sample doctor/slot count. The supplied planning/review documents
are templates and the burn chart reference provides examples, not a requirement
to add frameworks or deployment infrastructure.

**Documentation discrepancy:** the attached `Sprint-Backlog(2).xlsx` still assigns
patient database structure to Aryam, registration implementation to Sarah, login
and doctor frontend work to Tala, doctor data structure to Ghaliah, and saving
appointments to Tala. Those assignments differ from the latest role instructions.
The code follows the latest instructions. No spreadsheet/document was changed,
no hours/statuses were invented, and no Scrum meeting records were fabricated.

### Run on Windows

Use PHP 8.2+ and MySQL 8.0+. Actual testing used PHP 8.3.6 and MySQL 8.0.46 on Linux;
Windows setup and browser interaction have not been executed in this session.

1. Install/start MySQL Community Server and its command-line client from
   [MySQL Downloads](https://dev.mysql.com/downloads/mysql/). Record your local
   username/password and port (normally 3306).
2. Download PHP from [PHP Windows Downloads](https://windows.php.net/download/),
   extract it to `C:\php`, and follow that page's runtime requirements. Copy
   `php.ini-development` to `php.ini`; set `extension_dir = "ext"` and enable
   `extension=pdo_mysql`. If PHP is already installed, use that installation.
3. In GitHub select **experiment/sprint1-prototype**, then **Code → Download ZIP**.
   Extract into `C:\projects\hospital-reservation-system`. Alternatively:

   ```powershell
   git clone --branch experiment/sprint1-prototype https://github.com/ThisIsSara/hospital-reservation-system.git
   ```

   With an existing local clone, preserve uncommitted team work before switching
   branches. Do not reset or delete it.
4. In MySQL Workbench open `database/schema.sql` and execute it. Alternatively,
   use the MySQL command-line client:

   ```sql
   SOURCE C:/projects/hospital-reservation-system/database/schema.sql;
   ```

   This single file includes the tables AND sample data. It contains no DROP,
   DELETE or TRUNCATE. If `hospital_reservation` already exists, inspect its schema
   first: `CREATE TABLE IF NOT EXISTS` will not convert incompatible old tables.
   The earlier experiment database `hospital_sprint1_prototype` is not altered.
5. Copy `backend/config.example.php` to `backend/config.local.php` and enter your
   local MySQL credentials. Keep the database name `hospital_reservation`.
   `config.local.php` is ignored by Git; never upload it. A local MySQL user with
   SELECT/INSERT/UPDATE/DELETE on this database can run the app. Schema creation
   requires a MySQL account allowed to create the database/tables.
6. Open PowerShell in the project folder:

   ```powershell
   C:\php\php.exe -m
   C:\php\php.exe -S 127.0.0.1:8000 -t .
   ```

   Confirm PDO and pdo_mysql appear in the module list; keep the server running.
7. Open [Registration](http://127.0.0.1:8000/frontend/register.html), create an
   account, follow the login link, and log in. Doctors/times load from MySQL.
8. Click **Book Appointment**, then **Confirm Booking**. The selected `slot_id`
   comes from the URL and is checked by PHP. The booking result appears in the
   page. Returning to Doctors & Times removes the booked time from availability.

JavaScript must be enabled. Opening HTML through `file://` or a static Live Server
will not run PHP. Use the PHP server above, or an existing local PHP HTTP server.

### Tests

With the server running, use a second terminal:

```powershell
C:\php\php.exe tests\integration.php http://127.0.0.1:8000
```

Use a freshly seeded local database. PHP's `allow_url_fopen` must be enabled.
The script uses PHP and MySQL only and queries actual database rows. It creates
uniquely named test accounts and temporary slots, then removes only those rows
in its cleanup step. See [the actual test record](docs/sprint-1/TESTING.md).

Security basics: password_hash/password_verify, prepared statements, PHP input
validation, CSRF tokens, session ID regeneration, HttpOnly/SameSite cookies,
no-store API responses, session patient identification, database constraints and
server-side error logging. JavaScript inserts database text using `textContent`.
Secure session cookies are enabled when the server request uses HTTPS.

### Changes from the earlier experiment

The experiment now uses the exact team database/column names, four HTML pages,
`backend/db.php` and `backend/book.php`. Schema and seed are combined in
`database/schema.sql`. The earlier experiment's PHP-rendered frontend, extra
appointments page, `backend/database.php`, `backend/booking.php`,
`backend/appointments.php` and separate `database/seed.sql` were replaced/removed.
These were files created by the experiment, not files supplied by the team;
their old contents remain in the earlier experiment commit. No team file or
branch was deleted. Root README, `.gitignore`, application files and test record
are the only changed/added materials; uploaded Scrum templates are untouched.

Official references:
[PHP password_hash](https://www.php.net/manual/en/function.password-hash.php),
[PHP PDO prepared statements](https://www.php.net/manual/en/pdo.prepared-statements.php),
[PHP development server](https://www.php.net/manual/en/features.commandline.webserver.php),
[MySQL locking reads](https://dev.mysql.com/doc/refman/8.0/en/innodb-locking-reads.html).
