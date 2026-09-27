# RANEY LUBRICANTS TRADING — User Manual

An online shop and back office for a lubricants trading business. Customers
browse oils, ask an assistant which one suits their vehicle, and place orders.
Staff see those orders, take payment, move deliveries along, and watch what the
business is doing.

This manual covers both halves. Sections 1 to 3 are for whoever runs the
system; section 4 is for customers; section 5 is for staff.

---

## 1. Starting the system

Three things run at once, each in its own window. Close any one of them and the
site stops.

**MySQL** — from the XAMPP Control Panel, press Start next to MySQL.

**The site** — a terminal in the project folder:

```bash
cd C:\xampp\htdocs\thesis-laravel
php artisan serve --port=8123
```

**The public link** — only needed when people outside this laptop must reach
it. A second terminal, any folder:

```bash
ngrok http 8123 --url https://cognition-prancing-splendid.ngrok-free.dev
```

On this machine the site is at <http://127.0.0.1:8123>. Through the tunnel it
is at the ngrok address above. The tunnel address is permanent — it does not
change between runs — but it only works while all three windows are open and
the laptop is awake.

Visitors reaching it through the tunnel see an ngrok warning page first, once
per browser, with a **Visit Site** button. That page belongs to ngrok and
cannot be removed without paying for a plan.

### Where people should start

| Who | Address |
| --- | --- |
| Someone being shown the system | `/survey` |
| A customer | `/shop` |
| Staff | `/admin/login` |

`/survey` is a landing page that hands over both logins, links to each
sign-in, and lists things worth trying. It only exists while the environment is
local.

---

## 2. Settings

All of these live in `.env` in the project folder. After changing any of them:

```bash
php artisan config:clear
```

### Already set

| Setting | What it does |
| --- | --- |
| `DB_DATABASE` | The MySQL database. `engine_oil_inventory`. |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | The Gmail account that sends order, verification and password-reset mail. The password is a Google **App Password**, not the account password. |
| `GROQ_API_KEY` | The assistant's AI key. Free, from <https://console.groq.com/keys>. |
| `GROQ_MODEL` | `openai/gpt-oss-120b`. Change only if Groq retires it. |
| `BUSINESS_PHONE` | The contact and GCash number shown to customers. |
| `GOOGLE_SIGNIN_ENABLED` | `false`. See below. |

### Worth setting

| Setting | Why |
| --- | --- |
| `SURVEY_FORM_URL` | The questionnaire. Set it and a button to the form appears on `/survey`. Empty now, so no button is shown. |

### Deliberately off

`GOOGLE_SIGNIN_ENABLED=false` hides the Sign in with Google button and closes
its route. Google will not accept an ngrok address as an authorised domain, so
the consent screen cannot be published, and an unpublished app admits only
named test users. Nothing was deleted: set it to `true` and register
`http://localhost:8123/auth/google/callback` with the OAuth client to use it on
this machine.

### Checking things work

```bash
php artisan assistant:check
```

Says whether the AI key works, and what is wrong if it does not — including
listing the models the key can reach when the configured one has been retired.

---

## 3. Accounts

Four accounts are created by the seeder. While the environment is local their
passwords are printed on the sign-in pages, and a button fills the form in.

| Account | Password | Sees |
| --- | --- | --- |
| `admin@raney.test` | `admin123` | Everything |
| `inventory@raney.test` | `inventory123` | Products, inventory |
| `accounting@raney.test` | `accounting123` | Sales, payments |
| `john@example.com` | `customer123` | The shop, as a customer |

The shop and the back office keep **separate sessions**. You can be signed in
as a customer in one tab and as staff in another, and switch freely. You do not
need to sign out of one to use the other.

The two sign-ins take different accounts, though. A staff login typed into the
shop page is refused, and the other way round — the page says which to use.

---

## 4. For customers

### Finding an oil

**Browse** — `/shop` lists everything, with filters by brand, oil type and
viscosity grade. Each product page shows the pack sizes it comes in (1, 4 and 5
litres), the price of each, and what is in stock.

**Ask the assistant** — the chat bubble at the bottom right. It answers
questions about products, delivery, payment, returns and which oil suits which
vehicle. Ask it the way you would ask a person:

- *"What oil does my car take?"* — it asks which car, then answers
- *"2018 Toyota Vios"* — the grade, how many litres, and which pack covers it
- *"Do you have 0W-16?"* — a straight yes or no about the shelf
- *"Do you deliver to Cebu?"*, *"What is your returns policy?"*

Every oil recommendation ends on the same line: your own vehicle handbook has
the final say, and checking it before an oil change is the owner's
responsibility. The wrong viscosity can damage an engine.

### Ordering

Add to the cart, then check out. Delivery is anywhere in the Philippines and is
included in the price shown — nothing is added at checkout.

Three ways to pay:

| Method | How it works |
| --- | --- |
| **Cash on delivery** | Pay the courier in full. |
| **GCash** | Scan the QR or send to the number shown, then enter your reference number. Staff verify it. |
| **Down payment** | At least **50%** by GCash, the rest in cash on delivery. |

A down payment can be sent in more than one GCash transfer — each is entered
with its own reference number and they add up.

### Following an order

**My Orders** shows each one. Open it for the full receipt: what was ordered,
how it was paid, what is still owed, and where the delivery is:

- **Processing** — being prepared, still in the warehouse
- **With the courier** — on its way
- **Delivered** — arrived

### Forgotten password

The sign-in page has a reset link. It sends a real email with a link that
expires. Use an address you can open.

---

## 5. For staff

Sign in at `/admin/login`. The menu runs down the left. What you see depends on
your role — an administrator sees all of it.

### Dashboard

Money taken over a period you choose: all time, 12 / 6 / 3 months, 30 or 7
days, or a custom range from a calendar. The chart and every figure follow that
range.

**Action required** lists what needs doing right now — orders awaiting payment,
payments to verify, paid orders not yet delivered, products out of stock or
running low, refunds to process. Each is a link to the screen that clears it,
filtered to exactly the set it counted. This list ignores the date filter: it
is about today.

### Analytics

The same date filter applied to the trade itself: best-selling products, oil
types, brands, units sold, and customers — how many, how many new, what share
are returning, orders per customer. Everything here exports.

### Products

Add, edit and archive products. Pack size is 1, 4 or 5 litres, chosen from
buttons. Before anything is saved you are shown **how the product page will
look to a customer**, and asked whether to go ahead.

Archiving hides a product from the shop without deleting it — past orders
containing it still read correctly.

### Inventory

Stock levels, with pictures. A product is **running low** when its quantity has
fallen to or below its own reorder level, and **out of stock** at zero. The
reorder level is set per product.

### Sales

Every order. Open one for the whole receipt: items, how it was paid, what is
still owed, the delivery state, and any GCash references the customer sent.

Two jobs happen here:

- **Verify a payment** — approve or reject a GCash reference. Approving
  settles the balance by that amount.
- **Move a delivery** — Processing → With the courier → Delivered. The middle
  state is set by somebody who watched it leave, not assumed at checkout.

### Users

Customer and staff accounts, paginated, with an activity log of what each has
been doing.

### Assistant

Every question the chatbot could not answer, most-asked first. Write an answer
for one and the chatbot uses it immediately — no restart.

The **Vehicle oil guide** on the same screen lists the 62 vehicles the guide
holds. Each row shows its grade, capacity and whether it has been **checked
against a manual**. Open a row, verify it, tick Checked. Until a row is ticked
it is a general reference figure, and the manual is what decides.

### Backup

Take a copy of the whole system as a single file, and restore one, without
touching a database. Take one before anything risky.

---

## 6. How the assistant works

Two halves, and which answers depends on the question.

**Questions about your own data** — where an order is, what is owed, whether a
payment landed, what is in stock, what something costs — are answered from the
database. No AI is involved, because the real figure is in the table.

**Everything else** goes to Groq, which is given the shop's own written
answers, the catalogue arranged by viscosity grade, and the vehicles the shop
has looked up. It is told to answer from those and nothing else, never to state
a price or a stock level, and never to offer a grade the shop does not carry.

**If Groq cannot be reached** — no key, no internet, or the free allowance
spent — the first half answers on its own from keywords. The assistant keeps
working with no internet at all, which is how it behaves during a demonstration
on a laptop with no connection.

The free allowance is roughly three questions a minute across everyone using
the site at once. Past that the answers get simpler rather than failing.

---

## 7. When something goes wrong

**Nothing loads at all** — the laptop running it is off, asleep, or one of the
three windows was closed.

**"No connection could be made" in the terminal** — MySQL is not running. Start
it from the XAMPP panel.

**The tunnel link 404s with an ngrok page** — the site is running but the
tunnel is not, or it is pointed at the wrong port. It must be 8123.

**The assistant gives short, keyword-ish answers** — Groq is unreachable or the
free allowance is spent. `php artisan assistant:check` says which.

**A login is refused with the right password** — the browser autofilled an old
one saved for a different address. Use the fill button under the form, or type
it out.

**Email does not arrive** — check `MAIL_USERNAME` and `MAIL_PASSWORD`. The
password must be a Google App Password.

---

## 8. Known limits

- **37 of the 62 vehicle rows** have been checked against manufacturer
  specification. The rest are general reference figures and say so. Every oil
  answer tells the customer their own handbook decides.
- **The assistant's knowledge of vehicles outside the guide** comes from the
  model, not from this business. Those answers carry the same disclaimer.
- **Google sign-in is off.** See section 2.
- **Sharing the link shares the system.** The demo logins are printed on the
  sign-in pages, and the administrator account can do anything. Take the link
  down when it is no longer needed.
