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

**Current Status:** Google OAuth is disabled by default. The "Continue with Google" button will show an error message until properly configured.

To enable "Continue with Google" login:

#### Option A: Using Composer (Recommended)
1. Install PHP dependencies:
   ```bash
   composer install
   ```
   If you encounter SSL certificate errors, try:
   ```bash
   composer config disable-tls true
   composer install
   ```

#### Option B: Manual Setup (if Composer fails)
1. Download the Google API Client library manually from GitHub
2. Extract it to `vendor/google/apiclient/`
3. Ensure the autoload files are in place

#### Google Cloud Console Setup
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

#### Update Environment Variables
5. Update your `.env` file with the real credentials (replace the placeholder values):
   ```
   GOOGLE_CLIENT_ID=your_actual_client_id_here
   GOOGLE_CLIENT_SECRET=your_actual_client_secret_here
   ```

Users can now log in with their Google account if their email matches a worker's email in the database.

**Testing Google OAuth:**
- Click "Continue with Google" on the login page
- You should be redirected to Google for authentication
- After authentication, you'll be redirected back
- If your Google email matches a worker email in the database, you'll be logged in
- If not, you'll see an error message

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
