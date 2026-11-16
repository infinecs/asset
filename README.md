# Infinecs Asset Management

A Laravel app for Infinecs to track IT assets, employees and the org structure, software licenses, birthday gift cards, and contingent-worker task logs.

## Features

- **Dashboard**: asset status overview, warranties expiring soon, quick actions
- **Assets**: inventory with filters, CSV export, QR labels, live tracker, and a full change history. Assets can be assigned to an employee or to a non-employee (e.g. an office PC).
- **Workstations**: desks within each location. Fixed assets (monitors, desktops, speakerphones) are placed at a workstation, and every move between desks is recorded in a movement log. Desks are coded after the office labels (e.g. `INFP1ARAWS01`), can be printed as QR labels, audited against their records, viewed room by room, and exported to CSV.
- **Asset agreements**: email an employee a link to e-sign the agreement for the asset they hold (no login needed)
- **Employees**: directory with advanced search, CSV import/export, HR documents, history, and offboarding (reclaim assets, revoke licenses)
- **Org structure**: roles, teams (internal or client placement), clients, a manager hierarchy, org charts (company-wide and per manager), and bulk editors
- **Digital products**: software licenses assigned to employees, with renewal tracking
- **Birthday gift cards**: daily job that sends Touch 'n Go gift cards, reminds the persons in charge, and notifies managers ahead of a report's birthday (see [docs/BIRTHDAY_GIFT_CARDS.md](docs/BIRTHDAY_GIFT_CARDS.md))
- **Outcome Based / Task Log**: contingent workers log man-hours; managers get reports and monthly/yearly summaries
- **Also**: tasks, contact directory, brands, categories, locations, departments, users, global search

## Requirements

- PHP 8.2+
- Composer and Node.js
- MySQL (the database dump is in Infinecs GDrive: My Drive > Infinecs > Backup > Asset-System > DB)

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/infinecs/asset.git
cd asset

# 2. Install dependencies
composer install
npm install

# 3. Configure environment
cp .env.example .env
php artisan key:generate
# set DB_CONNECTION=mysql and fill DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 4. Run migrations and seed demo data
php artisan migrate --seed

# 5. Build front-end assets and start the server
npm run build      # or `npm run dev` while developing
php artisan serve
```

Visit `http://localhost:8000` and log in with a demo account.

The birthday gift card job needs the Laravel scheduler running on the server. See [docs/CRON_SETUP.md](docs/CRON_SETUP.md).

## Demo Credentials

All seeded accounts use the password `password`.

| Role        | Email                     |
|-------------|---------------------------|
| Super Admin | superadmin@itasset.local  |
| Admin       | admin@itasset.local       |
| Staff       | staff@itasset.local       |
| Manager     | manager@itasset.local     |

## Roles

| Role              | Access |
|-------------------|--------|
| Super Admin       | Everything an admin can do, plus Outcome Based administration |
| Admin             | Full access: assets, employees, org structure, gift cards, users, and all settings |
| Staff             | Dashboard, directory, and read-only employee pages |
| Manager           | Outcome Based admin only (categories, departments, reports, summaries) |
| Contingent Worker | Outcome Based task log only |

Manager and contingent-worker access is enforced by middleware (`app/Http/Middleware`). Everything else is checked in each controller.

## Tests

Tests run against a separate MySQL database, `asset_infinecs_testing` (configured in `phpunit.xml`). Create it once, then run:

```bash
php artisan test
```

## Tech Stack

- **Framework**: Laravel 12 (PHP 8.2+)
- **Frontend**: Blade, Tailwind CSS 4, Alpine.js, Bootstrap Icons, Tom Select (built with Vite)
- **Database**: MySQL
- **Other**: simple-qrcode for asset labels
