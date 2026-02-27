# University Online Job Application Portal (PHP 8 + MySQL)

This project is a **role-based** job application portal for a university:

- **Candidate**: profile + CV upload, apply to jobs, track application status
- **HR**: create jobs, review applications, schedule interviews
- **Management**: approve job postings
- **SysAdmin**: user management, system settings, audit logs

## Setup (XAMPP / Windows)

### 1) Database

1. Create a database named `job_portal`
2. Import:
   - `database/schema.sql`
3. Seed initial SysAdmin:
   - `database/seed_sysadmin.sql`

### 2) Configure app

Edit:
- `config/database.php` (DB credentials)
- `config/config.php` (BASE_URL + SMTP settings)

If your folder is `C:\xampp\htdocs\Job_portal`, your base URL is typically:
- `http://localhost/Job_portal`

### 3) (Optional) Install PHPMailer

From the project folder, run:

```bash
composer install
```

If you skip this step, emails will be **suppressed** (no fatal errors).

### 4) Visit

- Public jobs list: `/index.php`
- Login: `/login.php`
- Candidate dashboard: `/candidate/dashboard.php`
- HR dashboard: `/hr/dashboard.php`
- Management dashboard: `/management/dashboard.php`
- SysAdmin dashboard: `/admin/dashboard.php`

## Default roles

Roles are created by `schema.sql`:
- Candidate (1), HR (2), Management (3), SysAdmin (4)

Use SysAdmin pages to assign roles:
- `/admin/users.php`

## Initial SysAdmin login

After importing `database/seed_sysadmin.sql`:
- **Email**: `admin@university.edu`
- **Password**: `Admin@12345`

Change it immediately (recommended: build a password change page next).

