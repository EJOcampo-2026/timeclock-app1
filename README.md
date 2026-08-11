# Timecard — Employee Time In/Out (HTML/CSS/JS + PHP + MySQL/phpMyAdmin)

A simple time clock: employees log in, hit a big Clock In / Clock Out
button, and see their own recent history. Admins get a separate page with
a filterable table of everyone's time logs.

## Stack
- **Frontend:** HTML, CSS, vanilla JavaScript (fetch API)
- **Backend:** PHP (PDO + MySQL, session-based auth)
- **Database:** MySQL, set up via phpMyAdmin

## File overview
| File | Purpose |
|---|---|
| `database.sql` | Creates `timeclock_db`, the `users` table (with a `role`: `employee`/`admin`), and `time_logs` |
| `config.php` | DB connection, session bootstrap, `requireLogin()` / `requireAdmin()` helpers |
| `auth.php` | `login`, `logout`, `check` — self-registration is off |
| `api.php` | Employee-facing: `status`, `clock_in`, `clock_out`, `history` (own logs only) |
| `admin_api.php` | Admin-only: `employees` (for the filter dropdown), `logs` (everyone's logs, filterable) |
| `login.html` / `login.js` | Login form |
| `index.html` / `script.js` | Employee dashboard: live clock, clock in/out button, own history table |
| `admin.html` / `admin.js` | Admin dashboard: stats + filterable table of all logs |
| `style.css` | Shared styling for all pages |

## Setup

1. **Copy this folder** into your web root, e.g.
   `C:\xampp\htdocs\timeclock-app` (XAMPP) or `C:\wamp64\www\timeclock-app` (WAMP).

2. **Start Apache and MySQL.**

3. **Import the database:** phpMyAdmin → **Import** → choose `database.sql` → **Go**.
   This creates `timeclock_db` with empty `users` and `time_logs` tables.

4. **Add your accounts manually** (self-registration is off, same as the
   Stockroom app). In phpMyAdmin → `timeclock_db` → `users` → **Insert** tab:
   - `username`, `email` — whatever you like
   - `password_hash` — type the **plain password directly** into this field
     (no hashing, to keep manual entry simple — see note below)
   - `role` — `admin` for anyone who should see the admin dashboard,
     `employee` for everyone else

   Or paste this into the **SQL** tab, editing the values first:
   ```sql
   INSERT INTO users (username, email, password_hash, role) VALUES
   ('admin', 'admin@example.com', 'admin123', 'admin'),
   ('jdoe', 'jdoe@example.com', 'password123', 'employee');
   ```

5. **Open the app:**
   ```
   http://localhost/timeclock-app/login.html
   ```
   Employees land on the clock in/out screen (`index.html`) after logging
   in. Admins land on `admin.html` automatically, and can still reach their
   own timecard via the "My timecard" link.

⚠️ **Passwords are stored as plain text**, not hashed, to keep manual
phpMyAdmin entry simple (matching how the Stockroom app was set up). Don't
reuse real/important passwords for these accounts, and don't deploy this
anywhere public without adding proper hashing back in.

## How it works

- **Clock in/out logic:** each clock-in creates a new row in `time_logs`
  with `time_out` left `NULL`. Clocking out finds that employee's open row
  and fills in `time_out`. An employee can't clock in twice without
  clocking out first (the API blocks it with a 409).
- **Roles:** `config.php`'s `requireAdmin()` blocks `admin_api.php` for
  anyone whose session role isn't `admin` — so employees can't reach the
  all-logs view even by guessing the URL.
- **Duration** is computed in SQL with `TIMESTAMPDIFF`, live (using `NOW()`)
  for entries still clocked in, so the admin table shows a running total
  for anyone currently on the clock.
