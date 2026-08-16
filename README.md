# KhajaTime — School/College Cafeteria Ordering System

A complete, framework-free PHP + MySQL web app for pre-ordering food at a school/college
cafeteria: students order ahead and get a pickup token, kitchen staff manage the live menu
and work an order queue.

## Features

**Students**
- Register/login with email + 4-digit PIN
- Browse menu with search + category filters
- Add items to cart, adjust quantities, place order
- Get a pickup token (e.g. `#47`) and live order status (auto-refreshes until ready)
- View past orders

**Kitchen staff**
- Register/login (same screen, toggle to "I'm kitchen staff")
- Order Queue: see incoming orders, tick off items as prepared, mark orders ready
  (auto-flags orders as "Urgent" if they've been waiting 15+ minutes)
- Menu Manager: add new dishes with photo upload, edit existing ones, toggle
  availability on/off instantly

## Requirements

- XAMPP (Apache + MySQL + PHP 8+) — https://www.apachefriends.org/

## Setup

1. **Copy the project into `htdocs`**
   Copy the entire `khajatime` folder into your XAMPP `htdocs` directory, e.g.:
   - Windows: `C:\xampp\htdocs\khajatime`
   - macOS: `/Applications/XAMPP/htdocs/khajatime`
   - Linux: `/opt/lampp/htdocs/khajatime`

2. **Start Apache and MySQL** from the XAMPP Control Panel.

3. **Create the database**
   - Open `http://localhost/phpmyadmin`
   - Click **Import**, choose `database/khajatime.sql`, click **Go**
   - This creates the `khajatime` database, all tables, some starter menu items, and
     two demo accounts.

4. **Check your DB credentials**
   `config/database.php` uses the default XAMPP MySQL settings (`root` / no password).
   If yours differ, edit that file.

5. **Visit the site**
   `http://localhost/khajatime/`

## Demo accounts

| Role    | Email                          | PIN  |
|---------|---------------------------------|------|
| Student | anil.shrestha@swsc.edu.np       | 1234 |
| Kitchen | kitchen@swsc.edu.np             | 1234 |

Or just register a new account from the login screen — the same page lets you toggle
between "I'm a student" and "I'm kitchen staff".

## Project structure

```
khajatime/
├── config/database.php        # DB connection settings
├── database/khajatime.sql     # Full schema + seed data — import this first
├── includes/
│   ├── auth.php                # session/login helpers
│   ├── functions.php           # cart, pricing, token helpers
│   ├── header.php / footer.php # shared layout + nav (role-aware)
├── assets/
│   ├── css/style.css           # all styling
│   ├── js/                     # menu.js, cart.js, kitchen-queue.js, kitchen-menu.js
│   └── uploads/                # uploaded food photos land here
├── api/                        # AJAX endpoints (cart, orders, kitchen actions)
├── index.php                   # login
├── register.php                # registration (student/kitchen toggle)
├── menu.php                    # student menu + ordering
├── cart.php                    # order review
├── order-status.php            # token + live status
├── orders.php                  # student order history
├── account.php                 # account info + logout
├── kitchen-queue.php           # kitchen: order queue
└── kitchen-menu.php            # kitchen: menu manager
```
