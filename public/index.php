<?php
/**
 * public/index.php
 * Mkatteb Homepage — real bookstore look with multiple sections.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle  = 'Online Bookstore & Used-Books Marketplace';
$categories = getCategories();

// Homepage sections — each is a targeted query
$pdo = db();

// New official books
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

// Most viewed / popular
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

// Used books by regular users
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

// Recently added (any type)
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

/** Render a small book card */
function bookCard(array $book): string {
    $bookUrl = url('book.php?id=' . (int)$book['id']);
    $cover = bookCoverUrlFromBook($book);

    $badge = $book['book_type'] === 'official'
        ? '<span class="book-badge book-badge--official">Official</span>'
        : '<span class="book-badge book-badge--used">Used</span>';

    $condition = conditionLabel($book['condition_type']);
    $price     = formatPrice((float)$book['price']);

    return sprintf('
    <article class="book-card">
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
                <a href="%s" class="btn btn--primary btn--sm">View</a>
            </div>
        </div>
    </article>',
        e($bookUrl), e($cover), e($book['title']),
        $badge,
        e($bookUrl), e($book['title']),
        e($book['author']),
        e($condition),
        e($price),
        e($bookUrl)
    );
}
?>

<!-- ==================== HERO ==================== -->
<section class="hero">
    <div class="hero__bg"></div>
    <div class="container hero__inner">
        <div class="hero__content">
            <p class="hero__eyebrow">📚 Bahrain's Book Marketplace</p>
            <h1 class="hero__title">Discover, Buy &amp; Sell Books</h1>
            <p class="hero__sub">Official new books · Used books by local sellers · One platform.</p>
            <div class="hero__actions">
                <a href="<?= e(url('books.php')) ?>" class="btn btn--primary btn--lg">Browse All Books</a>
                <?php if (!isLoggedIn()): ?>
                <a href="<?= e(url('register.php')) ?>" class="btn btn--ghost btn--lg">Join Free</a>
                <?php else: ?>
                <a href="<?= e(url('add-book.php')) ?>" class="btn btn--ghost btn--lg">Sell a Book</a>
                <?php endif; ?>
            </div>
        </div>
        <!-- Hero search -->
        <form action="<?= e(url('books.php')) ?>" method="get" class="hero__search" role="search">
            <input type="search" name="q" class="hero__search-input"
                   placeholder="Search by title or author" aria-label="Search books">
            <button type="submit" class="btn btn--primary">Search</button>
        </form>
    </div>
</section>

<!-- ==================== STATS STRIP ==================== -->
<div class="stats-strip">
    <div class="container stats-strip__inner">
        <div class="stat">📖 <strong>Official Store</strong> New Books</div>
        <div class="stat">🔄 <strong>Used Books</strong> From Local Sellers</div>
        <div class="stat">📦 <strong>Secure</strong> Checkout</div>
        <div class="stat">⭐ <strong>Reviews</strong> & Ratings</div>
    </div>
</div>

<!-- ==================== CATEGORIES ==================== -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Browse by Category</h2>
            <a href="<?= e(url('books.php')) ?>" class="section-more">All Books →</a>
        </div>
        <div class="categories-grid">
            <?php foreach ($categories as $cat): ?>
            <a href="<?= e(url('books.php?category=' . (int)$cat['id'])) ?>" class="category-chip">
                <span class="category-chip__icon"><?= e($cat['icon'] ?? '📚') ?></span>
                <span class="category-chip__name"><?= e($cat['name']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==================== NEW OFFICIAL BOOKS ==================== -->
<?php if (!empty($newBooks)): ?>
<section class="section section--alt">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">🏪 Official Store — New Books</h2>
            <a href="<?= e(url('books.php?type=official')) ?>" class="section-more">See all →</a>
        </div>
        <div class="books-grid">
            <?php foreach ($newBooks as $book): echo bookCard($book); endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ==================== POPULAR BOOKS ==================== -->
<?php if (!empty($popularBooks)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">🔥 Popular Books</h2>
            <a href="<?= e(url('books.php?sort=views')) ?>" class="section-more">See all →</a>
        </div>
        <div class="books-grid">
            <?php foreach ($popularBooks as $book): echo bookCard($book); endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ==================== USED BOOKS ==================== -->
<?php if (!empty($usedBooks)): ?>
<section class="section section--alt">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">♻️ Used Books from Sellers</h2>
            <a href="<?= e(url('books.php?type=used')) ?>" class="section-more">Browse used →</a>
        </div>
        <?php if (empty($usedBooks)): ?>
        <div class="empty-state empty-state--inline">
            <span class="empty-state__icon">🛒</span>
            <p>No used books yet. <a href="<?= e(url('add-book.php')) ?>">Be the first to sell!</a></p>
        </div>
        <?php else: ?>
        <div class="books-grid">
            <?php foreach ($usedBooks as $book): echo bookCard($book); endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- ==================== RECENTLY ADDED ==================== -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">🆕 Recently Added</h2>
            <a href="<?= e(url('books.php?sort=newest')) ?>" class="section-more">See all →</a>
        </div>
        <div class="books-grid">
            <?php foreach ($recentBooks as $book): echo bookCard($book); endforeach; ?>
        </div>
    </div>
</section>

<!-- ==================== CTA ==================== -->
<?php if (!isLoggedIn()): ?>
<section class="cta-section">
    <div class="container cta-section__inner">
        <h2>Have books gathering dust?</h2>
        <p>List your used books and reach buyers across Bahrain.</p>
        <a href="<?= e(url('register.php')) ?>" class="btn btn--primary btn--lg">Start Selling Free</a>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
