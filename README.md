# Inventory Management System with POS & Chatbot

RANEY LUBRICANTS TRADING — Laravel 12, MySQL, Tailwind.

A clone of this repository runs on its own: the product catalogue, the product
photographs, and the Philippine place names behind the address fields are all
in here. Nothing has to be fetched from anywhere, and nothing but the database
has to be created by hand.

---

## What you need first

| Tool          | Version       | Where                         |
| ------------- | ------------- | ----------------------------- |
| XAMPP         | Latest        | https://www.apachefriends.org |
| PHP           | 8.2 or higher | Comes with XAMPP              |
| Composer      | Latest        | https://getcomposer.org       |
| Node.js + npm | 18 or higher  | https://nodejs.org            |
| Git           | Latest        | https://git-scm.com           |

Check them before you start. Every one of these should print a version:

```bash
php -v
composer -V
node -v
npm -v
git -v
```

If `php -v` is not found, or prints a version below 8.2, add `C:\xampp\php` to
your Windows PATH and open a new terminal.

---

## Setting up

### 1. Start XAMPP

Open the XAMPP Control Panel and start **Apache** and **MySQL**. Both should
go green. If MySQL will not start, something else is using port 3306.

### 2. Create the database

Go to `http://localhost/phpmyadmin`, click **New**, and create a database
named `engine_oil_inventory` with collation `utf8mb4_general_ci`.

Create it empty. The tables come from the migrations in the next steps.

### 3. Clone the repository

```bash
cd C:/xampp/htdocs
```

```bash
git clone https://github.com/exia3251/thesis-laravel.git
```

```bash
cd thesis-laravel
```

### 4. Install the PHP packages

```bash
composer install
```

### 5. Install the frontend packages

```bash
npm install
```

### 6. Create your .env file

```bash
copy .env.example .env
```

That is for Windows Command Prompt or PowerShell. In Git Bash, or on macOS
or Linux, use this instead:

```bash
cp .env.example .env
```

Open the new `.env` and check the database block matches what you created:

```env
DB_DATABASE=engine_oil_inventory
DB_USERNAME=root
DB_PASSWORD=
```

A default XAMPP MySQL has no root password, so leave `DB_PASSWORD` empty
unless you set one.

Everything else in `.env` can stay as it is for now — the system runs without
any of the optional keys. See **Optional keys** below for what each one turns
on.

### 7. Generate the application key

```bash
php artisan key:generate
```

### 8. Create the tables and fill them

```bash
php artisan migrate --seed
```

This builds every table and loads everything the system needs to run: the
staff and customer accounts, the product catalogue, the assistant's answers,
the vehicle oil guide, and 43,764 Philippine provinces, cities and barangays
for the address fields. The place names take a few seconds — that is normal.

If you set the database up before this was added to the seeder, run
`php artisan db:seed` once on its own to pick up the assistant's answers and
the vehicle guide. It is safe on an existing install: nothing is duplicated
and nothing you have entered is touched.

### 9. Link the storage folder

```bash
php artisan storage:link
```

**This is the step that makes product photographs appear.** The pictures are
in the repository, but they live under `storage/` and the browser can only
reach `public/`. This command bridges the two.

If it says the link already exists, that is fine — carry on.

**On Windows it can fail**, because creating a link needs permission that an
ordinary terminal does not have. The symptom is a shop with every product
photograph missing. Two ways out, either is fine:

- Close the terminal, reopen it with **Run as administrator**, and run the
  command again; or
- Turn on **Settings → System → For developers → Developer Mode**, then run it
  again in a new terminal.

If neither is available to you, copy the folder by hand instead. It works, but
anything uploaded afterwards will not appear until you copy it again:

```bash
xcopy /E /I /Y storage\app\public public\storage
```

### 10. Build the stylesheet

```bash
npm run build
```

**Do not skip this.** The stylesheet is compiled, not shipped. Without it
every page loads unstyled, or Laravel stops with "Vite manifest not found".

Run it again any time you pull changes that touch a `.blade.php` or
`.css` file.

### 11. Run it

```bash
php artisan serve --port=8123
```

Then open **http://localhost:8123**

Use port 8123. It is the port the Google Sign-In callback is registered
against, and the one `.claude/launch.json` expects.

---

## Signing in

These accounts are created by step 8. **Sign in with the email address, not a
username.**

| Role            | Email                     | Password         |
| --------------- | ------------------------- | ---------------- |
| Administrator   | `admin@raney.test`        | `admin123`       |
| Inventory Staff | `inventory@raney.test`    | `inventory123`   |
| Accounting      | `accounting@raney.test`   | `accounting123`  |
| Customer        | `john@example.com`        | `customer123`    |

| Page                 | Address                                  |
| -------------------- | ---------------------------------------- |
| Shop                 | `http://localhost:8123/shop`             |
| Customer sign-in     | `http://localhost:8123/shop/login`       |
| Customer registration| `http://localhost:8123/shop/register`    |
| Back office sign-in  | `http://localhost:8123/admin/login`      |

Staff sign in with an email and password only. Google Sign-In is for
customers, and the back office refuses it deliberately.

---

## Optional keys in .env

The system runs with all of these empty. Each one turns on a feature; none of
them stops anything else working.

| Key | What it turns on | Without it |
| --- | --- | --- |
| `GROQ_API_KEY` | The assistant answers with Groq | It matches keywords against the answers in **Admin → Assistant** and still reads real orders, stock and prices from the database |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | Order, payment, delivery, verification and password-reset emails | Mail is logged as a failure and swallowed; an order still completes, it just goes unannounced |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | "Continue with Google" on the customer pages | The buttons are hidden and the route is closed |
| `SURVEY_FORM_URL` | The link on the `/survey` page | The page has nothing to point at |

`.env` is in `.gitignore` and must stay there. It holds live credentials and
is never committed — which is why `.env.example` carries empty placeholders
and comments instead of values.

**Getting the real values:** ask whoever set the services up and keep them out
of the repository, out of issues, and out of chat threads. A key that reaches
a place other people can read has to be replaced, not hidden.

Check the assistant key once it is in:

```bash
php artisan assistant:check
```

It says whether the key works, and lists the models it can reach if the
configured one has been retired.

---

## Optional extra data

Neither of these runs as part of `--seed`. Both are deliberate.

**A year of trading history**, so the dashboard and analytics have something
to show:

```bash
php artisan db:seed --class=DemoSalesSeeder
```

These are written as orders that already happened. They do not move current
stock.

**Re-fetch the product photographs** from the manufacturers' own sites. The
repository already carries them, so this is only for refreshing them:

```bash
php artisan db:seed --class=ProductImageSeeder
```

---

## Everyday commands

```bash
php artisan serve --port=8123
```

```bash
npm run dev
```

`npm run dev` rebuilds the stylesheet as you edit, instead of running
`npm run build` by hand each time. Leave it running in its own terminal.

```bash
php artisan test
```

The test suite. It runs against a database of its own and sends no mail, so
it cannot touch your data or anybody's inbox.

That database has to exist first. Create one more empty database in
phpMyAdmin named `engine_oil_inventory_test`, the same way as in step 2. You
do not need to migrate or seed it — the suite builds and empties it itself on
every run.

```bash
php artisan migrate:fresh --seed
```

Start the database over. **This drops every table**, including any orders or
accounts you made while working.

---

## When something is wrong

**Pages load with no styling, or "Vite manifest not found"**
`npm run build` has not been run, or has not been run since the last pull.

**`php artisan` is not recognised**
PHP is not on your PATH. Add `C:\xampp\php` and open a new terminal.

**`composer install` complains about the PHP version**
Your terminal is using a PHP older than 8.2 — often a second PHP that is
earlier in PATH than XAMPP's. Check with `php -v`.

**Migrations cannot connect**
MySQL is not running in XAMPP, or `engine_oil_inventory` does not exist yet,
or `DB_PASSWORD` in `.env` does not match your MySQL root password.

**Product images are missing**
`php artisan storage:link` has not been run, or it failed. On Windows it needs
an administrator terminal or Developer Mode — see step 9, which has the
fallback if neither is possible.

**The assistant answers nothing, or the shop looks empty of chat answers**
The assistant's answers were not loaded. Run `php artisan db:seed`. It is safe
to run on an existing database.

**The assistant answers, but never with AI**
That is the fallback working, not a fault. With no `GROQ_API_KEY` it matches
keywords against the answers in **Admin → Assistant** and still reads real
orders, stock and prices from the database. Run `php artisan assistant:check`
to see what it thinks of the key.

**Port 8123 is already in use**
Something is still serving from an earlier run. Close that terminal, or pick
another port — but if you change it, the Google Sign-In callback for the new
port has to be registered in the Google console, or that button will fail.

**The assistant answers oddly, or always the same way**
`GROQ_API_KEY` is empty or rejected, so it has fallen back to keyword
matching. Run `php artisan assistant:check`.

---

## The other documents here

| File | What it covers |
| --- | --- |
| `USER-MANUAL.md` | How to actually use the system, screen by screen |
| `TECHNICAL-OVERVIEW.md` | How it is built and why, for the write-up |
| `PROJECT_GUIDE.md` | Which file to edit for a given change |
| `SHARING.md` | Putting the system on a public link for the survey |
| `SURVEY-PREAMBLE.md` | The wording for the survey form |

This file is the only one about getting it running.

---

## Where things live

```
app/Http/Controllers/Admin/      Back office
app/Http/Controllers/Customer/   Storefront
app/Services/                    Business rules, kept out of controllers
app/Models/                      Database models
resources/views/admin/           Back office pages
resources/views/customer/        Storefront pages
resources/views/layouts/         Shared layout and shared JavaScript
routes/web.php                   Every route, grouped by who may reach it
database/migrations/             Table definitions
database/seeders/                Starting data
database/data/psgc.csv           The Philippine place names
tests/Feature/                   The test suite
```
