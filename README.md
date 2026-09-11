# Sponsorship Web Application

Change a Life. Sponsor a Future. A production-ready PHP application for managing child sponsorships.

## 1. Requirements
- PHP 8.0 or higher
- MySQL / MariaDB
- Apache Web Server (XAMPP recommended)

## 2. Installation (XAMPP)
1. Copy the entire `sponsor-app` folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL from the XAMPP Control Panel.

## 3. Database Setup
1. Open phpMyAdmin (`http://localhost/phpmyadmin`).
2. Create a database named `sponsor_app`.
3. Import the `database/sponsor_app.sql` file provided in this repository.

## 4. Configuration
Review and edit `config/database.php` and `config/config.php` if your local environment settings differ from the defaults (e.g. database password).

## 5. How to run
Visit `http://localhost/sponsor-app/` in your browser.

## 6. Default Accounts
- **Admin**: email: `admin@sponsor.local` | password: `Admin123!`

*(Register manually for Sponsor and Organization roles using the web interface)*

## 7. Project Structure
The project uses vanilla PHP 8, following a modular structure separating assets, configurations, includes, and role-based sections (admin, organization, sponsor).

## 8. Payment Integration
Payments use a sandbox layer by default. Implement real API calls inside the transaction handling logic once credentials for Stripe/MTN/Airtel are available.

## 9. Security
- PDO prepared statements
- CSRF Protection
- Input sanitization & output escaping
- Secure password hashing (Bcrypt)

## 10. Deployment
When deploying to a live server, ensure `UPLOAD_DIR` has correct write permissions, and disable displaying errors in `php.ini`.
