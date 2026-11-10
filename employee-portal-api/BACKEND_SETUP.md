# Employee CRUD API

Start: php artisan serve --host=localhost --port=8000

No login, admin account, token, cookie or CSRF setup is required. Anyone with
API access can manage employees. Existing users/schema are retained but unused.

Send Accept: application/json and Content-Type: application/json.
React baseURL: http://localhost:8000. Do not enable withCredentials.
Allowed frontend origin: FRONTEND_URL, default http://localhost:5173.

- GET /api/roles: role dropdown under data.
- GET /api/employees?page=1&per_page=10&search=: paginated list.
- GET /api/employees/active?page=1&per_page=10&search=: active employees only, with the same response format and filters.
- POST /api/employees: create (201).
- GET /api/employees/{id}: view (200).
- PUT /api/employees/{id}: edit (200).
- DELETE /api/employees/{route_key}: delete (200 with a success message).
- PATCH /api/employees/{route_key}/status: toggle status with {"status":"active"} or {"status":"inactive"}.

Create/update fields: name, employee_id, email, role_id, area, region, zone,
joining_date (YYYY-MM-DD), status (active/inactive), password.
Use the role's route_key from GET /api/roles as the string role_id in the form.
Email and employee_id are unique.
Password requires 8+ characters, letters and numbers; it is hashed and hidden.
The employee password field does not enable login.
On edit omit password or send an empty string to keep it. Other profile fields
are required for PUT/PATCH. Invalid data returns 422; missing records return 404.
Responses include the role relationship. Pagination serial number: from + rowIndex.

Seed dropdown: php artisan db:seed --class=RoleSeeder
Tests: php artisan test (isolated in-memory SQLite).
Login, logout and current-user routes have been removed.

Employee deletion is a soft delete: deleted_at is set and the row is retained.
Deleted employees are excluded from lists and view/update/status routes.
Their unique employee codes and emails remain reserved.

## Hashed identifiers

Vinkla Hashids supplies route_key on employees and roles alongside their actual
numeric id. Employee role_id remains hidden; role.id is available in the nested
role object. All employee URL parameters must be
route_key, including view, update, delete and status. Raw numeric IDs return 404.
The business employee_id such as EMP-001 remains readable as required by the table.

GET /api/roles returns {"data":[{"id":1,"route_key":"...","name":"Manager",...}]}.
Send that exact role route_key as {"role_id":"..."} on employee create/update.
An employee response includes its own route_key and role.route_key.

Minimum hash length is 5 characters. Hashids may grow beyond 5 for larger IDs.
The salt defaults to APP_KEY; optionally configure a stable HASHIDS_SALT.
Changing the salt or APP_KEY (when using the fallback) changes all public keys.
No database schema changes are needed. This is ID obfuscation, not authorization.

Create, update, delete and status changes return a message field. The frontend
shows this message in a Bootstrap success alert only after the API succeeds.
