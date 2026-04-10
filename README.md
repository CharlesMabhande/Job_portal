# Lupane State University — Online Job Application Portal (PHP 8 + MySQL)

This project is a **role-based** job application portal for the university:

- **Branding:** place the official logo at `assets/img/lupane-logo.png` (navbar, login, footer, and favicon use `SITE_LOGO_URL` in `config/config.php`).

- **Candidate**: profile with CV and qualifications document, apply to jobs (documents reused from profile), track application status
- **HR**: create/edit jobs, review applications, view CV and certificates inline or download, schedule and manage interviews
- **Management**: approve job postings before they go live
- **SysAdmin**: user management, system settings, audit logs

## Application deadlines (public visibility)

- Jobs must be **Active** to appear on the public list (`index.php`).
- If a job has an **`application_deadline`**, it is shown to candidates only while **today’s date (server/MySQL `CURDATE()`) is on or before that deadline**. After that, it **disappears** from the homepage, job detail URL, and the public jobs API list; candidates cannot apply (form POST, `job.php`, and `api/applications.php` enforce this).
- If **`application_deadline` is NULL**, the job stays visible until status changes (no automatic hide by date).
- **HR / Management / Admin** dashboards still list jobs by status; deadline filtering applies to **public** discovery and applying, not to internal editing screens.

**Timezone:** PHP uses the timezone set in `config/config.php` (default UTC). MySQL `CURDATE()` uses the database connection’s timezone. For consistent “midnight” behavior in Zimbabwe, align PHP and MySQL time zones with your policy.

## Setup (XAMPP / Windows)

### 1) Database

1. Create a database named `job_portal` (or match the name inside your dump).
2. Import:
   - `database/job_portal.sql`

If your dump does not include a SysAdmin user, create one in the database or register and promote the account via SQL. Older split installs used `database/schema.sql` plus `database/seed_sysadmin.sql`; those files are no longer in this repo.

**Upgrading an older database?** If you see errors about unknown column `vacancy_scope`, run once:

- `database/patches/add_vacancy_scope.sql`

If you see errors about unknown column `application_ref` (application tracking numbers), run once:

- `database/patches/add_application_ref.sql`

### 2) Configure app

Edit:

- `config/database.php` (DB credentials)
- `config/config.php` (BASE_URL, **`SITE_CONTACT_*`** constants for address / phone / fax / email + SMTP)

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
- Change password (logged-in users): `/change_password.php`
- Candidate dashboard: `/candidate/dashboard.php`
- HR dashboard: `/hr/dashboard.php`
- Management dashboard: `/management/dashboard.php`
- SysAdmin dashboard: `/admin/dashboard.php`

## JSON API (selected)

Used by parts of the UI and for integrations:

- `api/jobs.php` — list/search jobs (`action=list`, default `status=Active` respects application deadlines), job detail (`action=get`)
- `api/applications.php` — candidate apply and role-scoped application lists (requires session / CSRF where applicable)

## Default roles

Roles are defined in the database (typically created when you import `job_portal.sql`):

- Candidate (1), HR (2), Management (3), SysAdmin (4)

Use SysAdmin pages to assign roles:

- `/admin/users.php`

## Initial SysAdmin login

Credentials depend on what is inside `job_portal.sql`. If your dump includes the old seed user, it may be:

- **Email**: `admin@university.edu`
- **Password**: `Admin@12345`

Change it immediately via `/change_password.php` while logged in, or update the account in the database.
