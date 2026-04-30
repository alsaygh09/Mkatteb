# Mkatteb

## Project Overview

Mkatteb is a PHP and MySQL online bookstore and used-books marketplace. It supports browsing books, filtering by category and price, selling used books, cart management, Cash on Delivery checkout, user accounts, and admin management tools.

## Main Features

- Public homepage with featured, official, used, popular, and recent books.
- Book browsing with search, category filter, type filter, condition filter, availability filter, max-price slider, and sorting.
- Book details page with multiple book photos.
- User registration, login, account profile, and personal book listings.
- Used-book listing flow for logged-in users.
- User-specific cart with quantity validation, remove item, and clear cart.
- Cash on Delivery checkout with delivery details and order history.
- Admin dashboard for books, users, orders, and categories.
- Admin role-based navigation and access control.
- Bilingual language structure with English defaults and Arabic placeholders.
- Central URL helpers for running from `/Mkatteb/public/`.

## Tech Stack

- PHP
- MySQL / MariaDB
- PDO prepared statements
- HTML5
- CSS3
- JavaScript
- XAMPP / Apache
- phpMyAdmin

## Folder Structure

```text
Mkatteb/
|-- database/
|   `-- schema.sql              # Database schema and seed data
|-- includes/
|   |-- auth.php                # Authentication and access helpers
|   |-- db.php                  # Database connection settings
|   |-- footer.php              # Shared footer
|   |-- functions.php           # URL, book, cart, upload, and utility helpers
|   |-- header.php              # Shared header and navigation
|   `-- lang.php                # Translation arrays and language helper
|-- public/
|   |-- admin/                  # Admin pages
|   |-- assets/
|   |   |-- css/style.css       # Main stylesheet
|   |   `-- js/main.js          # Shared JavaScript
|   |-- uploads/books/          # Uploaded book images
|   |-- index.php               # Homepage
|   |-- books.php               # Book listing and filters
|   |-- book.php                # Book details
|   |-- cart.php                # Shopping cart
|   |-- checkout.php            # Cash on Delivery checkout
|   |-- orders.php              # User order history
|   |-- add-book.php            # Add/edit book listing
|   |-- login.php
|   |-- register.php
|   |-- logout.php
|   `-- account.php
`-- README.md
```

## Database Setup Using phpMyAdmin

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Open phpMyAdmin:

   ```text
   http://localhost/phpmyadmin/
   ```

3. Import the database schema:
   - Click **Import**.
   - Choose `database/schema.sql`.
   - Click **Go**.

4. The SQL file creates and uses this database:

   ```sql
   mkatteb
   ```

5. Confirm the database connection settings in `includes/db.php`:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'mkatteb');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

   These are the default XAMPP MySQL settings. Update them only if your local MySQL user or password is different.

## Default Admin Login

The schema seeds a default admin account.

```text
Email: owner@book.io
Password: books
```

The password is stored as a `password_hash()` compatible hash, not plain text.

## How to Run With XAMPP

1. Place the project folder here:

   ```text
   C:\Projects\PHPtrail\htdocs\Mkatteb
   ```

2. Start Apache and MySQL in XAMPP.
3. Import `database/schema.sql` in phpMyAdmin.
4. Open the site in your browser:

   ```text
   http://localhost/Mkatteb/public/
   ```

5. Admin pages are available after logging in as the admin user:

   ```text
   http://localhost/Mkatteb/public/admin/dashboard.php
   ```

## Testing Checklist

- Homepage loads at `http://localhost/Mkatteb/public/`.
- Register a new user.
- Login and logout work.
- Default admin login works with `owner@book.io` / `books`.
- Admin link appears only for admin users.
- Browse books page loads and filters work:
  - search
  - category
  - book type
  - condition
  - availability
  - max price slider
  - sort order
- Category URLs work, for example `books.php?category=2`.
- Book details page loads and images display.
- Logged-in users can add a book listing.
- Multiple book images can be uploaded.
- Add to cart works from a book details page.
- Cart quantity cannot exceed stock.
- Clear cart asks for confirmation.
- Checkout creates a Cash on Delivery order.
- Cart clears only after successful checkout.
- User can view order history.
- Admin can manage books, users, orders, and categories.
- Dangerous admin actions show confirmation prompts.
- PHP syntax check passes:

   ```powershell
   Get-ChildItem -Recurse -Filter *.php | ForEach-Object { C:\Projects\PHPtrail\php\php.exe -l $_.FullName }
   ```

## Screenshots

Add screenshots here later.

```text
public/assets/screenshots/homepage.png
public/assets/screenshots/books-filters.png
public/assets/screenshots/book-details.png
public/assets/screenshots/cart.png
public/assets/screenshots/admin-dashboard.png
```
