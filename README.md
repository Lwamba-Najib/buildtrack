# BuildTrack - Inventory & Sales Management System

## Project Overview
BuildTrack is a full-stack inventory and sales management application 
built with Laravel and Vue.js.

## Technologies Used
- **Backend:** Laravel 10.x (PHP 8.3.30)
- **Frontend:** Vue.js 3.x with Vite
- **Database:** MySQL 8.0 (via MAMP)
- **Authentication:** Laravel Sanctum

---

## Setup Instructions

### Prerequisites
- **MAMP** (with PHP 8.3.30 and MySQL)
- **Node.js** (v22.14.0 or higher)
- **Composer** (PHP package manager)

---

### 1. Backend Setup

```bash
cd backend
composer install
cp .env.example .env
```

Update `.env` with your database credentials (MAMP uses port 8889):
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=buildtrack
DB_USERNAME=root
DB_PASSWORD=root
```

```bash
php artisan key:generate
php artisan migrate
php artisan serve
```

Backend runs on: `http://localhost:8000`

---

### 2. Frontend Setup

```bash
cd frontend
npm install
npm run dev
```

Frontend runs on: `http://localhost:5173`

---

### 3. Default Login Credentials

| Field | Value |
|-------|-------|
| **Email** | `admin@example.com` |
| **Password** | `password` |

---

## Environment Configuration

### MAMP Settings
- **PHP Version:** 8.3.30
- **MySQL Port:** 8889
- **Web Server:** Apache

### Key Environment Variables (`.env`)
```
APP_URL=http://localhost:8000
SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173
```

---

## Troubleshooting

### Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| **PHP extensions missing** | Use MAMP's PHP instead of system PHP |
| **Database connection failed** | Check `.env` DB_PORT=8889 |
| **CSRF token error** | Ensure frontend axios baseURL is correct |
| **CORS error** | Verify `SANCTUM_STATEFUL_DOMAINS` in `.env` |

### Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
```

---

## Project Status

| Component | Status | URL |
|-----------|--------|-----|
| **Backend (Laravel)** | ✅ Running | http://localhost:8000 |
| **Frontend (Vue.js)** | ✅ Running | http://localhost:5173 |
| **Database (MySQL)** | ✅ Connected | Port 8889 |
| **API Login** | ✅ Working (curl test passed) |
| **Browser Login** | ⚠️ In Progress |

---

## What I Did to Get This Running

1. Identified tech stack (Laravel + Vue.js)
2. Configured MAMP for PHP 8.3.30 and MySQL
3. Installed Composer and Laravel dependencies
4. Set up database with migrations and seeders
5. Created test user using Laravel Tinker
6. Inserted email and security settings
7. Fixed frontend-backend CSRF communication
8. Configured CORS and Sanctum settings
9. Pushed everything to GitHub


## Links

- **GitHub Repository:** https://github.com/Lwamba-Najib/buildtrack


## Author

**Lwamba Najib**  
Diploma in Computer Science

