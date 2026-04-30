<?php
/**
 * public/checkout.php
 * Cash on Delivery checkout.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('login.php');

$pageTitle = 'Checkout';
$userId = (int)currentUserId();
$user = currentUser();
$errors = [];
$formData = [
    'customer_name' => $user['name'] ?? '',
    'phone' => $user['phone'] ?? '',
    'delivery_address' => $user['address'] ?? '',
    'notes' => '',
];

$cartItems = getCartItems($userId);
$cartTotal = 0.0;
foreach ($cartItems as $item) {
    $cartTotal += min((int)$item['quantity'], (int)$item['stock']) * (float)$item['price'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $formData = [
        'customer_name' => trim($_POST['customer_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'delivery_address' => trim($_POST['delivery_address'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
    ];

    if ($formData['customer_name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if ($formData['phone'] === '') {
        $errors[] = 'Phone number is required.';
    }
    if ($formData['delivery_address'] === '') {
        $errors[] = 'Delivery address is required.';
    }
    if (empty($cartItems)) {
        $errors[] = 'Your cart is empty.';
    }

    if (empty($errors)) {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT ci.book_id, ci.quantity, b.title, b.price, b.stock, b.seller_id, b.is_active
                 FROM cart_items ci
                 JOIN books b ON b.id = ci.book_id
                 WHERE ci.user_id = ?
                 FOR UPDATE"
            );
            $stmt->execute([$userId]);
            $lockedItems = $stmt->fetchAll();

            if (empty($lockedItems)) {
                throw new RuntimeException('Your cart is empty.');
            }

            $total = 0.0;
            foreach ($lockedItems as $item) {
                $quantity = (int)$item['quantity'];
                $stock = (int)$item['stock'];

                if (empty($item['is_active'])) {
                    throw new RuntimeException($item['title'] . ' is no longer available.');
                }
                if ($quantity < 1) {
                    throw new RuntimeException('Invalid quantity for ' . $item['title'] . '.');
                }
                if ($quantity > $stock) {
                    throw new RuntimeException($item['title'] . ' only has ' . $stock . ' in stock.');
                }

                $total += $quantity * (float)$item['price'];
            }

            $orderStmt = $pdo->prepare(
                "INSERT INTO orders
                    (buyer_id, customer_name, phone, delivery_address, notes, payment_method, total_price, total_amount, status)
                 VALUES
                    (?, ?, ?, ?, ?, 'cash_on_delivery', ?, ?, 'pending')"
            );
            $orderStmt->execute([
                $userId,
                $formData['customer_name'],
                $formData['phone'],
                $formData['delivery_address'],
                $formData['notes'] !== '' ? $formData['notes'] : null,
                $total,
                $total,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, book_id, seller_id, quantity, unit_price)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stockStmt = $pdo->prepare('UPDATE books SET stock = stock - ? WHERE id = ? AND stock >= ?');

            foreach ($lockedItems as $item) {
                $quantity = (int)$item['quantity'];
                $itemStmt->execute([
                    $orderId,
                    (int)$item['book_id'],
                    (int)$item['seller_id'],
                    $quantity,
                    (float)$item['price'],
                ]);

                $stockStmt->execute([$quantity, (int)$item['book_id'], $quantity]);
                if ($stockStmt->rowCount() !== 1) {
                    throw new RuntimeException('Stock changed while placing your order. Please review your cart.');
                }
            }

            $clearStmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
            $clearStmt->execute([$userId]);

            $pdo->commit();
            flashSet('success', 'Order #' . $orderId . ' placed successfully. Payment method: Cash on Delivery.');
            redirect('orders.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $exception->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Checkout</h1>
        <p class="page-sub">Cash on Delivery is the only payment method for now.</p>
    </div>

    <?php if (empty($cartItems)): ?>
        <div class="empty-state">
            <span class="empty-state__icon">Cart</span>
            <p>Your cart is empty. Add a book before checkout.</p>
            <a href="<?= e(url('books.php')) ?>" class="btn btn--primary">Browse Books</a>
        </div>
    <?php else: ?>
        <?php if ($errors): ?>
            <div class="flash flash--error">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="checkout-layout">
            <form method="post" action="<?= e(url('checkout.php')) ?>" class="auth-form card">
                <?= csrfField() ?>

                <div class="form-group">
                    <label class="form-label" for="customer_name">Full Name</label>
                    <input id="customer_name" name="customer_name" class="form-input" type="text" value="<?= e($formData['customer_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input id="phone" name="phone" class="form-input" type="tel" value="<?= e($formData['phone']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="delivery_address">Delivery Address</label>
                    <textarea id="delivery_address" name="delivery_address" class="form-input" rows="3" required><?= e($formData['delivery_address']) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="form-input" rows="2"><?= e($formData['notes']) ?></textarea>
                    <span class="form-hint">Optional delivery notes.</span>
                </div>

                <div class="payment-method-box">
                    <span>Payment Method</span>
                    <strong>Cash on Delivery</strong>
                </div>

                <button type="submit" class="btn btn--primary btn--full">Place Order</button>
            </form>

            <aside class="cart-summary card">
                <h2 class="card__title">Order Summary</h2>
                <?php foreach ($cartItems as $item): ?>
                    <?php
                    $quantity = min((int)$item['quantity'], (int)$item['stock']);
                    $lineTotal = $quantity * (float)$item['price'];
                    ?>
                    <div class="summary-line-item">
                        <span><?= e($item['title']) ?> x <?= $quantity ?></span>
                        <strong><?= e(formatPrice($lineTotal)) ?></strong>
                    </div>
                <?php endforeach; ?>
                <div class="summary-row summary-row--total">
                    <span>Total</span>
                    <strong><?= e(formatPrice($cartTotal)) ?></strong>
                </div>
            </aside>
        </section>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
