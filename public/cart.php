<?php
/**
 * public/cart.php
 * User-specific shopping cart.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('login.php');

$pageTitle = t('my_cart');
$userId = (int)currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';
    $bookId = (int)($_POST['book_id'] ?? 0);

    if ($action === 'add' && $bookId > 0) {
        $result = addToCart($userId, $bookId, 1);
        flashSet(isset($result['error']) ? 'error' : 'success', $result['error'] ?? t('flash_book_added_cart'));
    } elseif ($action === 'update_quantity' && $bookId > 0) {
        $quantity = (int)($_POST['quantity'] ?? 1);
        $result = updateCartQuantity($userId, $bookId, $quantity);
        if (isset($result['error'])) {
            flashSet('error', $result['error']);
        }
    } elseif ($action === 'remove' && $bookId > 0) {
        removeCartItem($userId, $bookId);
        flashSet('success', t('flash_item_removed_cart'));
    } elseif ($action === 'clear') {
        clearCart($userId);
        flashSet('success', t('flash_cart_cleared'));
    }

    redirect('cart.php');
}

$cartItems = getCartItems($userId);
$total = 0.0;

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><?= e(t('shopping_cart')) ?></h1>
    </div>

    <?php if (empty($cartItems)): ?>
        <div class="empty-state">
            <span class="empty-state__icon"><?= e(t('cart')) ?></span>
            <p><?= e(t('your_cart_empty')) ?></p>
            <a href="<?= e(url('books.php')) ?>" class="btn btn--primary"><?= e(t('continue_browsing')) ?></a>
        </div>
    <?php else: ?>
        <section class="cart-layout">
            <div class="cart-items card">
                <?php foreach ($cartItems as $item): ?>
                    <?php
                    $stock = (int)$item['stock'];
                    $quantity = min((int)$item['quantity'], $stock);
                    $lineTotal = $quantity * (float)$item['price'];
                    $total += $lineTotal;
                    ?>
                    <article class="cart-item">
                        <a href="<?= e(url('book.php?id=' . (int)$item['book_id'])) ?>" class="cart-item__cover">
                            <img src="<?= e(bookCoverUrlFromBook($item)) ?>" alt="<?= e($item['title']) ?>">
                        </a>
                        <div class="cart-item__body">
                            <a href="<?= e(url('book.php?id=' . (int)$item['book_id'])) ?>" class="cart-item__title"><?= e($item['title']) ?></a>
                            <p class="cart-item__meta"><?= e(t('by_author')) ?> <?= e($item['author']) ?></p>
                            <p class="cart-item__meta"><?= e(formatPrice((float)$item['price'])) ?> <?= e(t('unit_price')) ?> - <?= $stock ?> <?= e(t('in_stock')) ?></p>
                        </div>
                        <form method="post" action="<?= e(url('cart.php')) ?>" class="cart-item__qty js-auto-submit">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_quantity">
                            <input type="hidden" name="book_id" value="<?= (int)$item['book_id'] ?>">
                            <label class="form-label" for="qty-<?= (int)$item['book_id'] ?>"><?= e(t('qty')) ?></label>
                            <input id="qty-<?= (int)$item['book_id'] ?>" class="form-input form-input--qty" type="number" name="quantity" min="1" max="<?= $stock ?>" value="<?= $quantity ?>">
                        </form>
                        <div class="cart-item__total"><?= e(formatPrice($lineTotal)) ?></div>
                        <form method="post" action="<?= e(url('cart.php')) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="book_id" value="<?= (int)$item['book_id'] ?>">
                            <button type="submit" class="btn btn--sm btn--outline"><?= e(t('remove')) ?></button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>

            <aside class="cart-summary card">
                <h2 class="card__title"><?= e(t('summary')) ?></h2>
                <div class="summary-row">
                    <span><?= e(t('subtotal')) ?></span>
                    <strong><?= e(formatPrice($total)) ?></strong>
                </div>
                <div class="summary-row summary-row--total">
                    <span><?= e(t('total')) ?></span>
                    <strong><?= e(formatPrice($total)) ?></strong>
                </div>
                <p class="form-hint"><?= e(t('payment_method_cash_on_delivery')) ?></p>
                <a href="<?= e(url('checkout.php')) ?>" class="btn btn--primary btn--full"><?= e(t('checkout')) ?></a>
                <form method="post" action="<?= e(url('cart.php')) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="clear">
                    <button
                        type="submit"
                        class="btn btn--outline btn--full"
                        data-confirm="<?= e(t('confirm_clear_cart')) ?>"
                    >
                        <?= e(t('clear_cart')) ?>
                    </button>
                </form>
            </aside>
        </section>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
