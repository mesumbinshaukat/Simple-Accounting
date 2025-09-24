# Accounting (Core PHP)

A minimal, clean, and responsive accounting dashboard built with core PHP and MySQL (XAMPP). Create accounts, post debit/credit transactions, and see balances in a simple table with light theme and toast alerts.

## Features

- **Minimal UI** with a responsive sidebar and light theme
- **Create account** and **Debit/Credit** transactions
- **Auto database setup** on first run (tables are created automatically)
- **Running balance per account** and a transactions table
- **Simple toast notifications** for create/debit/credit actions

## Requirements

- XAMPP (Apache + MySQL)
- PHP 7.4+ (modern PHP works as well)

## Setup (Local, XAMPP)

1. Copy this folder `simple_accounting/` into your XAMPP `htdocs/` directory:
   - Windows default: `C:\\xampp\\htdocs\\simple_accounting`
2. Start Apache and MySQL from XAMPP Control Panel.
3. Visit in your browser:
   - `http://localhost/simple_accounting/`
4. On first load, the app will:
   - Create database `simple_accounting` if it doesn't exist
   - Create tables `accounts` and `transactions`

No manual SQL steps required.

## Default DB Credentials

Configured for XAMPP defaults in `config.php`:

```php
return [
  'db_host' => 'localhost',
  'db_user' => 'root',
  'db_pass' => '',
  'db_name' => 'simple_accounting',
  'app_name' => 'Accounting',
];
```

If your MySQL password differs, update `'db_pass'` accordingly.

## Project Structure

- `index.php` — Dashboard: list accounts with balances
- `account_new.php` — Create a new account
- `account.php` — Account details: debit/credit forms and transactions table
- `db.php` — DB connection + auto-migrations
- `functions.php` — Helpers (flash toasts, balance calc, etc.)
- `layout/header.php`, `layout/footer.php` — Page chrome
- `assets/style.css` — Light theme CSS (responsive)
- `assets/app.js` — Sidebar toggle and toast logic
- `config.php` — App and DB config

## Usage

1. Open the app at `http://localhost/simple_accounting/`.
2. Click "New Account" and create an account (e.g., Cash, Wallet).
3. Open the account and post a Credit or Debit with an optional note.
4. The balance and transactions table update immediately. Toasts confirm actions.

## Notes

- Paths assume the app lives at `/simple_accounting/` under your Apache root.
- Uses `mysqli` and prepared statements for inserts/selects.
- Toasts are minimal vanilla JS, no external libraries.

## New: REST API and PWA

This project now includes a local REST API and a React-based PWA (keeps the same simple UI and styling):

- Web UI (PHP): `http://localhost/simple_accounting/`
- PWA (React): `http://localhost/simple_accounting/pwa/index.html`

### REST API Endpoints

- POST `/simple_accounting/api/login.php` — `{ username, password }`
- POST `/simple_accounting/api/logout.php`
- POST `/simple_accounting/api/register.php` — `{ username, password }`
- GET `/simple_accounting/api/me.php`
- GET `/simple_accounting/api/accounts.php`
- POST `/simple_accounting/api/accounts.php` — `{ name }`
- GET `/simple_accounting/api/transactions.php?account_id=ID`
- POST `/simple_accounting/api/transactions.php` — `{ account_id, action: 'credit'|'debit', amount, note?, transfer_to? }`
  - If `action = debit` and `transfer_to` is provided, a matching credit is written to the target account.
- GET `/simple_accounting/api/dump.php` — full data snapshot for offline cache
- POST `/simple_accounting/api/batch.php` — `{ actions: [...] }` to apply queued offline actions

### PWA (Offline-first enhancements)

- Uses the same CSS (`assets/style.css`) to keep UI consistent.
- Caches static assets via Service Worker (`pwa/sw.js`).
- Stores data locally (localStorage JSON) for offline mode.
- Queues POST actions offline and syncs via `api/batch.php` when back online.

### Desktop (no Laravel)

- NativePHP targets Laravel. To avoid complexity, package the PWA with a lightweight wrapper (Tauri/Electron/PHP Desktop) pointing to:
  - `http://localhost/simple_accounting/pwa/index.html` (use XAMPP Apache), or
  - Bundle a tiny PHP server and open the same URL in a native window.

## License

MIT — do whatever you want; attribution appreciated.
