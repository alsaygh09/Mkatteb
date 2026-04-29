<?php

$pageTitle = 'Home | Mkatteb';
$featuredBooks = [];
$databaseError = null;

require __DIR__ . '/../includes/functions.php';

try {
    $featuredBooks = getFeaturedBooks();
} catch (Throwable $exception) {
    $databaseError = 'Featured books are unavailable. Import database/schema.sql and check your database settings in includes/db.php.';
}

require __DIR__ . '/../includes/header.php';
?>

<section class="intro">
    <h1>Welcome to Mkatteb</h1>
    <p>Browse selected books from our growing bookstore collection.</p>
</section>

<section class="featured-books" aria-labelledby="featured-books-title">
    <div class="section-heading">
        <h2 id="featured-books-title">Featured Books</h2>
    </div>

    <?php if ($databaseError): ?>
        <p class="notice"><?php echo htmlspecialchars($databaseError, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php elseif (empty($featuredBooks)): ?>
        <p class="notice">No featured books are available yet.</p>
    <?php else: ?>
        <div class="book-grid">
            <?php foreach ($featuredBooks as $book): ?>
                <article class="book-card">
                    <?php if (!empty($book['cover_image'])): ?>
                        <img
                            class="book-cover"
                            src="<?php echo htmlspecialchars($book['cover_image'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?> cover"
                        >
                    <?php endif; ?>

                    <div class="book-details">
                        <h3><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="book-author">by <?php echo htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><?php echo htmlspecialchars($book['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <div class="book-meta">
                        <span>BHD <?php echo number_format((float) $book['price'], 3); ?></span>
                        <span><?php echo (int) $book['stock']; ?> in stock</span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
require __DIR__ . '/../includes/footer.php';
