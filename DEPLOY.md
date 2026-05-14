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

## 8. First login (sample accounts)

Use **User ID** (not email) on the login form. Passwords are **case-sensitive**.

| User ID   | Password   | Role        |
|-----------|------------|-------------|
| `ADMIN001` | `admin123` | Admin       |
| `STAFF001` | `staff123` | Officer     |
| `UPSA001`  | `2001-05-14` | Student (date format `YYYY-MM-DD`) |
| `UPSA002`  | `2002-09-22` | Student   |
| `UPSA003`  | `2000-12-03` | Student   |
| `HOST001`  | `hostel123` | Hostel officer |

If these rows are missing, re-import `database/schema_shared_hosting.sql` into the correct database (see below).

---

## 9. Login still fails?

1. **Confirm data exists** — In phpMyAdmin, select your app database → **SQL**:
   ```sql
   SELECT user_id, LENGTH(password) AS hash_len, LEFT(password, 4) AS hash_start FROM users LIMIT 5;
   ```
   - You should see rows. If **no rows**, the schema import did not run on this database (wrong DB selected, or import errored).
   - `hash_len` should be **60** and `hash_start` should be **`$2y$`** (bcrypt). If `hash_len` is shorter, the column was truncated or the import was corrupted — re-import or fix the column type (`VARCHAR(255)`).

2. **Confirm the app uses that database** — Wrong `DB_NAME` / credentials in `config/db.php` or env vars can point to an **empty** database while phpMyAdmin shows data in another.

3. **Plain-text passwords in the database** — If `LENGTH(password)` is about **8–14** and the value starts with a **date** or `staff`, the column holds **plain text**, not bcrypt. The app cannot log you in until hashes are fixed. In phpMyAdmin → **SQL**, run the script **`database/fix_passwords_to_bcrypt.sql`** from this repository (paste its contents), then verify again: `hash_len` should be **60** and `start` should be **`$2y$10`**.

4. **Reload before retry** — If you see “Invalid session token”, reload the page once (CSRF). The message **“Invalid user ID or password”** means CSRF passed but the user was not found or the password did not match.

5. **Redeploy updated PHP** — Upload the latest `models/User.php` and `controllers/StudentController.php` from the repo: login now matches **User ID case-insensitively** (`upsa001` = `UPSA001`) and tolerates stray whitespace around stored password hashes.

6. **Logo / assets on Linux hosting** — URLs are now built with **`app_url()`** so `/pictures/logo.jpg` resolves at the domain root. Ensure the folder on the server is exactly **`pictures`** (all lowercase) and contains **`logo.jpg`**; Linux is case-sensitive (`Pictures` ≠ `pictures`).

---

## 10. Production hygiene

- Remove or **block public access** to dev-only scripts (`debug_*.php`, `test_*.php`, `tmp_*.php`, etc.) before sharing the URL widely.
- Change default **demo passwords** in the database for anything beyond a class demo (see comments in `database/schema.sql` for sample credentials).

---

## 11. Ongoing workflow

- Develop locally → **commit/push** to [GitHub](https://github.com/Rhorkizz/CampusSafe).
- After changes, **re-upload** changed files (or use the host’s Git deploy if they offer it).

If you tell your assistant which **exact host** you signed up for, they can map these steps to that panel’s labels (where MySQL host appears, doc root name, etc.).
