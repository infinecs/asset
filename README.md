# IT Asset Management System

A Laravel-based IT Asset Management System that allows IT departments to track, manage, and request assets efficiently.

## Features

- **Dashboard** — Overview of asset status, open tickets, warranties expiring soon, and quick actions
- **Asset Inventory** — Full CRUD for IT assets with filtering by status, category, and location
- **Live Asset Tracker** — Real-time status view of all assets with auto-refresh
- **Request Tickets** — Submit and manage IT request tickets (new asset, repair, replacement, return, transfer)
- **Category & Location Management** — Organize assets by category and physical location
- **User Management** — Role-based access control (Admin, Staff, User)
- **Asset History** — Full audit trail of changes and status updates

## Screenshots

### Login Page
![Login](https://github.com/user-attachments/assets/b6b278f4-6872-49ec-8aec-fbf28c085214)

## Requirements

- PHP 8.2+
- Composer
- MySQL (the mysql file can be found in Infinecs GDrive My Drive > Infinecs > Backup > Asset-System > DB)

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/infinecs/asset.git
cd asset

# 2. Install PHP dependencies
composer install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Configure database in .env (default uses SQLite)

# set DB_CONNECTION=mysql and fill DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 5. Run migrations and seed demo data
php artisan migrate --seed

# 6. Start the development server
php artisan serve
```

Visit `http://localhost:8000` and log in with the demo credentials.

## Demo Credentials

| Role  | Email                   | Password  |
|-------|-------------------------|-----------|
| Admin | admin@itasset.local     | password  |
| Staff | staff@itasset.local     | password  |

## Role Permissions

| Feature                    | User | Staff | Admin |
|---------------------------|------|-------|-------|
| View assets & tickets      | ✅   | ✅    | ✅    |
| Submit request tickets     | ✅   | ✅    | ✅    |
| Create/edit assets         | ❌   | ✅    | ✅    |
| Manage categories/locations| ❌   | ✅    | ✅    |
| Update ticket status       | ❌   | ✅    | ✅    |
| Manage users               | ❌   | ❌    | ✅    |
| Delete records             | ❌   | ❌    | ✅    |

## Tech Stack

- **Framework**: Laravel 13
- **Frontend**: Blade templates, Bootstrap 5, Bootstrap Icons
- **Database**: SQLite (default), MySQL/PostgreSQL compatible
- **JavaScript**: Minimal — only used for Bootstrap UI interactions and live tracker auto-refresh
