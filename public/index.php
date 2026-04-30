<?php
/**
 * public/index.php
 * Mkatteb homepage with translated visible labels.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lang.php';

$pageTitle  = t('online_bookstore_marketplace');
$categories = getCategories();
$pdo        = db();

$stmt = $pdo->prepare(
    "SELECT b.*, u.name AS seller_name, c.name AS category_name
     FROM books b
     LEFT JOIN users u ON b.seller_id = u.id
     LEFT JOIN categories c ON b.category_id = c.id
     WHERE b.is_active = 1 AND b.book_type = 'official'
     ORDER BY b.created_at DESC LIMIT 6"
);
$stmt->execute();
$newBooks = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT b.*, u.name AS seller_name, c.name AS category_name
     FROM books b
     LEFT JOIN users u ON b.seller_id = u.id
     LEFT JOIN categories c ON b.category_id = c.id
     WHERE b.is_active = 1
     ORDER BY b.views DESC, b.created_at DESC LIMIT 6"
);
$stmt->execute();
$popularBooks = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT b.*, u.name AS seller_name, c.name AS category_name
     FROM books b
     LEFT JOIN users u ON b.seller_id = u.id
     LEFT JOIN categories c ON b.category_id = c.id
     WHERE b.is_active = 1 AND b.book_type = 'used'
     ORDER BY b.created_at DESC LIMIT 6"
);
$stmt->execute();
$usedBooks = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT b.*, u.name AS seller_name, c.name AS category_name
     FROM books b
     LEFT JOIN users u ON b.seller_id = u.id
     LEFT JOIN categories c ON b.category_id = c.id
     WHERE b.is_active = 1
     ORDER BY b.created_at DESC LIMIT 6"
);
$stmt->execute();
$recentBooks = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';

function bookCard(array $book): string {
    $bookUrl = url('book.php?id=' . (int)$book['id']);
    $cover = bookCoverUrlFromBook($book);

    $badge = $book['book_type'] === 'official'
        ? '<span class="book-badge book-badge--official">' . e(t('official')) . '</span>'
        : '<span class="book-badge book-badge--used">' . e(t('used')) . '</span>';

    return sprintf(
        '<article class="book-card">
            <a href="%s" class="book-card__cover-link">
                <img src="%s" alt="%s" class="book-card__cover" loading="lazy">
                %s
            </a>
            <div class="book-card__body">
                <a href="%s" class="book-card__title">%s</a>
                <p class="book-card__author">%s</p>
                <div class="book-card__meta">
                    <span class="book-card__condition">%s</span>
                </div>
                <div class="book-card__footer">
                    <span class="book-card__price">%s</span>
                    <a href="%s" class="btn btn--primary btn--sm">%s</a>
                </div>
            </div>
        </article>',
        e($bookUrl),
        e($cover),
        e($book['title']),
        $badge,
        e($bookUrl),
        e($book['title']),
        e($book['author']),
        e(conditionLabel($book['condition_type'])),
        e(formatPrice((float)$book['price'])),
        e($bookUrl),
        e(t('view'))
    );
}

?>

<section class="hero">
    <div class="hero__bg"></div>
    <div class="container hero__inner">
        <div class="hero__content">
            <p class="hero__eyebrow"><?= e(t('bahrain_book_marketplace')) ?></p>
            <h1 class="hero__title"><?= e(t('discover_buy_sell_books')) ?></h1>
            <p class="hero__sub">
                <?= e(t('official_new_books')) ?> &middot;
                <?= e(t('used_books_by_local_sellers')) ?> &middot;
                <?= e(t('one_platform')) ?>.
            </p>
            <div class="hero__actions">
                <a href="<?= e(url('books.php')) ?>" class="btn btn--primary btn--lg"><?= e(t('browse_all_books')) ?></a>
                <?php if (!isLoggedIn()): ?>
                    <a href="<?= e(url('register.php')) ?>" class="btn btn--ghost btn--lg"><?= e(t('join_free')) ?></a>
                <?php else: ?>
                    <a href="<?= e(url('add-book.php')) ?>" class="btn btn--ghost btn--lg"><?= e(t('sell_book')) ?></a>
                <?php endif; ?>
            </div>
        </div>

        <form action="<?= e(url('books.php')) ?>" method="get" class="hero__search" role="search">
            <input
                type="search"
                name="q"
                class="hero__search-input"
                placeholder="<?= e(t('search_title_author')) ?>"
                aria-label="<?= e(t('search_title_author')) ?>"
            >
            <button type="submit" class="btn btn--primary"><?= e(t('search')) ?></button>
        </form>
    </div>
</section>

<div class="stats-strip">
    <div class="container stats-strip__inner">
        <div class="stat"><strong><?= e(t('official_store')) ?></strong> <?= e(t('new_books')) ?></div>
        <div class="stat"><strong><?= e(t('used_books')) ?></strong> <?= e(t('used_books_from_local_sellers')) ?></div>
        <div class="stat"><strong><?= e(t('secure_checkout')) ?></strong></div>
        <div class="stat"><strong><?= e(t('reviews_ratings')) ?></strong></div>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?= e(t('browse_by_category')) ?></h2>
            <a href="<?= e(url('books.php')) ?>" class="section-more"><?= e(t('all_books')) ?> &rarr;</a>
        </div>
        <div class="categories-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= e(url('books.php?category=' . (int)$cat['id'])) ?>" class="category-chip">
                    <span class="category-chip__icon"><?= e($cat['icon'] ?? '') ?></span>
                    <span class="category-chip__name"><?= e(localizedCategoryName($cat)) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($newBooks)): ?>
<section class="section section--alt">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?= e(t('official_store_new_books')) ?></h2>
            <a href="<?= e(url('books.php?type=official')) ?>" class="section-more"><?= e(t('see_all')) ?> &rarr;</a>
        </div>
        <div class="books-grid">
            <?php foreach ($newBooks as $book): echo bookCard($book); endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($popularBooks)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?= e(t('popular_books')) ?></h2>
            <a href="<?= e(url('books.php?sort=views')) ?>" class="section-more"><?= e(t('see_all')) ?> &rarr;</a>
        </div>
        <div class="books-grid">
            <?php foreach ($popularBooks as $book): echo bookCard($book); endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($usedBooks)): ?>
<section class="section section--alt">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?= e(t('used_books_from_local_sellers')) ?></h2>
            <a href="<?= e(url('books.php?type=used')) ?>" class="section-more"><?= e(t('browse_used')) ?> &rarr;</a>
        </div>
        <div class="books-grid">
            <?php foreach ($usedBooks as $book): echo bookCard($book); endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?= e(t('recently_added')) ?></h2>
            <a href="<?= e(url('books.php?sort=newest')) ?>" class="section-more"><?= e(t('see_all')) ?> &rarr;</a>
        </div>
        <div class="books-grid">
            <?php foreach ($recentBooks as $book): echo bookCard($book); endforeach; ?>
        </div>
    </div>
</section>

<?php if (!isLoggedIn()): ?>
<section class="cta-section">
    <div class="container cta-section__inner">
        <h2><?= e(t('have_books_gathering_dust')) ?></h2>
        <p><?= e(t('list_used_books_reach_bahrain')) ?></p>
        <a href="<?= e(url('register.php')) ?>" class="btn btn--primary btn--lg"><?= e(t('start_selling_free')) ?></a>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
