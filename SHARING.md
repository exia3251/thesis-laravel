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

**The seeded passwords disappear.** The admin sign-in page lists
`admin@raney.test / admin123` and the other demo logins, which is convenient on
your own screen and would hand a stranger the entire system. That block is
drawn only for requests to `localhost`, so it is simply absent on the tunnel
address. Check it yourself: open the tunnel link, go to `/admin/login`, and the
box should not be there.

**Error pages stop talking.** The project runs with `APP_DEBUG=true`, which is
right on a laptop. Laravel's error page lists the whole environment, including
the database password and the assistant's API key, so any unhandled error on a
public link would publish both. Debug is forced off for non-local requests;
locally you keep the detailed page.

Neither of these is a reason to leave the link up longer than the survey needs.
Close the tunnel when you are done -- Ctrl+C in that window -- and the address
stops working.

---

## Google sign-in over a tunnel

It will not work unless you do something first, and this is the one part of
the site that breaks silently for respondents.

Google only redirects back to an address registered on the OAuth client, and
it has to match exactly -- scheme, host, port and path. Right now
`GOOGLE_REDIRECT_URI` in `.env` says `http://localhost:8000/auth/google/callback`
while the server runs on **8123**, so it is already wrong locally.

Fix it for whichever address you are using:

1. Open <https://console.cloud.google.com/apis/credentials>, edit the OAuth
   client, and add the exact callback URL under **Authorised redirect URIs**:
   - `http://localhost:8123/auth/google/callback` for your own machine
   - `https://<your-tunnel-address>/auth/google/callback` for the survey
2. Set the matching value in `.env` as `GOOGLE_REDIRECT_URI`.

A Cloudflare quick tunnel gives a different address every run, so you would be
re-registering it each session. Two ways round that: leave Google sign-in out
of the survey and let respondents register with email and password, which
works on any address; or get a stable subdomain (a free ngrok static domain,
or a named Cloudflare tunnel) and register it once.

### Publishing the consent screen

While the app is in **Testing**, only accounts you list as test users can sign
in, up to 100 of them, and sign-ins expire after a week. That is not workable
for a survey where you do not know who is answering.

Publishing is one button: OAuth consent screen → **Publish app**. This project
asks only for `openid`, `profile` and `email`, which Google treats as
non-sensitive, so it does not need the security assessment that sensitive
scopes trigger. If you have set a logo or an app name on the consent screen
you may be asked for brand verification, which takes a few business days.

**Rotate the client secret before you publish.** The current one has been
exposed. Same page: OAuth client → add a new secret, put it in `.env` as
`GOOGLE_CLIENT_SECRET`, then delete the old one.

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
