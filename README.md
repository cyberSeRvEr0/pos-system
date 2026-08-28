# POS System — Point of Sale with Inventory Management

A lightweight, fast Point of Sale system built in PHP. Supports cash, card, and mobile money payments. Works with SQLite (default) or MySQL.

## Features

- POS terminal with product grid, search & barcode scanner support
- Cart with quantity controls, tax calculation, and change calculator
- Payment methods: Cash (with change), Card (with transaction reference), Mobile Money (with SMS reference)
- Auto-generated transaction reference for cash payments
- Printable receipts (browser print, no extra software needed)
- Product & category management (CRUD)
- Inventory tracking with low-stock and out-of-stock alerts
- Sales history with filters (Today / This Month / All) and item-level detail view
- Dashboard with daily and monthly revenue stats
- Settings panel (store name, currency, tax rate, receipt footer)
- Role-based access (Admin / Cashier)
- Dual database support: SQLite (zero setup) or MySQL — switch in one line
- Clean dark UI, no external dependencies

## Requirements

- PHP 7.4+
- SQLite (built into PHP) **or** MySQL 5.7+ / MariaDB 10+
- Any modern web server (Apache, Nginx, or PHP built-in server)

## Installation

1. Upload all files to your web server (e.g., `htdocs/pos-system/`)
2. Open `http://your-domain/pos-system/install.php` in your browser
3. Enter your admin username and password
4. Click **Install & Create Admin**
5. Delete `install.php` for security

### Using MySQL instead of SQLite (optional)

Open `config/config.php` and change:

```php
define('DB_TYPE', 'mysql');   
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'pos_system');   

### File Structure

pos-system/
├── config/
│   └── config.php          ← DB connection & helpers
├── database/
│   └── pos.db              ← SQLite database (created on install)
├── public/
│   ├── register.php        ← POS terminal
│   └── checkout.php        ← Sale processing API
├── admin/
│   ├── dashboard.php       ← Stats overview
│   ├── products.php        ← Product CRUD
│   ├── categories.php      ← Category CRUD
│   ├── sales.php           ← Sales history & detail
│   ├── inventory.php       ← Stock management
│   └── settings.php        ← Store configuration
├── login.php               ← Login
├── logout.php              ← Session destroy
├── install.php             ← One-time installer
└── README.md   

Barcode Scanners
Any USB barcode scanner works out of the box. Connect the scanner, click the search bar on the POS screen, and scan. The product is added to the cart automatically. No drivers or extra software needed.

Payment Reference System
Method	Reference Source
Cash	Auto-generated (6-character code)
Card	Typed by cashier from terminal slip
Mobile	Typed by cashier from SMS notification