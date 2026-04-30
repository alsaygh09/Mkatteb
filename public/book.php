<?php

require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/lang.php';

$pageTitle = t('book_details') . ' | Mkatteb';
$book = null;
$databaseError = null;
$notFoundMessage = null;
$bookId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;

if ($bookId === false) {
    $notFoundMessage = t('book_not_found_choose');
} else {
    try {
        $book = getBook((int)$bookId);

        if ($book === null) {
            $notFoundMessage = t('book_not_found_removed');
        } else {
            $pageTitle = $book['title'] . ' | Mkatteb';
        }
    } catch (Throwable $exception) {
        $databaseError = t('book_details_unavailable');
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="container book-detail-page">
    <?php if ($databaseError): ?>
        <p class="notice"><?= e($databaseError) ?></p>
    <?php elseif ($notFoundMessage): ?>
        <p class="notice"><?= e($notFoundMessage) ?></p>
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
                alt="<?= e($book['title'] . ' ' . t('cover')) ?>"
                class="book-detail__img"
            >
            <?php if (count($bookImages) > 1): ?>
            <div class="book-gallery">
                <?php foreach ($bookImages as $image): ?>
                <img src="<?= e(bookCoverUrl($image['image_path'], $book['title'])) ?>" alt="<?= e($book['title'] . ' ' . t('photo')) ?>">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="book-detail-content">
            <h1 class="book-detail__title"><?= e($book['title']) ?></h1>
            <p class="book-detail__author"><?= e(t('by_author')) ?> <?= e($book['author']) ?></p>

            <p class="book-detail__price"><?= e(formatPrice((float)$book['price'])) ?></p>

            <dl class="book-detail__meta">
                <dt><?= e(t('stock')) ?></dt>
                <dd><?= (int)$book['stock'] ?> <?= e(t('available')) ?></dd>

                <dt><?= e(t('type')) ?></dt>
                <dd><?= e($bookType === 'official' ? t('official') : t('used')) ?></dd>

                <?php if ($bookType === 'used' && !empty($book['condition_type'])): ?>
                    <dt><?= e(t('condition')) ?></dt>
                    <dd><?= e(conditionLabel($book['condition_type'])) ?></dd>
                <?php endif; ?>

                <dt><?= e(t('status')) ?></dt>
                <dd><?= e($isAvailable ? t('available') : t('unavailable')) ?></dd>

                <?php if ($bookType === 'used'): ?>
                    <dt><?= e(t('seller')) ?></dt>
                    <dd><?= e($book['seller_name'] ?: t('unknown_seller')) ?></dd>
                <?php endif; ?>
            </dl>

            <div class="book-detail__desc">
                <h3><?= e(t('description')) ?></h3>
                <p><?= e((string)($book['description'] ?? t('no_description'))) ?></p>
            </div>

            <?php if ($isAvailable): ?>
                <form class="cart-action-form book-detail__actions" method="post" action="<?= e(url('cart.php')) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
                    <button type="submit" class="btn btn--primary"><?= e(t('add_to_cart')) ?></button>
                </form>
            <?php else: ?>
                <p class="notice"><?= e(t('not_available_add_cart')) ?></p>
            <?php endif; ?>
        </div>
    </article>
    <?php endif; ?>
</div>
<?php
require __DIR__ . '/../includes/footer.php';
