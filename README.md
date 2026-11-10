# Employee Portal

One repository with two applications:

```text
employee-portal/
  .gitignore
  README.md
  employee-portal-api/     # Laravel API, with its own .gitignore
  employee-portal-web/     # React + Vite + Bootstrap, with its own .gitignore
```

## Setup

Follow the [API setup and endpoints](employee-portal-api/README.md), then the
[frontend setup](employee-portal-web/README.md).

Run each app in a separate terminal:

```bash
cd employee-portal-api
php artisan serve --host=localhost --port=8000
```

```bash
cd employee-portal-web
npm run dev
```

Open http://localhost:5173. API: http://localhost:8000/api.

## Publish as one public GitHub repository

Create an empty **Public** repository named employee-portal on GitHub. Leave
the initialize README, .gitignore and license options unchecked.
From Git Bash, replace YOUR_USERNAME below with your GitHub username:

```bash
cd /d/assignment/employee-portal
git init
git branch -M main
git add .
git status --short
git diff --cached --stat
```

Review the staged files. Environment secrets, vendor, node_modules, local runtime
files and builds must not be staged. Keep .env.example and dependency lockfiles.

```bash
git commit -m "Add employee portal API and frontend"
git remote add origin https://github.com/YOUR_USERNAME/employee-portal.git
git push -u origin main
```

Initialize Git only at the parent folder, not separately in each app. Both apps
will appear as regular folders in the same public repository.
Public repository visibility makes source accessible; it does not host the
running React application or Laravel API. Database records are not uploaded.
