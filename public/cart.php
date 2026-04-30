<?php
/**
 * public/cart.php
 * User-specific shopping cart.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('login.php');

$pageTitle = 'My Cart';
$userId = (int)currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';
    $bookId = (int)($_POST['book_id'] ?? 0);

    if ($action === 'add' && $bookId > 0) {
        $result = addToCart($userId, $bookId, 1);
        flashSet(isset($result['error']) ? 'error' : 'success', $result['error'] ?? 'Book added to cart.');
    } elseif ($action === 'update_quantity' && $bookId > 0) {
        $quantity = (int)($_POST['quantity'] ?? 1);
        $result = updateCartQuantity($userId, $bookId, $quantity);
        if (isset($result['error'])) {
            flashSet('error', $result['error']);
        }
    } elseif ($action === 'remove' && $bookId > 0) {
        removeCartItem($userId, $bookId);
        flashSet('success', 'Item removed from cart.');
    } elseif ($action === 'clear') {
        clearCart($userId);
        flashSet('success', 'Cart cleared.');
    }

    redirect('cart.php');
}

$cartItems = getCartItems($userId);
$total = 0.0;

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Shopping Cart</h1>
    </div>

    <?php if (empty($cartItems)): ?>
        <div class="empty-state">
            <span class="empty-state__icon">Cart</span>
            <p>Your cart is empty.</p>
            <a href="<?= e(url('books.php')) ?>" class="btn btn--primary">Continue Browsing</a>
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
                            <p class="cart-item__meta">by <?= e($item['author']) ?></p>
                            <p class="cart-item__meta"><?= e(formatPrice((float)$item['price'])) ?> each - <?= $stock ?> in stock</p>
                        </div>
                        <form method="post" action="<?= e(url('cart.php')) ?>" class="cart-item__qty js-auto-submit">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_quantity">
                            <input type="hidden" name="book_id" value="<?= (int)$item['book_id'] ?>">
                            <label class="form-label" for="qty-<?= (int)$item['book_id'] ?>">Qty</label>
                            <input id="qty-<?= (int)$item['book_id'] ?>" class="form-input form-input--qty" type="number" name="quantity" min="1" max="<?= $stock ?>" value="<?= $quantity ?>">
                        </form>
                        <div class="cart-item__total"><?= e(formatPrice($lineTotal)) ?></div>
                        <form method="post" action="<?= e(url('cart.php')) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="book_id" value="<?= (int)$item['book_id'] ?>">
                            <button type="submit" class="btn btn--sm btn--outline">Remove</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>

            <aside class="cart-summary card">
                <h2 class="card__title">Summary</h2>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong><?= e(formatPrice($total)) ?></strong>
                </div>
                <div class="summary-row summary-row--total">
                    <span>Total</span>
                    <strong><?= e(formatPrice($total)) ?></strong>
                </div>
                <p class="form-hint">Payment method: Cash on Delivery.</p>
                <a href="<?= e(url('checkout.php')) ?>" class="btn btn--primary btn--full">Checkout</a>
                <form method="post" action="<?= e(url('cart.php')) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="clear">
                    <button
                        type="submit"
                        class="btn btn--outline btn--full"
                        data-confirm="Are you sure you want to clear your cart?"
                    >
                        Clear Cart
                    </button>
                </form>
            </aside>
        </section>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
