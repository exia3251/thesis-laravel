## Requirements

Make sure you have all of these installed before starting.

| Tool          | Version       | Download                      |
| ------------- | ------------- | ----------------------------- |
| XAMPP         | Latest        | https://www.apachefriends.org |
| PHP           | 8.2 or higher | Included with XAMPP           |
| Composer      | Latest        | https://getcomposer.org       |
| Node.js + npm | 18 or higher  | https://nodejs.org            |
| Git           | Latest        | https://git-scm.com           |

To verify versions after installing, open a terminal and run:

```bash
php -v
composer -V
node -v
npm -v
git -v
```

---

## Step 1 — Start XAMPP

1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL**
3. Make sure both show green — if either fails, check that port 80 (Apache) and port 3306 (MySQL) are not occupied by another program

---

## Step 2 — Create the Database

1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click **New** on the left sidebar
3. Name the database: `engine_oil_inventory`
4. Set collation to `utf8mb4_general_ci`
5. Click **Create**

---

## Step 3 — Clone the Repository

Open a terminal and navigate to XAMPP's `htdocs` folder:

```bash
# Windows
cd C:/xampp/htdocs

```

Then clone the project:

```bash
git clone https://github.com/exia3251/thesis-laravel.git
cd thesis-laravel
```

## Step 4 — Open vs code and Install PHP Dependencies

Open thesis-laravel folder name in xampp htdocs and in terminal:

```bash
cd thesis-laravel
composer install
```

This installs all Laravel packages listed in `composer.json`. It may take a minute.

---

## Step 5 — Install Frontend Dependencies

```bash
cd thesis-laravel
npm install
```

---

## Step 6 — Set Up the Environment File

Copy the example environment file:

```bash
# Windows
copy .env.example .env

```

Open the `.env` file in any text editor and update the database section:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=engine_oil_inventory
DB_USERNAME=root
DB_PASSWORD=
```

## Step 7 — Generate Application Key

```bash
php artisan key:generate
```

This fills in `APP_KEY` in your `.env` file.

---

## Step 8 — Run Migrations and Seed the Database

```bash
php artisan migrate --seed
```

This creates all the database tables and populates them with the default demo accounts and products (SOLAR, CANROYAL, PATROL only).

If you ever need to reset everything and start fresh:

```bash
php artisan migrate:fresh --seed
```

> **Warning:** `migrate:fresh` drops all tables and recreates them.

---

## Step 9 — Create the Storage Link

This links the `storage` folder so uploaded product images are accessible in the browser:

```bash
php artisan storage:link
```

---

## Step 10 — Run the Development Server

```bash
php artisan serve
```

Open your browser and go to: `http://localhost:8000`

The root URL redirects to `/shop` automatically.

---

## Default Demo Accounts

These accounts are created automatically by the seeder in Step 8.

| Role        | Username     | Password        |
| ----------- | ------------ | --------------- |
| Super Admin | `superadmin` | `superadmin123` |
| Admin       | `admin`      | `admin123`      |
| Customer    | `customer`   | `customer123`   |

| URL                                   | Purpose                   |
| ------------------------------------- | ------------------------- |
| `http://localhost:8000/shop`          | Customer storefront       |
| `http://localhost:8000/shop/login`    | Customer login            |
| `http://localhost:8000/shop/register` | Customer registration     |
| `http://localhost:8000/admin/login`   | Admin / Super Admin login |

---

## Common Issues

**`composer install` fails with PHP version error**
Make sure your system is using PHP 8.2+. On Windows with XAMPP, add `C:/xampp/php` to your system PATH environment variable.

**`php artisan` command not found**
Your PHP is not in your PATH. On Windows, add `C:/xampp/php` to system environment variables. On Mac, add it to your shell profile.

**Database connection error on `php artisan migrate`**

- Check that MySQL is running in XAMPP
- Double-check `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in your `.env`
- Make sure the database `engine_oil_inventory` exists in phpMyAdmin

**`php artisan storage:link` fails**
Run it as administrator (Windows) or with `sudo` (Mac/Linux). If the link already exists, it will say so — that's fine, skip it.

**Port 8000 already in use**
Run the server on a different port:

```bash
php artisan serve --port=8080
```

## Project Structure (Quick Reference)

```
app/Http/Controllers/Admin/       — Admin backend logic
app/Http/Controllers/Customer/    — Customer storefront logic
app/Models/                       — Database models
resources/views/admin/            — Admin UI pages
resources/views/customer/         — Customer UI pages
routes/web.php                    — Main web routes
routes/api.php                    — API routes
database/migrations/              — Database schema
database/seeders/                 — Demo data seeders
storage/app/public/products/      — Uploaded product images
```
