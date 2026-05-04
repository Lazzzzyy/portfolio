# Portfolio Auth + OTP Setup

This project now supports:

- database-backed login (no hardcoded credentials)
- Gmail OTP verification (6-digit, expires in 1 minute)
- resend OTP support after expiry
- SQL injection-safe DB queries via prepared statements

## 1) Terminal commands to initialize

Run these commands in PowerShell from project root (`c:\laragon\www\portfolio`):

```powershell
Copy-Item .env.example .env
composer require phpmailer/phpmailer
& "C:\laragon\bin\mysql\mysql-8.0.42-winx64\bin\mysql.exe" -u root -h 127.0.0.1 -P 3306 -e "source C:/laragon/www/portfolio/database/schema.sql"
```

If your MySQL root has password, use:

```powershell
& "C:\laragon\bin\mysql\mysql-8.0.42-winx64\bin\mysql.exe" -u root -p -h 127.0.0.1 -P 3306 -e "source C:/laragon/www/portfolio/database/schema.sql"
```

## 2) Configure `.env`

Edit `.env` and fill these values:

- `PORTFOLIO_DB_HOST`
- `PORTFOLIO_DB_PORT`
- `PORTFOLIO_DB_NAME`
- `PORTFOLIO_DB_USER`
- `PORTFOLIO_DB_PASS`
- `MAIL_USERNAME` (your Gmail)
- `MAIL_PASSWORD` (Google App Password, not normal Gmail password)
- `MAIL_FROM_ADDRESS`
- `MAIL_THEME` (`light` or `dark` for OTP email color palette)

OTP settings:

- OTP expiry is fixed at 60 seconds
- `OTP_MAX_ATTEMPTS=5`

## 3) Set your private email/password for login

Option A (browser setup, one-time):

- `http://localhost/portfolio/setup.php`

Create your admin credentials there. Only you decide the email/password.

Option B (terminal):

```powershell
php .\scripts\create-admin.php --name="Your Name" --email="you@gmail.com" --password="YourStrongPassword123!"
```

## 4) Login flow

1. Enter email + password on `index.html`
2. System sends OTP to Gmail
3. UI switches to OTP input fields
4. After 6th digit, verification runs automatically
5. Success redirects to `dashboard.php`

## 5) Security notes

- SQL injection mitigation: PDO prepared statements are used in API endpoints.
- Passwords are hashed (`password_hash` / `password_verify`).
- OTP codes are stored as SHA-256 hashes (not plaintext).
- OTP records have expiry, attempt counters, and are invalidated after use.

## Core files

- `config/database.php`
- `config/env.php`
- `config/mailer.php`
- `api/_bootstrap.php`
- `api/_otp.php`
- `api/login.php`
- `api/verify-otp.php`
- `api/resend-otp.php`
- `database/schema.sql`
