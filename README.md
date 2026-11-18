# Green Homes — by Green Hill Group

A vanilla PHP + MySQL website for **Green Homes**, a home-construction brand in Siliguri and North Bengal. Built as an inspired-by design (referencing bricknbolt.com's information architecture), with original copy and assets.

> Stack: **plain HTML/CSS/JS + PHP 8 + MySQL/MariaDB**. No npm, no Composer, no build step.

---

## Folder structure

```
green-homes/
├── public/               web root (point your dev server at this)
│   ├── index.php         front controller / router
│   ├── assets/           css, js, images
│   ├── pages/            server-rendered page partials
│   └── api/              JSON endpoints (estimate, quote, contact, auth)
├── src/                  server-side helpers (config, auth, mail, repos)
├── sql/                  schema.sql
├── storage/              mail log (created at runtime, gitignored)
└── .env.example          copy to .env and fill in real values
```

---

## Setup

### 1. Requirements

- PHP 8.0+ with `pdo_mysql` and `mbstring` enabled
- MySQL 8 / MariaDB 10.4+
- A way to serve the `public/` directory (PHP built-in server, Apache, Nginx)

### 2. Create the database

```bash
mysql -u root -p < sql/schema.sql
```

This creates the `green_homes` database with three tables: `users`, `quote_requests`, `contact_messages`.

### 3. Configure the environment

```bash
cp .env.example .env
```

Open `.env` and fill in the real DB credentials and contact details:

```ini
GH_DB_HOST=127.0.0.1
GH_DB_USER=root
GH_DB_PASS=your_password
GH_DB_NAME=green_homes

GH_CONTACT_PHONE=+91 12345 67890
GH_CONTACT_EMAIL=contact@thegreen.homes
GH_CONTACT_ADDRESS="Your real address, Siliguri, West Bengal 734001"
```

### 4. Create your first admin

The schema does **not** seed an admin (no stale default credentials). Create one through the UI:

1. `php -S 127.0.0.1:8000 -t public` (from inside this folder)
2. Visit `http://127.0.0.1:8000/signup` and create your account
3. Promote it in MySQL:
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'you@example.com';
   ```
4. Log out, log back in, and the **Admin** link appears in the nav.

### 5. Run

```bash
php -S 127.0.0.1:8000 -t public
```

Then open `http://127.0.0.1:8000`.

For Apache, point the document root at `public/` and `mod_rewrite` is handled by PHP directly. For Nginx, see "Deployment" below.

---

## What's in here

| Route             | Purpose                                              | Auth     |
|-------------------|------------------------------------------------------|----------|
| `/`               | Home — hero, services, why-us, how-it-works, FAQ     | public   |
| `/services`       | All four service areas in detail                     | public   |
| `/estimator`      | Multi-step cost calculator (JSON estimate)           | public   |
| `/how-it-works`   | 6-step process overview                              | public   |
| `/projects`       | Gallery of completed projects                        | public   |
| `/about`          | Story, mission, link to parent Green Hill Group      | public   |
| `/contact`        | Address, map, contact form                           | public   |
| `/signup`, `/login` | Account creation and login                         | public   |
| `/dashboard`      | User's own quote requests                            | logged in |
| `/quote/new`      | Multi-step request form → DB row + notification      | logged in |
| `/admin`          | All quote requests + contact messages, status update | admin    |
| `/api/estimate`   | POST → JSON cost breakdown                           | public   |
| `/api/quote`      | POST → create quote_request row                      | logged in |
| `/api/contact`    | POST → create contact_message row                    | public   |
| `/api/auth`       | POST action=login\|signup\|logout                    | public   |
| `/api/quote-status` | POST → admin status update                          | admin    |

---

## Email in dev

`mail()` is unreliable on Windows. Outgoing messages are appended to `storage/mail/*.eml` instead. Inspect them with:

```bash
ls -lt storage/mail/ | head
cat storage/mail/2026-07-13_*.eml
```

To actually send mail, set `GH_MAIL_LOG_ONLY=false` in `.env` and configure your `php.ini` `sendmail_path` or SMTP relay.

---

## Replacing placeholders

These are placeholder values you'll likely want to swap before going live:

- **Contact details** in `.env` (`GH_CONTACT_PHONE`, `GH_CONTACT_EMAIL`, `GH_CONTACT_ADDRESS`)
- **Google Maps embed URL** in `public/pages/contact.php` (currently a Siliguri centroid)
- **Project photos** in `public/assets/img/projects/` (currently SVG placeholders)
- **Testimonials** in `public/pages/home.php` (currently placeholder copy)

---

## Deployment notes (Nginx)

```nginx
server {
  listen 80;
  server_name yourdomain.com;
  root /var/www/green-homes/public;
  index index.php;

  location / {
    try_files $uri $uri/ /index.php?$query_string;
  }

  location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
  }
}
```

Set `GH_APP_ENV=production` and the `X-Frame-Options` / HSTS headers kick in automatically.

---

## License

Internal project. All rights reserved by Green Hill Group.
