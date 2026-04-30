<?php

$pageTitle = 'Book Details | Mkatteb';
$book = null;
$databaseError = null;
$notFoundMessage = null;
$bookId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;

require __DIR__ . '/../includes/functions.php';

if ($bookId === false) {
    $notFoundMessage = 'Book not found. Please choose a book from the books page.';
} else {
    try {
        $book = getBook((int) $bookId);

        if ($book === null) {
            $notFoundMessage = 'Book not found. It may have been removed or is no longer available.';
        } else {
            $pageTitle = $book['title'] . ' | Mkatteb';
        }
    } catch (Throwable $exception) {
        $databaseError = 'Book details are unavailable. Please try again later.';
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="container book-detail-page">
    <?php if ($databaseError): ?>
        <p class="notice"><?php echo htmlspecialchars($databaseError, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php elseif ($notFoundMessage): ?>
        <p class="notice"><?php echo htmlspecialchars($notFoundMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php elseif ($book): ?>
        <?php
        $bookType = $book['book_type'] ?? 'official';
        $isAvailable = !empty($book['is_active']) && (int)$book['stock'] > 0;
        $bookImages = getBookImages((int)$book['id'], $book['cover_image'] ?? null);
        ?>

    <article class="book-detail-grid">
        <div class="book-detail-cover">
            <img
                src="<?= e(bookCoverUrlFromBook($book)) ?>"
                alt="<?= e($book['title']) ?> cover"
                class="book-detail__img"
            >
            <?php if (count($bookImages) > 1): ?>
            <div class="book-gallery">
                <?php foreach ($bookImages as $image): ?>
                <img src="<?= e(bookCoverUrl($image['image_path'], $book['title'])) ?>" alt="<?= e($book['title']) ?> photo">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="book-detail-content">
            <h1 class="book-detail__title"><?= e($book['title']) ?></h1>
            <p class="book-detail__author">by <?= e($book['author']) ?></p>

            <p class="book-detail__price"><?= e(formatPrice((float)$book['price'])) ?></p>

            <dl class="book-detail__meta">
                <dt>Stock</dt>
                <dd><?php echo (int) $book['stock']; ?> available</dd>

                <dt>Type</dt>
                <dd><?= e(ucfirst($bookType)) ?></dd>

                <?php if ($bookType === 'used' && !empty($book['condition_type'])): ?>
                    <dt>Condition</dt>
                    <dd><?= e(conditionLabel($book['condition_type'])) ?></dd>
                <?php endif; ?>

                <dt>Status</dt>
                <dd><?= $isAvailable ? 'Available' : 'Unavailable' ?></dd>

                <?php if ($bookType === 'used'): ?>
                    <dt>Seller</dt>
                    <dd><?= e($book['seller_name'] ?: 'Unknown seller') ?></dd>
                <?php endif; ?>
            </dl>

            <div class="book-detail__desc">
                <h3>Description</h3>
                <p><?= e((string)($book['description'] ?? 'No description provided.')) ?></p>
            </div>

            <?php if ($isAvailable): ?>
                <form class="cart-action-form book-detail__actions" method="post" action="<?= e(url('cart.php')) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                    <button type="submit" class="btn btn--primary">Add to Cart</button>
                </form>
            <?php else: ?>
                <p class="notice">This book is not available to add to the cart.</p>
            <?php endif; ?>
        </div>
    </article>
    <?php endif; ?>
</div>
<?php
require __DIR__ . '/../includes/footer.php';
