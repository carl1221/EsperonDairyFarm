# Esperon Dairy Farm — Management System

A full-stack PHP + MySQL management system for Esperon Dairy Farm, built to run locally on **XAMPP**.

---

## 📁 Project Structure

```
esperon/
├── dairy_farm_backend/          ← PHP REST API
│   ├── api/
│   │   ├── auth.php             ← Login / logout / session status
│   │   ├── signup.php           ← New account registration
│   │   ├── cows.php
│   │   ├── customers.php
│   │   ├── orders.php
│   │   └── workers.php
│   ├── config/
│   │   ├── bootstrap.php        ← CORS, session, shared helpers
│   │   ├── database.php         ← PDO singleton connection
│   │   └── response.php        ← JSON response helpers
│   └── models/
│       ├── Cow.php
│       ├── Customer.php
│       ├── Order.php
│       └── Worker.php
│
├── UI/                          ← HTML / CSS / JS frontend
│   ├── login.php                ← Login page
│   ├── signup.php               ← Registration page
│   ├── index.php                ← Dashboard (shows user name)
│   ├── cows.php
│   ├── customers.php
│   ├── orders.php
│   ├── workers.php
│   ├── assets/
│   ├── css/style.css
│   └── js/
│       ├── api.js               ← All API calls
│       ├── nav.js               ← Sidebar + user profile card
│       └── ui.js                ← Toast / modal / utilities
│
├── migrations/                  ← SQL migration files (run with php migrate.php)
│   └── 001_initial_schema.sql   ← Full schema, views, indexes, and seed data
├── migrate.php                  ← Migration runner (CLI only)
├── .env                         ← Database + OAuth credentials (never commit)
├── db.sql                       ← Original schema file (superseded by migrations/)
```

---

## ⚙️ Setup (XAMPP)

### 1. Place project files
Copy the entire `esperon_final` folder into your XAMPP `htdocs` directory:

```
C:\xampp\htdocs\esperon_final\
```

### 2. Set up the database

Run the migration script from the project root:

```bash
php migrate.php
```

This reads every `.sql` file in `migrations/` in order, skips any that have already run, and records each successful migration in a `migrations` tracking table. It is safe to run multiple times.

To add a future schema change, create a new numbered file in `migrations/` (e.g. `003_add_new_table.sql`) and run `php migrate.php` again — only the new file will be applied.

### 3. Configure `.env`
Edit `esperon_final/.env` if your MySQL credentials differ:
```
DB_HOST=localhost
DB_NAME=esperon_dairy_farm
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

### 4. Install PHP dependencies
Run Composer to install dependencies:
```bash
composer install
```

### 5. Serve the application
The backend PHP files are served by Apache at **http://localhost/esperon_final/dairy_farm_backend/api/**.

Open **http://localhost/esperon_final/UI/login.php** in your browser to access the application.

> If your setup differs, update `API_BASE` in `UI/js/api.js`.

### 6. (Optional) Set up Google OAuth Login

**Current Status:** Google OAuth is disabled by default. Once you add your Google credentials to `.env`, it will work automatically.

**Good news:** You don't need to install Composer or any external PHP libraries! Google OAuth is implemented using native PHP/cURL, so it works out of the box once configured.

#### Setup Steps

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google+ API:
   - Go to "APIs & Services" > "Library"
   - Search for "Google+ API" and enable it
4. Create OAuth 2.0 credentials:
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "OAuth 2.0 Client IDs"
   - Choose "Web application"
   - Set authorized redirect URIs to: `http://localhost/esperon_final/dairy_farm_backend/api/auth.php?action=google_callback`
   - Copy the Client ID and Client Secret

5. Update your `.env` file with the real credentials and your redirect URI:
   ```
   GOOGLE_CLIENT_ID=your_actual_client_id_here
   GOOGLE_CLIENT_SECRET=your_actual_client_secret_here
   GOOGLE_REDIRECT_URI=http://localhost/EsperonDairyFarm/dairy_farm_backend/api/auth.php?action=google_callback
   ```
   > **Note:** `GOOGLE_REDIRECT_URI` must exactly match the URI you registered in the Google Cloud Console. Update it to match your local path if it differs.

Users can now log in with their Google account if their email matches a worker's email in the database.

Users can now log in with their Google account. If their email doesn't exist in the system, a new account will be automatically created with "Staff" role.

**Testing Google OAuth:**
- Click "Continue with Google" on the login page
- You should be redirected to Google for authentication
- After authentication, you'll be redirected back
- If your Google email matches an existing worker → Login with existing account
- If your Google email is new → Auto-create new "Staff" account and login
- Username is automatically generated from your Google name

**Current Worker Emails in Database:**
- Mark: No email set
- Carl: No email set  
- Ian: Ian@gmail.com
- kurt: kurt@gmail.com

**Note:** The application works perfectly without Google OAuth. Username/password login is always available.

---

## 🔐 Default Login Credentials (seed data)

| Username | Password | Role |
|---|---|---|
| Mark | password | Staff |
| Carl | password | Admin |

> Change these immediately after first login.

---

## ✅ Features

- **Signup** — username, email, password (hashed with bcrypt), role selection
- **Login** — `password_verify`, PHP session, CSRF token issued on login
- **Dashboard** — personalised greeting ("Good morning, Mark! 👋"), stats, recent orders
- **Sidebar** — shows logged-in user's name, role badge, and email
- **Logout** — destroys server session + clears localStorage
- All database queries use **PDO prepared statements** — no SQL injection possible
- Passwords are never returned in API responses

---

## 🧪 Running the Tests

The test suite makes real HTTP calls to the local XAMPP server, so Apache and MySQL must be running before you execute it.

### Prerequisites

```bash
# Install dev dependencies (PHPUnit) if you haven't already
composer install
```

### Run all tests

```bash
composer test
```

### Run a single test file

```bash
vendor/bin/phpunit tests/AuthTest.php
vendor/bin/phpunit tests/SignupTest.php
vendor/bin/phpunit tests/ApprovalTest.php
```

### Base URL

By default the tests hit `http://localhost/EsperonDairyFarm`.
If your XAMPP project lives at a different path, override the variable:

```bash
APP_BASE_URL=http://localhost/my-path composer test
```

Or edit the `<env>` value in `phpunit.xml`.

### What the tests cover

| File | Endpoint | Scenarios |
|---|---|---|
| `tests/AuthTest.php` | `POST /api/auth.php?action=login` | Valid login (200 + user object), wrong password (401), pending account (403), rejected account (403), 6th failed attempt (429) |
| `tests/SignupTest.php` | `POST /api/signup.php` | Valid signup (201 + pending status in DB), duplicate username (409), missing username / password / email (400) |
| `tests/ApprovalTest.php` | `GET/POST /api/approval.php` | Non-admin blocked (403), admin approves pending worker (200 + DB verified), admin rejects pending worker (200 + DB verified) |

### Notes

- Tests use native PHP cURL — no Guzzle or other HTTP client required.
- Each test class gets its own isolated cookie jar so sessions don't bleed between tests.
- Temporary Worker rows created during tests are deleted in `tearDown` — the database is left clean after every run.
- The reCAPTCHA check is automatically bypassed on `localhost` (non-HTTPS) by `verifyRecaptcha()` in `bootstrap.php`.

---

## 🔒 Security Notes

- Passwords are stored using `password_hash($pw, PASSWORD_DEFAULT)` (bcrypt, cost 10)
- CSRF tokens are required on all `POST / PUT / DELETE` requests
- Session is regenerated on login to prevent session fixation
- Database error details are logged internally and never exposed to clients
- Email uniqueness is enforced at both application and database level
