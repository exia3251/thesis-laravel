# RANEY LUBRICANTS TRADING

Laravel-based ecommerce and admin management system for engine oil and lubricant retail operations.

## Core Features

- Customer product catalog with search and filtering
- Product details, cart, checkout, and order history
- Admin dashboard, product management, inventory, sales, and reports
- Super admin user management and activity logs
- Partial payment and GCash payment-request flow
- Single-session login enforcement

## Requirements

- PHP 8.2+
- Composer
- MySQL / MariaDB
- Node.js and npm
- XAMPP or equivalent local web stack

## Installation

1. Clone the repository

```bash
git clone <your-github-repository-url>
cd engine-oil-laravel
```

2. Install PHP dependencies

```bash
composer install
```

3. Install frontend dependencies

```bash
npm install
```

4. Create environment file

```bash
copy .env.example .env
```

5. Generate application key

```bash
php artisan key:generate
```

6. Configure database in `.env`

Example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=engine_oil_laravel
DB_USERNAME=root
DB_PASSWORD=
```

7. Run migrations and seeders

```bash
php artisan migrate --seed
```

8. Create storage link

```bash
php artisan storage:link
```

9. Run Laravel server

```bash
php artisan serve
```

10. Optional: run frontend watcher if needed

```bash
npm run dev
```

## Demo Notes

- Root URL redirects to `/shop`
- Customer login/register is under `/shop/login` and `/shop/register`
- Admin login is under `/admin/login`
- If GCash is selected during checkout, the user is redirected to the order payment section
- Replace `public/images/gcash-qr-placeholder.svg` with the real staff GCash QR before the demo if available

## Suggested Demo Accounts

Create accounts in the database or via seeders for:

- `super_admin`
- `admin`
- `customer`

If needed, I can help you generate a final demo seeder for those accounts.

## Important Directories

- `app/Http/Controllers/Admin`:
  admin backend logic
- `app/Http/Controllers/Customer`:
  customer storefront logic
- `app/Models`:
  database models
- `resources/views/admin`:
  admin UI pages
- `resources/views/customer`:
  customer UI pages
- `routes/web.php`:
  session-backed web routes
- `routes/api.php`:
  API routes used by some frontend fetch calls
- `database/migrations`:
  database schema history
- `database/seeders`:
  demo and initial data seeders

## Presentation Focus

Recommended demo sequence:

1. Customer browses products
2. Customer adds to cart and checks out
3. Customer submits GCash payment request
4. Admin approves payment request
5. Super admin views staff action logs
6. Admin shows reports and inventory
