# ServicePilot CRM

ServicePilot is a field-service CRM for Syncognify. Organizations manage customers, service requests, estimates, work orders, technician schedules, invoices, and payments from one Laravel + React workspace.

This repository is `service-pilot-crm`. The product name in the app is **ServicePilot**.

## Stack

- Laravel 13, PHP 8.3+, MySQL 8
- Inertia 3, React 19, TypeScript, Tailwind 4
- Official Laravel React starter kit (Fortify, teams, Pest, Wayfinder, shadcn/ui)

Teams are organizations. Authorization is permission-based through extended `TeamRole` / `TeamPermission` values. Organization admins cannot add, update, or remove members and cannot delete the organization.

## Local setup

```bash
cp .env.example .env
# Set APP_NAME=ServicePilot and MySQL credentials
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run dev
php artisan serve
```

Fictional Harborline Field Services demo users (password `password`):

| Email | Role |
| --- | --- |
| maya@harborline.example | Owner |
| jordan@harborline.example | Dispatcher |
| priya@harborline.example | Technician |
| luis@harborline.example | Technician |
| elena@harborline.example | Accountant |
| sam@harborline.example | Sales |

Public intake: `/request-service?org=harborline-field-services`

Do not store real customer data in this development copy. Demo records are fictional.

## Tests

```bash
php artisan test
```

## Notes

- Money math uses `bcmath` through `App\Support\Money`.
- Invoice uniqueness for a work order is enforced in the application with a row lock, not a brittle unique index.
- Do not run destructive production migrations or deploy without explicit authorization.
