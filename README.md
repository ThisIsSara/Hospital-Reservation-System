# Hospital Reservation System

A CS388T university project for patient registration, login and hospital
appointment booking. Sprint 1 runs from 7 to 21 October 2026.

## Technologies

HTML, CSS, JavaScript, plain PHP and MySQL. No frameworks or application libraries.
Requires PHP 8.2+ with PDO/pdo_mysql, MySQL 8.0+ and a browser with JavaScript enabled.

## Sprint 1

1. Create a patient account with input validation and a hashed password.
2. Log in using email and password.
3. View doctors and available appointment times from MySQL.
4. Book an appointment and prevent duplicate reservations.

Registration uses full name, email, password and password confirmation. Booking
shows a success or error message on the confirmation page.

## Project structure

| Location | Contents |
| --- | --- |
| `frontend/` | `register.html`, `login.html`, `doctors.html`, `booking.html`, `style.css`, `script.js` |
| `backend/` | `db.php`, `register.php`, `login.php`, `doctors.php`, `book.php`, session helpers and example configuration |
| `database/schema.sql` | Database, four tables and sample data |
| `docs/sprint-1/` | Scrum documentation |
| `index.php` | Redirect to the login page |

Database: `hospital_reservation`. Tables: `patients`, `doctors`, `slots`,
`appointments`. A fresh setup includes three fictional doctors and six future
slots. Unique email and slot constraints prevent duplicate accounts and bookings.
The backend uses prepared statements, password hashing, CSRF tokens and PHP sessions.

## Windows setup

1. Install and start [MySQL Community Server](https://dev.mysql.com/downloads/mysql/).
   Keep your local username, password and port (normally 3306).
2. Download [PHP for Windows](https://windows.php.net/download/) into `C:\php` and
   follow its runtime requirements. Copy `php.ini-development` to `php.ini`, set
   `extension_dir = "ext"` and enable `extension=pdo_mysql`.
3. Download the `experiment/sprint1-prototype` branch ZIP from GitHub and extract
   it to `C:\projects\hospital-reservation-system`, or clone it:

   ```powershell
   git clone --branch experiment/sprint1-prototype https://github.com/ThisIsSara/hospital-reservation-system.git
   ```

4. Open `database/schema.sql` in MySQL Workbench and execute it. Alternatively,
   run this in the MySQL command-line client:

   ```sql
   SOURCE C:/projects/hospital-reservation-system/database/schema.sql;
   ```

5. Copy `backend/config.example.php` to `backend/config.local.php` and enter your
   MySQL credentials. Use database `hospital_reservation`. The local configuration
   is ignored by Git; keep credentials out of the repository.
6. Open PowerShell in the project folder and run:

   ```powershell
   C:\php\php.exe -m
   C:\php\php.exe -S 127.0.0.1:8000 -t .
   ```

   Confirm that `PDO` and `pdo_mysql` appear in the module list. Keep the server open.
7. Visit [Registration](http://127.0.0.1:8000/frontend/register.html), create an
   account, log in, choose a time and click **Confirm Booking**.

Use the PHP server rather than opening HTML files directly or through a static
Live Server. Reimporting the SQL keeps existing records; it does not migrate an
incompatible schema. If sample dates have expired, update unbooked times in
Workbench before the demonstration.
