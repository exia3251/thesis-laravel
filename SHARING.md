# Sharing the system for the survey

How to put this system on a public link while it runs on your laptop, so
survey respondents can use it without installing anything. The link works only
while your laptop is on, the server is running and the tunnel is open.

---

## Every time you share

Three things have to be running at once.

**1. MySQL.** From the XAMPP control panel, or:

```bash
C:/xampp/mysql/bin/mysqld.exe --defaults-file=C:/xampp/mysql/bin/my.ini --standalone
```

**2. The site**, on a port of its own:

```bash
php artisan serve --port=8123
```

**3. The tunnel**, which turns that port into a public address. Pick one of
the two below the first time; after that it is one command.

---

## This project's link

The tunnel is set up already. Three windows, each left open:

```bash
cd C:\xampp\htdocs\thesis-laravel
php artisan serve --port=8123
```

```bash
ngrok http 8123 --url https://cognition-prancing-splendid.ngrok-free.dev
```

plus MySQL from the XAMPP panel. The address is permanent -- it belongs to the
account and does not change between runs -- so what goes in the Google Form is
always:

```
https://cognition-prancing-splendid.ngrok-free.dev/survey
```

That is the landing page, not the shop. It hands the respondent both logins,
links to each sign-in, and lists what is worth trying on either side -- which
the shop address does not, and a respondent arriving at a sign-in page with no
account gets no further. Set `SURVEY_FORM_URL` in `.env` and it shows a button
through to the questionnaire as well.

**Respondents see an ngrok warning page first.** "You are about to visit...",
with a **Visit Site** button. It appears once per browser and is ngrok's, not
ours; the only way to remove it is a paid plan. Worth a line in the form so
nobody assumes the link is broken: *"Click Visit Site on the first page."*

The two options below are what to do if this setup is ever rebuilt from
scratch.

## Option A -- Cloudflare (no account, nothing to sign up for)

Install once:

```bash
winget install --id Cloudflare.cloudflared
```

Then every time:

```bash
cloudflared tunnel --url http://localhost:8123
```

It prints a line like `https://random-words-here.trycloudflare.com`. That is
the link. A new one is generated each run, so paste the current one into the
Google Form each session.

## Option B -- ngrok (already installed here, needs a free account)

ngrok is on this machine but not signed in. Once only:

1. Make a free account at <https://dashboard.ngrok.com/signup>
2. Copy the authtoken it shows you
3. Run `ngrok config add-authtoken YOUR_TOKEN_HERE`

Then every time:

```bash
ngrok http 8123
```

It prints a `https://something.ngrok-free.app` forwarding address. Visitors see
a Cloudflare-style "you are about to visit" page first and click through it.

---

## What respondents should be given

Send them the **shop** address, not the admin one:

```
https://<your-tunnel-address>/shop
```

They can browse the catalogue, use the assistant, register an account and
place an order, which is the part the survey is about. Nothing stops them
reaching `/admin/login`, but there is nothing there for them without an
account, and the seeded passwords are not shown to them (see below).

---

## What protects you while the link is open

Two things happen automatically as soon as a request arrives on anything other
than `localhost`. Both are in the code, so neither depends on remembering.

**The seeded logins are shown on purpose**, so this is the one to be
deliberate about. Both sign-in pages print an account anybody can copy:
`john@example.com / customer123` on the shop, and the three staff logins on
`/admin/login`. A respondent who has to register and confirm an email before
seeing anything is a respondent who closes the tab, so the accounts are handed
over instead.

The cost is real and worth stating plainly: **whoever has the link has the
system.** The administrator account can edit the catalogue, read every
customer's details and take a backup. Take the link down when the survey
closes, take a backup before you put it up, and do not leave it running
overnight. A real deployment drops the whole box automatically, because it is
still gated on `APP_ENV` -- it is only ever shown while the environment is
`local`.

**Error pages stop talking.** The project runs with `APP_DEBUG=true`, which is
right on a laptop. Laravel's error page lists the whole environment, including
the database password and the assistant's API key, so any unhandled error on a
public link would publish both. Debug is forced off for non-local requests;
locally you keep the detailed page.

Neither of these is a reason to leave the link up longer than the survey needs.
Close the tunnel when you are done -- Ctrl+C in that window -- and the address
stops working.

---

## Google sign-in is off for the survey

`GOOGLE_SIGNIN_ENABLED=false`, so the button is not drawn and the route turns
visitors back. This is not a bug and not laziness.

Google will not accept an ngrok address as an authorised domain. Those live on
the Public Suffix List -- anybody can take a subdomain under them -- so Google
has no way to tell that this one belongs to us. Without an authorised domain
the consent screen cannot be published, and an unpublished app admits only
named test users, up to a hundred, added one Gmail address at a time. A survey
of strangers cannot work that way.

The alternatives were a domain of our own plus a paid ngrok plan, or leaving a
button that sends respondents to a Google error page. Neither is worth it when
a working login is already printed on the sign-in page.

Nothing was deleted. The credentials are still in `.env`, the code is still
there, and setting `GOOGLE_SIGNIN_ENABLED=true` brings it back on localhost,
where the callback is an address Google accepts. Register it once at
<https://console.cloud.google.com/apis/credentials?project=368877792106>:

```
http://localhost:8123/auth/google/callback
```

The callback follows whichever address the visitor arrived on, so there is
nothing to edit when switching between localhost and the tunnel.

## Worth knowing

- **The assistant costs nothing but is rate limited.** Groq's free tier allows
  roughly 8,000 tokens a minute, which is about three questions a minute across
  *all* visitors at once. Past that it falls back to the keyword assistant,
  which still answers -- respondents will not see an error, just a simpler
  answer. If several people test at the same moment, expect some of that.
- **Real email needs real mail settings.** Registration and password reset send
  mail through whatever `MAIL_*` says in `.env`. If that is not configured,
  those steps will fail for respondents.
- **Orders they place are real rows** in your database, mixed in with the demo
  data. Worth taking a backup from Admin -> Backup before you share the link,
  so you can tell survey traffic apart from your seeded figures afterwards.
- **Your laptop must stay awake.** Sleep closes the tunnel and the link dies.
