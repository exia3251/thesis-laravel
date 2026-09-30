# Technical Overview

**Inventory Management System with Point of Sale and Chatbot**
Raney Lubricants Trading · Imus Institute of Science and Technology

Written for the IT experts validating this system under ISO/IEC 25010. It
describes what the system is built from, how it is put together, and what was
done about the quality characteristics that model asks after.

---

## 1. At a glance

| | |
| --- | --- |
| Type | Localhost web application, served from the researchers' machine |
| Language | PHP 8.2 |
| Framework | Laravel 12.54 |
| Database | MySQL (XAMPP), 17 domain tables |
| Views | Blade server-side templates, 46 files |
| Styling | Tailwind CSS 3.4, compiled by Vite 7 |
| Client-side | Plain JavaScript. No React, Vue or Alpine. |
| AI assistant | Groq API (`openai/gpt-oss-120b`), with a non-AI fallback |
| Third-party PHP packages | 2 (Laravel Socialite, Tinker) |
| Automated tests | 361 passing, 1,160 assertions, 31 test files |

Roughly 11,000 lines of PHP and 13,000 of Blade, across 23 controllers, 15
models, 9 services, 4 middleware, 31 migrations.

---

## 2. Why this shape

**Server-rendered, not a single-page application.** Pages are Blade templates
returned whole; JavaScript is used for the parts that genuinely need it -- the
chat widget, table filters, the cart. There is no client framework and no build
step needed to read the code. The reason is maintainability by whoever inherits
this: a Blade file and a controller are two files to follow, where a component
tree is a graph.

**Plain JavaScript, no framework.** The interactive surfaces here are lists,
filters and forms. Nothing on any screen holds enough client state to earn a
framework, and adding one would put a toolchain between a future maintainer and
a one-line fix.

**Two dependencies.** Socialite for Google Sign-In, Tinker for the console.
Everything else is Laravel or written here. Every package added is a thing that
can rot, and a localhost system for a small trader should still run in three
years without a dependency audit.

---

## 3. Structure

```
app/
  Http/Controllers/      23  — request handling, split Admin/ and Customer/
  Http/Middleware/        4  — role gates, single-session enforcement
  Models/                15  — Eloquent models
  Rules/                  1  — an address has to name places that exist
  Services/               9  — logic too big for a controller
    Chatbot/                  the assistant: matcher, responder, Groq layer
  Support/                    shared helpers (search matching)
database/
  data/                       the PSA place-name list, committed
  migrations/            31  — every schema change, in order
  seeders/                7  — catalogue, vehicle guide, places, demo data
resources/views/         46  — Blade, one layout family
tests/Feature/           31  — behaviour tests against a real database
```

Business rules that outgrew a controller live in `app/Services`: order
cancellation, receipt numbering, the chatbot's two halves. Controllers validate
input and delegate.

---

## 4. The database

17 domain tables. The ones worth knowing:

- **`sales` / `sale_items`** — an order and its lines. Line items store their
  own `unit_price` and `subtotal` rather than reading today's price, so an old
  receipt still shows what was actually charged after a price change.
- **`payment_requests`** — a customer's GCash submission with reference number
  and screenshot, pending until staff approve it. Several may exist per order,
  which is how a 50% down payment can arrive as two 25% transfers.
- **`inventory` / `stock_transactions`** — current quantity, and the movements
  that produced it.
- **`chat_intents` / `chat_conversations` / `chat_messages`** — the assistant's
  written answers, and every exchange.
- **`vehicle_specs`** — 62 vehicles with the oil grade each takes, each row
  carrying its source and whether it has been checked.
- **`psgc_locations`** — the Philippine Statistics Authority's place names:
  84 provinces, 1,634 cities and municipalities, 42,046 barangays, each
  pointing at the one it sits inside. Reference data rather than this
  business's, so it is seeded from a committed file and never edited here. It
  is what makes the three address boxes narrow each other, and what stops a
  barangay being saved under a city it does not sit in.
- **`activity_logs`** — who did what.
- **`document_sequences`** — gapless order, receipt and delivery numbers.

Every table arrived through a migration. The schema can be rebuilt from empty
with `php artisan migrate --seed`.

---

## 5. Authentication and authorisation

**Two guards over one users table.** `web` for customers, `staff` for the back
office. They hold separate sessions, so a member of staff can be signed into
the shop in one tab and the back office in another without either displacing
the other — which is what a single shared guard would have done.

Four roles: administrator, inventory staff, accounting, customer. Route
middleware gates by role; a permission middleware gates finer actions.

**One live session per account.** Signing in elsewhere ends the earlier
session, and the account is told why. The demo logins used for evaluation are
exempt while the environment is local, because a login handed to fifty
respondents cannot also be single-session.

Passwords are hashed by Laravel (bcrypt). Password reset uses Laravel's
tokenised broker with expiry. Registration requires email confirmation.

---

## 6. The chatbot

Two halves, and which one answers depends on the question.

**Questions about the shop's own data** — where an order is, what is owed,
whether a payment landed, what is in stock, what something costs — are answered
from the database by a keyword matcher over `chat_intents`. No AI is involved,
because the real figure is in the table and a language model can only
approximate it.

**Everything else** goes to Groq. It is given the shop's own written answers,
the catalogue arranged by viscosity grade, and the vehicles the business has
looked up, and is instructed to answer from those alone. It is explicitly
forbidden to state a price, a stock level, a delivery date, or anything about a
particular order.

**Without the AI** — no key, no internet, or the free allowance spent — the
keyword half answers on its own. This is not a degraded mode bolted on
afterwards; it is how the assistant worked first, and it is what runs when the
system is demonstrated on a laptop with no connection.

Guards on the way in and out: messages that address the model rather than the
shop are never sent; replies that discuss their own instructions, or stop
mid-sentence, are discarded and the keyword half answers instead.

---

## 7. Against ISO/IEC 25010

**Functional suitability.** Sales, inventory, POS, deliveries, payments
including split, and the assistant. Behaviour is pinned by 361 automated tests
rather than by manual checking.

**Performance efficiency.** Pages are server-rendered and paginated. Lists that
could grow — orders, products, users, activity — are paginated server-side, so
a filter reaches the whole table rather than the page already loaded. The
assistant caches its fact page for ten minutes.

**Compatibility.** Runs on any XAMPP-class stack: PHP 8.2+, MySQL, no
extensions beyond Laravel's defaults. No queue worker, no Redis, no cron
required.

**Usability.** One visual language across both halves. Tables become labelled
cards below 768px, so the back office is usable on a phone. Every destructive
action confirms first. Errors say what to do next rather than what failed.

**Reliability.** The assistant falls back rather than erroring. Payment
approval and order cancellation run in database transactions. Receipt numbers
come from a sequence table, so two simultaneous orders cannot collide.

**Security.** CSRF on every state-changing request; Blade escapes output by
default; Eloquent parameterises queries; uploads are validated by type and
size; rate limiting on sign-in; `.env` is git-ignored and carries every secret.
Debug output is forced off for any request that did not come from the host
machine, so an error page cannot publish the environment.

**Maintainability.** Two third-party packages. Business rules sit in named
services rather than inside controllers. Tests describe intent, not
implementation. Comments explain *why* a thing is the way it is — several
record a bug that was found and what it cost — so the reasoning survives the
people who wrote it.

**Portability.** `git clone`, `composer install`, `npm install && npm run
build`, `php artisan migrate --seed`, `php artisan serve`. No container, no
cloud service, no account. The AI key is optional and the system works without
one.

---

## 8. Honest limits

Worth stating plainly to anyone evaluating this:

- **The vehicle guide is partly unverified.** 37 of 62 rows have been checked
  against manufacturer specification; the rest are general reference figures
  and say so. Every oil answer tells the customer their own handbook decides.
- **The assistant's knowledge of vehicles outside that guide comes from the
  model**, not from this business, and carries the same disclaimer.
- **Google Sign-In is switched off.** Google will not accept a tunnel address
  as an authorised domain, so the consent screen cannot be published. The code
  and credentials remain; a single setting re-enables it on localhost.
- **No inventory forecasting yet.** Stock levels, reorder thresholds and
  movement history exist; turnover, days-of-cover and slow-moving
  classification do not.
- **Localhost by design.** Reachable from outside only while a tunnel is
  running for evaluation.
