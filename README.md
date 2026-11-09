# Hospital Reservation System

CS388T project: a web application for patient appointment booking and hospital administration.

## Technologies
HTML, CSS, JavaScript, PHP, and MySQL.

## Features
- Patient registration and login
- Browse doctors, filter by specialty, and view available appointment times
- Book, view, reschedule, and cancel appointments
- Update patient profile
- Administrator login and management of specialties, doctors, appointment times, reservations, and patient accounts
- Appointment statistics

## Project files
- `frontend/` — pages, styles, and browser-side JavaScript
- `backend/` — PHP endpoints and database connection
- `database/schema.sql` — database tables, doctors, and sample appointment times
- `database/sprint2.sql` — administrator and specialty tables
- `database/create_admin.php` — local administrator account setup

## Run locally on Windows
1. Install MySQL 8.0+ and PHP 8.2+ with the `pdo_mysql` extension enabled.
2. In MySQL Workbench, run `database/schema.sql` and then `database/sprint2.sql`.
3. Copy `backend/config.example.php` to `backend/config.local.php` and fill in your local MySQL settings. Do not upload this file.
4. Open PowerShell in the project root and create an administrator account:
   ```powershell
   C:\php\php.exe database/create_admin.php
   ```
5. Start the application from the project root:
   ```powershell
   C:\php\php.exe -S 127.0.0.1:8000 -t .
   ```
6. Open `http://127.0.0.1:8000/frontend/login.html` for patients or `http://127.0.0.1:8000/frontend/admin-login.html` for administrators.

The included December 2026 sample appointment times are for demonstration. For a later demonstration, use future available times.

Passwords are stored as hashes. The local database credentials file is excluded through `.gitignore`.
