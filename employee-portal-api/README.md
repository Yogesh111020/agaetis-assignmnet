# Employee Portal API

Laravel 12 API for employee CRUD, role selection and active-employee lookup.
The React client is in [../employee-portal-web](../employee-portal-web/README.md).
No login, token, session cookie or CSRF setup is required. Anyone with API access can manage records.

## Requirements

- PHP 8.2+ and Composer 2; installed dependencies may require a higher PHP version. Run `composer check-platform-reqs` after installation.
- PHP extensions required by Composer, including PDO MySQL for MySQL.
- Running MySQL/MariaDB server and an empty database for a new installation.
- Node.js is not needed to serve this API; frontend dependencies belong in the React folder.

## First-time setup

From Git Bash on Windows:

```bash
cd /d/assignment/employee-portal/employee-portal-api
composer install
```

Only if `.env` does not already exist, copy the template:

```bash
cp .env.example .env
```

PowerShell equivalent: `Copy-Item .env.example .env` (only when the destination is absent).
Configure `.env` using your own database credentials:

```dotenv
APP_NAME="Employee Portal"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=assignement
DB_USERNAME=root
DB_PASSWORD=

FRONTEND_URL=http://localhost:5173
```

`assignement` is the database name used in the current local project; change it if needed.
Create that database in MySQL/phpMyAdmin before migrating. Replace the default
SQLite database settings from the template with the MySQL settings above.

Generate the application key only for a new installation with an empty APP_KEY:

```bash
php artisan key:generate
```

Then run:

```bash
php artisan config:clear
php artisan migrate
php artisan db:seed
php artisan serve --host=localhost --port=8000
```

Keep the server terminal open. Base URL: `http://localhost:8000/api`.
The standard seeder calls RoleSeeder and supplies Manager, Supervisor and Employee.
Existing installations usually only need `composer install`, `php artisan migrate`
and `php artisan db:seed` before starting the server.

## Role recovery and migrations

If roles are empty:

```bash
php artisan db:seed --class=RoleSeeder
```

The role seeder safely adds missing default roles. Fetch the roles again afterward;
recreated records can have new IDs and route keys. Roles must be migrated before
employees because employees reference roles. The migration filenames already enforce this order.
Use `php artisan migrate:status` to inspect migration state. Do not use
`migrate:fresh` on a database whose data you need: it drops all tables.

## Postman configuration

Create collection variable `base_url` = `http://localhost:8000/api`.
Choose **No Auth**, and add:

```text
Accept: application/json
Content-Type: application/json
```

For create/update/status requests, choose **Body > raw > JSON**.
API errors return JSON even without an Accept header.

## API endpoints

All paths below are relative to `base_url`.

| Method | Path | Purpose | Success |
| --- | --- | --- | --- |
| GET | `/roles` | Available role dropdown values | 200 |
| GET | `/employees` | All employees, paginated | 200 |
| GET | `/employees/active` | Active employees only, paginated | 200 |
| POST | `/employees` | Create employee | 201 |
| GET | `/employees/{route_key}` | View employee | 200 |
| PUT or PATCH | `/employees/{route_key}` | Update employee profile | 200 |
| PATCH | `/employees/{route_key}/status` | Update active/inactive status only | 200 |
| DELETE | `/employees/{route_key}` | Soft delete employee | 200 |

### 1. Get roles

```http
GET {{base_url}}/roles
```

Each object in `data` includes `id`, `name`, timestamps and `route_key`.
Copy a role's current `route_key`; do not assume a fixed value or use numeric id
as the role_id input.

### 2. Create an employee

```http
POST {{base_url}}/employees
```

```json
{
  "name": "Rahul Sharma",
  "employee_id": "EMP-001",
  "email": "rahul@example.com",
  "role_id": "REPLACE_WITH_ROLE_ROUTE_KEY",
  "area": "Andheri",
  "region": "Mumbai",
  "zone": "West",
  "joining_date": "2026-11-10",
  "status": "active",
  "password": "Employee123"
}
```

Success returns `message` and the created employee in `data`. Save the employee's
`data.route_key` as Postman variable `employee_key`. Passwords are never returned.

### 3. List, search and paginate

```http
GET {{base_url}}/employees?search=Rahul&page=1&per_page=10
GET {{base_url}}/employees/active?page=1&per_page=10
```

Both endpoints accept `search` (name, employee ID or email; max 100 characters),
`page` (integer >= 1) and `per_page` (1-100; default 10). Newest records appear first.
An empty result is 200 with `data: []` and `total: 0`.
Responses include `data`, `current_page`, `per_page`, `from`, `to`, `total`,
`last_page` and pagination links. Active lookup always filters status to `active`.

### 4. View and update

```http
GET {{base_url}}/employees/{{employee_key}}
PUT {{base_url}}/employees/{{employee_key}}
```

For PUT/PATCH profile updates, send all profile fields from the create example.
Change the values you want to edit. Omit `password` or send `"password": ""` to
keep the existing hash; a non-empty password must meet creation rules.
An employee may retain their own employee code and email during editing.

### 5. Toggle status

```http
PATCH {{base_url}}/employees/{{employee_key}}/status
```

```json
{"status": "inactive"}
```

Use `active` to reactivate. No other fields are required for this endpoint.

### 6. Delete

```http
DELETE {{base_url}}/employees/{{employee_key}}
```

No body needed. Returns 200 with `{"message":"Employee deleted successfully."}`.
Viewing the deleted employee then returns 404.

Deletion sets deleted_at instead of removing the row. Deleted records are excluded
from employee lists, active lookup and route binding. Existing employee codes and
emails remain reserved, including those belonging to soft-deleted employees.

## Validation

| Field | Rules |
| --- | --- |
| name | Required string, max 100 characters |
| employee_id (employee code) | Required, unique, max 50; letters, numbers, hyphens, underscores; trimmed and converted to uppercase |
| email | Required valid email, unique, max 255; trimmed and lowercased |
| role_id | Required string containing an existing role's route_key |
| area / region / zone | Required strings, max 100 each |
| joining_date | Required date in YYYY-MM-DD format |
| status | Required, active or inactive |
| password | Required on creation; 8-255 characters with letters and numbers; optional on update |

Duplicate employee code/email is rejected on creation and when changing a record
to another employee's values. The database also has unique indexes for both.
Names are not unique: different people may share a name.

Validation errors use HTTP 422, for example:

```json
{
  "message": "An employee with this employee code already exists.",
  "errors": {
    "employee_id": ["An employee with this employee code already exists."]
  }
}
```

The message may summarize multiple errors. Missing/invalid employee route keys
return 404. Create, update, status and delete return a success `message`.

## IDs and Hashids

Employee and role responses expose both numeric `id` and hashed `route_key`.
Employee responses include a nested `role` object with its id/name/route_key;
the employee's raw role_id and password are hidden.

- Use employee route_key in URLs, not numeric id.
- Send the role route_key as role_id in JSON.
- employee_id is the readable business employee code, separate from both identifiers.
- Vinkla Hashids is configured in config/hashids.php with minimum length 5.
  Larger numeric IDs may require longer keys.
- Salt defaults to APP_KEY. Optionally configure a stable HASHIDS_SALT in .env.
  Changing the salt, or APP_KEY when it is the fallback, changes all route keys.
- Hashids obscure identifiers; they do not encrypt records or restrict access.

## Troubleshooting

```bash
php artisan route:list --path=api
```

Automated test cases are not included. Use the Postman steps above to verify APIs.

| Problem | Check |
| --- | --- |
| Empty roles / invalid role | Run RoleSeeder, fetch /roles and use a current route_key string |
| 422 | Read errors and correct the named fields; employee code/email may already exist |
| 404 on employee | Use employee route_key instead of numeric id; verify record exists |
| Database connection error | Start MySQL, check .env credentials/database, run config:clear |
| Old routes/config | Run php artisan route:clear and php artisan config:clear |
| React connection/CORS error | Backend on localhost:8000, frontend on localhost:5173, matching FRONTEND_URL |

No portal account creation is necessary. Existing authentication-related files and
users tables are not used by the employee CRUD endpoints.
