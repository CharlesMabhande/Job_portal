# Lupane State University — Job Portal (PHP 8 + MySQL)

Role-based university recruitment system built with procedural PHP and MySQL.

## Core capabilities

- **Candidate**
  - Create and maintain profile (education, experience, qualifications)
  - Upload CV and supporting documents
  - Browse active jobs and apply
  - Track application status and view interview details (date/time, type, duration, location/link)
- **HR**
  - Create/edit jobs
  - Review applications and manage status pipeline
  - Schedule interviews (single candidate and bulk by position)
  - Update/delete interviews and notify candidates
- **Management**
  - Approve job postings before publishing
  - Review published jobs and summary views
- **SysAdmin**
  - Create users directly in the system and assign roles
  - Manage user roles/status, reset passwords, and delete candidate accounts
  - View audit logs and update system settings

## Tech stack

- PHP 8 (XAMPP-friendly)
- MySQL / MariaDB (PDO)
- Bootstrap + custom styling
- PHPMailer (optional, for SMTP email delivery)

## Project structure

- `admin/`, `hr/`, `management/`, `candidate/` - role-based UI modules
- `api/` - JSON endpoints
- `includes/` - shared auth, security, helpers, and email functions
- `config/` - app/bootstrap and DB configuration
- `database/job_portal.sql` - full schema + seed data

## Setup (XAMPP / Windows)

### 1) Database

1. Create a database named `job_portal` (or use the name inside the dump).
2. Import:
   - `database/job_portal.sql`

### 2) Configure application

Update:

- `config/database.php` - database host/name/user/password
- `config/config.php` - `BASE_URL`, SMTP settings, site contact details, branding constants

Typical local base URL:

- `http://localhost/Job_portal`

### 3) Install dependencies (optional but recommended)

```bash
composer install
```

If composer packages are not installed, email sending is safely suppressed (no fatal crash).

### 4) Run

- Public jobs list: `/index.php`
- Login: `/login.php`
- Candidate dashboard: `/candidate/dashboard.php`
- HR dashboard: `/hr/dashboard.php`
- Management dashboard: `/management/dashboard.php`
- SysAdmin dashboard: `/admin/dashboard.php`

## Roles

Default role mapping in DB:

- Candidate (`role_id = 1`)
- HR (`role_id = 2`)
- Management (`role_id = 3`)
- SysAdmin (`role_id = 4`)

User and role administration:

- `/admin/users.php`

## Job visibility and deadlines

- Only jobs with status `Active` are shown publicly.
- If `application_deadline` is set, a job is visible/applicable only up to that date.
- If deadline is `NULL`, visibility depends on job status only.
- Internal role dashboards can still view/manage jobs beyond public deadline filtering.

## Interview scheduling

- Single interview scheduling is available in `/hr/interviews.php`.
- Bulk interview scheduler can schedule all eligible applicants for one position in one action.
- Duration input accepts **hours + minutes** and is stored as total minutes in DB (`duration_minutes`).

## Selected API endpoints

- `api/auth.php` - login/register/session actions
- `api/jobs.php` - list/get/create/update/delete/approve jobs (role-protected by action)
- `api/applications.php` - apply/list/update status
- `api/interviews.php` - schedule and list interview records

## Security and auditing

- Role guards via `requireRole(...)`
- CSRF protection for POST requests
- Password hashing with bcrypt
- Audit trail in `audit_logs` for key system actions

## Branding

Place the official logo at:

- `assets/img/lupane-logo.png`

Logo usage is controlled through branding constants in `config/config.php` (for navbar, login, footer, favicon).
