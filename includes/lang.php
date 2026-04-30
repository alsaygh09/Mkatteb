<?php
/**
 * includes/lang.php
 * Small bilingual language layer.
 *
 * Add Arabic text manually in the 'ar' array below.
 * Empty Arabic values intentionally fall back to English through t().
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$supportedLanguages = ['en', 'ar'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLanguages, true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (empty($_SESSION['lang']) || !in_array($_SESSION['lang'], $supportedLanguages, true)) {
    $_SESSION['lang'] = 'en';
}

$translations = [
    'en' => [
        'home' => 'Home',
        'books' => 'Books',
        'browse_books' => 'Browse Books',
        'sell_book' => 'Sell a Book',
        'sell_used_book' => 'Sell Used Book',
        'cart' => 'Cart',
        'my_account' => 'My Account',
        'login' => 'Login',
        'register' => 'Register',
        'logout' => 'Logout',
        'admin' => 'Admin',
        'search' => 'Search',
        'add_to_cart' => 'Add to Cart',
        'continue_browsing' => 'Continue Browsing',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'submit' => 'Submit',
        'new_books' => 'New Books',
        'used_books' => 'Used Books',
        'popular_books' => 'Popular Books',
        'recently_added' => 'Recently Added',
        'browse_by_category' => 'Browse by Category',
        'book_details' => 'Book Details',
        'price' => 'Price',
        'stock' => 'Stock',
        'condition' => 'Condition',
        'seller' => 'Seller',
        'available' => 'Available',
        'sold_out' => 'Sold Out',
        'list_a_book' => 'List a Book',
        'title' => 'Title',
        'author' => 'Author',
        'description' => 'Description',
        'category' => 'Category',
        'upload_images' => 'Upload Images',
        'dashboard' => 'Dashboard',
        'users' => 'Users',
        'orders' => 'Orders',
        'categories' => 'Categories',
        'payouts' => 'Payouts',
    ],

    // Write Arabic text manually here later. Empty strings fall back to English.
    'ar' => [
        'home' => '',
        'books' => '',
        'browse_books' => '',
        'sell_book' => '',
        'sell_used_book' => '',
        'cart' => '',
        'my_account' => '',
        'login' => '',
        'register' => '',
        'logout' => '',
        'admin' => '',
        'search' => '',
        'add_to_cart' => '',
        'continue_browsing' => '',
        'save' => '',
        'cancel' => '',
        'submit' => '',
        'new_books' => '',
        'used_books' => '',
        'popular_books' => '',
        'recently_added' => '',
        'browse_by_category' => '',
        'book_details' => '',
        'price' => '',
        'stock' => '',
        'condition' => '',
        'seller' => '',
        'available' => '',
        'sold_out' => '',
        'list_a_book' => '',
        'title' => '',
        'author' => '',
        'description' => '',
        'category' => '',
        'upload_images' => '',
        'dashboard' => '',
        'users' => '',
        'orders' => '',
        'categories' => '',
        'payouts' => '',
    ],
];

function currentLang(): string {
    return $_SESSION['lang'] ?? 'en';
}

function currentDir(): string {
    return currentLang() === 'ar' ? 'rtl' : 'ltr';
}

function t(string $key): string {
    global $translations;

    $lang = currentLang();
    $text = $translations[$lang][$key] ?? '';

    if ($text === '') {
        $text = $translations['en'][$key] ?? $key;
    }

    return $text;
}

function languageUrl(string $lang): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $query = [];
    parse_str(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?: '', $query);
    $query['lang'] = $lang;

    return $path . '?' . http_build_query($query);
}
