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
├── .env                         ← Database + OAuth credentials (never commit)
├── esperon_dairyfarm.sql        ← Full database schema + seed data
└── esperon_add_email.sql        ← Migration: adds Email column
```

---

## ⚙️ Setup (XAMPP)

### 1. Place project files
Copy the entire `esperon_final` folder into your XAMPP `htdocs` directory:

```
C:\xampp\htdocs\esperon_final\
```

### 2. Import the database

Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and run these two SQL files in order:

| Order | File | What it does |
|---|---|---|
| 1st | `esperon_dairyfarm.sql` | Creates the database, all tables, indexes, views, and seed data |
| 2nd | `esperon_add_email.sql` | Adds the `Email` column to the `Worker` table |

Or via terminal:
```bash
mysql -u root esperon_dairy_farm < esperon_dairyfarm.sql
mysql -u root esperon_dairy_farm < esperon_add_email.sql
```

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

## 🔒 Security Notes

- Passwords are stored using `password_hash($pw, PASSWORD_DEFAULT)` (bcrypt, cost 10)
- CSRF tokens are required on all `POST / PUT / DELETE` requests
- Session is regenerated on login to prevent session fixation
- Database error details are logged internally and never exposed to clients
- Email uniqueness is enforced at both application and database level
