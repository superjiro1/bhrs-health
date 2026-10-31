# Barangay Health Record System (BHRS)

PHP 8 + MySQL + Bootstrap 5, built for Laragon.

## Setup (Laragon, Windows)
1. Open Laragon and click **Start All** (Apache + MySQL must both be green).
2. Copy this folder to `C:\laragon\www\barangay-health-record` (so `index.php` is directly inside it).
3. Open `http://localhost/phpmyadmin`. Log in with user `root` and the password you use for Laragon MySQL (Laragon's default is an empty password - check yours under Laragon > Menu > MySQL).
4. In phpMyAdmin create a database named `barangay_health_db`, select it, then click **Import**, choose `database/database.sql`, and click **Import**. The SQL file recreates its tables and adds clearly fictional sample residents inside the selected database.
5. Open `config/db.php` in VS Code and set `user` / `pass` to match step 3 (`port` 3306 unless you changed it).
6. Browse to `http://localhost/barangay-health-record/`. Because no accounts exist yet you are sent to **First-time setup**: create your administrator (password >= 10 characters, letters + numbers). That page locks itself once an account exists, so there is no default password.
7. Sign in. Add health workers under **User Management**.

## Setup (shared hosting)
1. In InfinityFree's Control Panel, open **MySQL Databases** and create a database. Use the database hostname, database name, username, and password shown there; the hostname is not necessarily `localhost`, and the database name and username may include your account prefix.
2. Open that database in phpMyAdmin and select it before importing `database/database.sql`. The SQL file does not create or select a database, so it works with hosts that restrict `CREATE DATABASE` and with provider-assigned database names. **Importing drops and recreates the application's tables, so do not import it again after storing real records.**
3. Edit the uploaded `config/db.php` with the host, database name, username, and password from InfinityFree. The checked-in `127.0.0.1` / `root` / empty-password values are for local Laragon only.
4. Browse to your site and complete the first-time administrator setup.

Internet is needed on first load for Bootstrap, Bootstrap Icons and Chart.js (loaded from the jsDelivr CDN). To run fully offline, place the files in `assets/vendor/` (`bootstrap/bootstrap.min.css`, `bootstrap/bootstrap.bundle.min.js`, `bootstrap-icons/bootstrap-icons.min.css` + `bootstrap-icons/fonts/`, `chartjs/chart.umd.js`); the app uses local copies automatically when present.

## Roles
- **Administrator**: everything, plus User Management, Activity Logs, archiving/restoring residents.
- **Health Worker**: residents (register/edit), health records, consultations, immunizations, maternal & child, reports.

## Troubleshooting
- *"Database connection failed"*: MySQL not started, wrong `config/db.php` credentials, or `database.sql` not imported.
- *Blank page / 500*: check Laragon > Menu > Apache > Logs > error.log. PHP 8.0+ is required (Laragon > Menu > PHP > Version).
- *Page has no styling / links go to wrong place*: the folder must be named `barangay-health-record` under `www`; open it via `http://localhost/barangay-health-record/`.
- *No icons or charts*: no internet access to the CDN - see offline note above.
- *Forgot admin password*: in phpMyAdmin delete the row(s) in `users` only if you accept losing log attribution, then reopen the site to run setup again; or set a new hash from `php -r "echo password_hash('NewPass123456', PASSWORD_DEFAULT);"`.

## Notes
Sample data is fictional; delete the sample section of `database.sql` before real use. Records are archived, never deleted. Activity logs store actions only, never diagnoses or other medical details.
