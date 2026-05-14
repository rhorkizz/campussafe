# Hosting CampusSafe (PHP + MySQL)

GitHub stores your code only. To use the app in a browser, deploy to **shared PHP + MySQL hosting** (similar to WAMP). Netlify and GitHub Pages cannot run this stack.

## 1. Choose a provider

Any host offering **PHP 7.4+** (8.x preferred), **MySQL** or **MariaDB**, and **FTP/SFTP** or a file manager works.

**Free tier example:** [InfinityFree](https://www.infinityfree.net/) (PHP + MySQL, subdomain). Limits and terms apply; fine for demos and coursework.

**Paid / student:** inexpensive **cPanel** hosting is usually the least painful (one-click DB, phpMyAdmin, clear doc root).

---

## 2. Create the MySQL database

In the control panel:

1. Create a **MySQL database** and a **user** with **all privileges** on that database.
2. Copy the connection details exactly:
   - **Host** (often *not* `localhost` — e.g. `sqlXXX.epizy.com` on InfinityFree)
   - **Port** (usually `3306`)
   - **Database name**, **username**, **password**

---

## 3. Import the schema

1. Open **phpMyAdmin** (or your host’s SQL import tool).
2. **Select** the empty database you created (do not create a new DB from SQL).
3. **Import** the file:  
   `database/schema_shared_hosting.sql`  
   (This version omits `CREATE DATABASE` / `USE`, which shared hosts usually forbid.)

If you need the attachment column and your local DB has it, also run `database/migration_add_attachment.sql` after the import (only if that migration is not already reflected in your tables).

---

## 4. Upload the project

Upload **all application folders and files** so the **web root** contains `index.php` next to `config/`, `views/`, `assets/`, etc. (same layout as on your PC).

- **Typical doc root:** `htdocs`, `public_html`, or `www`.
- Upload via **FTP** (e.g. FileZilla) or the host’s **online file manager**.

---

## 5. Configure the database connection

**Never commit production passwords to Git.**

Pick **one** approach:

### A. Environment variables (if your host exposes them)

`config/db.php` reads: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.  
If redirects or links break, set `BASE_URL` to your public URL **without** a trailing slash (e.g. `https://yoursite.com` or `https://yoursite.com/CampusSafe`).

### B. Edit `config/db.php` on the server only

Via FTP/file manager, set the `define` defaults (or the `_dbEnv` fallbacks) to match the host’s MySQL panel. Do **not** push those edits to GitHub.

### C. Apache `SetEnv` (sometimes works)

If the host runs Apache and passes variables to PHP, you can try adding to the **root** `.htaccess` (adjust values):

```apache
SetEnv DB_HOST "your-mysql-host.example.com"
SetEnv DB_PORT "3306"
SetEnv DB_NAME "your_database_name"
SetEnv DB_USER "your_database_user"
SetEnv DB_PASS "your_database_password"
```

If PHP does not see these, use **A** or **B**.

---

## 6. Email (password reset)

On the server:

1. Copy `config/mail.example.php` to `config/mail.php`.
2. Fill in SMTP settings (same idea as on WAMP).  
   `mail.php` is listed in `.gitignore` so it is not pushed to GitHub.

---

## 7. Uploads folder

Ensure `uploads/` exists and is **writable** by the web server if you use incident attachments. The repo keeps `uploads/.htaccess`; uploaded files stay untracked.

---

## 8. Production hygiene

- Remove or **block public access** to dev-only scripts (`debug_*.php`, `test_*.php`, `tmp_*.php`, etc.) before sharing the URL widely.
- Change default **demo passwords** in the database for anything beyond a class demo (see comments in `database/schema.sql` for sample credentials).

---

## 9. Ongoing workflow

- Develop locally → **commit/push** to [GitHub](https://github.com/Rhorkizz/CampusSafe).
- After changes, **re-upload** changed files (or use the host’s Git deploy if they offer it).

If you tell your assistant which **exact host** you signed up for, they can map these steps to that panel’s labels (where MySQL host appears, doc root name, etc.).
