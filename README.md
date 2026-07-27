# Crime Report and Tracking System

PHP + MySQL based crime reporting system with:

- Citizen registration and login
- Citizen report submission
- Admin review and officer assignment
- Officer investigation updates
- Citizen and officer evidence submission with images, audio, video, PDF, and documents
- Officer-only private case files per assigned case
- Criminal registry with photo, ID, height, notes, and repeat-offender history
- Case closure and notification tracking
- Logout and shared modern UI
- Forgot password and profile page
- Search, filter, and printable case detail view

## Main Pages

- `login.php`
- `register.php`
- `logout.php`
- `index.php`
- `submit_report.php`
- `admin_reports.php`
- `officer_portal.php`
- `profile.php`
- `forgot_password.php`
- `report_view.php`

## Database Files

- `database/schema.sql`
- `database/seed.sql`
- `database/add_evidences_table.sql`
- `database/add_case_management_features.sql`

## Setup

1. Import `database/schema.sql` into MySQL.
2. Import `database/seed.sql`.
3. If you already imported the old schema before evidence support was added, run `database/add_evidences_table.sql`.
4. Run `database/add_case_management_features.sql` to add officer private files, evidence metadata, and criminal registry tables.
5. Update `config/database.php` if your MySQL username or password is different.
6. Run the project from the folder:

```powershell
php -S localhost:8000
```

7. Open `http://localhost:8000/login.php`

## Demo Login

- Admin email: `admin@gmail.com`
- Admin password: `admin123`
- Officer email: `aisha@gmail.com`
- Officer password: `officer123`

## Notes

- New users registered from `register.php` are stored as `citizen`
- Admin-only page: `admin_reports.php`
- Officer-only page: `officer_portal.php`
- Citizens can login, submit reports, and track their own cases from the dashboard
