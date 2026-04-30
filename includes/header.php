<?php
/**
 * includes/header.php
 * Session-aware navigation header.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/lang.php';

// ---------------------------------------------------------------
// Edit navbar labels here
// ---------------------------------------------------------------
$siteName = 'Mkatteb';

$navLinks = [
    ['label_key' => 'home',      'path' => 'index.php',    'roles' => ['guest', 'user', 'admin']],
    ['label_key' => 'books',     'path' => 'books.php',    'roles' => ['guest', 'user', 'admin']],
    ['label_key' => 'sell_book', 'path' => 'add-book.php', 'roles' => ['user']],
];

$adminLinks = [
    ['label_key' => 'dashboard',  'path' => 'admin/dashboard.php'],
    ['label_key' => 'books',      'path' => 'admin/books.php'],
    ['label_key' => 'users',      'path' => 'admin/users.php'],
    ['label_key' => 'orders',     'path' => 'admin/orders.php'],
    ['label_key' => 'categories', 'path' => 'admin/categories.php'],
];

if (is_file(__DIR__ . '/../public/admin/payouts.php')) {
    $adminLinks[] = ['label_key' => 'payouts', 'path' => 'admin/payouts.php'];
}

$guestActionLinks = [
    ['label_key' => 'login',    'path' => 'login.php',    'class' => 'btn btn--outline btn--sm'],
    ['label_key' => 'register', 'path' => 'register.php', 'class' => 'btn btn--primary btn--sm'],
];

$viewerRole = !isLoggedIn() ? 'guest' : (isAdmin() ? 'admin' : 'user');
$cartCount = isLoggedIn() ? getCartUniqueCount((int)currentUserId()) : 0;
?>
<!DOCTYPE html>
<html lang="<?= e(currentLang()) ?>" dir="<?= e(currentDir()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?><?= e($siteName) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
    <script src="<?= e(asset_url('js/main.js')) ?>" defer></script>
</head>
<body>

<header class="site-header">
    <div class="container header-inner">

        <a href="<?= e(app_url('index.php')) ?>" class="logo" aria-label="<?= e($siteName) ?> <?= e(t('home')) ?>">
            <span class="logo-icon">M</span>
            <span class="logo-text"><?= e($siteName) ?></span>
        </a>

        <nav class="main-nav" aria-label="Main navigation">
            <ul class="nav-list">
                <?php foreach ($navLinks as $link): ?>
                    <?php
                    if (!in_array($viewerRole, $link['roles'], true)) {
                        continue;
                    }
                    $href = url($link['path']);
                    ?>
                    <li>
                        <a href="<?= e($href) ?>"
                           class="nav-link<?= basename($_SERVER['PHP_SELF']) === basename(parse_url($href, PHP_URL_PATH)) ? ' active' : '' ?>">
                            <?= e(t($link['label_key'])) ?>
                        </a>
                    </li>
                <?php endforeach; ?>

                <?php if (isAdmin()): ?>
                    <li class="dropdown js-dropdown">
                        <button
                            type="button"
                            class="nav-link dropdown-toggle"
                            aria-expanded="false"
                            aria-haspopup="true"
                            aria-controls="adminDropdownMenu"
                        >
                            <?= e(t('admin')) ?>
                        </button>
                        <ul class="dropdown-menu" id="adminDropdownMenu" aria-label="<?= e(t('admin')) ?>">
                            <?php foreach ($adminLinks as $al): ?>
                                <li>
                                    <a href="<?= e(url($al['path'])) ?>" class="dropdown-item">
                                        <?= e(t($al['label_key'])) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="header-actions">
            <?php if (isLoggedIn()): ?>
                <a href="<?= e(app_url('cart.php')) ?>" class="btn btn--outline btn--sm">
                    <?= e(t('cart')) ?><?= $cartCount > 0 ? ' (' . e((string)$cartCount) . ')' : '' ?>
                </a>
                <a href="<?= e(app_url('account.php')) ?>" class="btn btn--outline btn--sm"><?= e(t('my_account')) ?></a>
                <a href="<?= e(app_url('logout.php')) ?>" class="btn btn--outline btn--sm"><?= e(t('logout')) ?></a>
            <?php else: ?>
                <?php foreach ($guestActionLinks as $link): ?>
                    <a href="<?= e(url($link['path'])) ?>" class="<?= e($link['class']) ?>"><?= e(t($link['label_key'])) ?></a>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="language-switch" aria-label="Language switcher">
                <a href="<?= e(languageUrl('en')) ?>" class="<?= currentLang() === 'en' ? 'active' : '' ?>">EN</a>
                <span>/</span>
                <a href="<?= e(languageUrl('ar')) ?>" class="<?= currentLang() === 'ar' ? 'active' : '' ?>">AR</a>
            </div>

            <button class="burger" id="burgerBtn" aria-label="Toggle menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>

    </div>
</header>

<div class="flash-wrap container">
    <?= flashRender() ?>
</div>

<main class="main-content">
