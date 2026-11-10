# Employee Portal frontend

React 19 + Vite 7 + Bootstrap 5 with simple custom CSS. Connected to the adjacent
[Laravel API](../employee-portal-api/README.md). No login is required.

## Requirements

- Node.js 20.19+ or 22.12+ and npm (compatible with Vite 7).
- Laravel API running at http://localhost:8000, with migrations and role seeding complete.

## Setup

In Git Bash:

```bash
cd /d/assignment/employee-portal/employee-portal-web
npm ci
```

For a new local configuration, copy the example:

```bash
cp .env.example .env.local
```

PowerShell equivalent: `Copy-Item .env.example .env.local`.
Do not overwrite an existing .env.local if you have customized it.

```dotenv
VITE_API_URL=http://localhost:8000/api
```

This is also the application's default URL when no environment file exists.
Vite environment variables are public browser configuration; never put secrets here.
Restart Vite after changing environment variables.

## Run both apps

Terminal 1 (after completing the backend README's database setup):

```bash
cd /d/assignment/employee-portal/employee-portal-api
php artisan serve --host=localhost --port=8000
```

Terminal 2:

```bash
cd /d/assignment/employee-portal/employee-portal-web
npm run dev
```

Open **http://localhost:5173**. Keep both terminals running.
The frontend uses strict port 5173, so free that port if it is occupied.
Use localhost consistently. Laravel's FRONTEND_URL must match the frontend origin:

```dotenv
FRONTEND_URL=http://localhost:5173
```

Run `php artisan config:clear` in Laravel after changing its configuration.
No Authorization header, login, CSRF request or credential cookies are needed.

## Features and usage

- Employee table with serial number, name, employee ID, email, joining date, status and actions.
- Add employee popup with role selection, location, joining date, status switch and password.
- View details, edit employee and delete confirmation popups.
- Active/inactive switch directly in each table row; changes save immediately.
- Search by name, employee ID or email; submit Search to apply it. Clear resets it.
- Server pagination and selectable page size.
- Loading/empty/error states, inline validation and success alerts after mutations.
- Responsive Bootstrap layout and keyboard-accessible dialogs.

Select a role returned by the API. Password is required when creating; leave it
blank when editing to keep it. Employee code and email must be unique.
The backend trims and uppercases employee codes and returns validation errors
under errors.employee_id. The form displays these errors beside the field.
Deletion sets deleted_at and retains the database record. No sample employees are inserted by the frontend.

## API integration

All paths below are relative to VITE_API_URL, normally http://localhost:8000/api.
For Postman bodies, response formats, rules and troubleshooting, see the
[complete backend API guide](../employee-portal-api/README.md#api-endpoints).

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /roles | Role dropdown |
| GET | /employees?search=&page=1&per_page=10 | Directory/search/pagination |
| GET | /employees/active | Active employees lookup, available for integration |
| POST | /employees | Create |
| GET | /employees/{route_key} | Fetch one employee |
| PUT / PATCH | /employees/{route_key} | Edit profile |
| PATCH | /employees/{route_key}/status | Toggle status |
| DELETE | /employees/{route_key} | Delete |

The current directory shows all employees; it does not have a separate active-only
screen. The view popup uses the employee details already loaded in the list.
Employee and role responses include actual id and hashed route_key. The UI uses
employee route_key for requests and role.route_key as the form's role_id value.
Numeric role id is not a valid role_id input. Never hardcode role keys; they may
change if records are recreated or the Hashids salt changes.

## Build and preview

```bash
npm run build
npm run preview
```

Production files are generated in dist/. Preview normally uses http://localhost:4173;
for browser API access from preview, temporarily set Laravel FRONTEND_URL to
http://localhost:4173 and run php artisan config:clear. Restore localhost:5173
when returning to development. For deployment, set VITE_API_URL before building
and configure Laravel FRONTEND_URL for the hosted origin.

## Files

| File | Purpose |
| --- | --- |
| src/App.jsx | Table, forms, dialogs, state and CRUD/status actions |
| src/api.js | Axios client, API URL and error handling |
| src/main.jsx | React entry and Bootstrap CSS import |
| src/styles.css | Custom responsive styling |
| .env.example | Example API URL |

## Troubleshooting

- Cannot connect: check Laravel is running on port 8000 and VITE_API_URL includes /api.
- CORS error: use localhost for both apps and check Laravel FRONTEND_URL.
- Empty roles: run php artisan db:seed --class=RoleSeeder in the API folder, then refresh.
- Invalid role: close and reopen the form, then select the role again after refreshing.
- Duplicate employee code/email: enter unused values; editing may retain the record's own values.
- 404 on actions: refresh the directory; the record may be deleted or its route_key changed.
- Port 5173 occupied: stop the other server or intentionally change both Vite's port and Laravel's allowed origin.

Build verification: npm run build. Verify backend requests using the Postman
examples in the API README.
