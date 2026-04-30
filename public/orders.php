<?php
/**
 * public/orders.php
 * User order history.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('login.php');

$pageTitle = t('my_orders');
$pdo = db();

$stmt = $pdo->prepare(
    'SELECT o.* FROM orders o WHERE o.buyer_id = ? ORDER BY o.created_at DESC'
);
$stmt->execute([currentUserId()]);
$orders = $stmt->fetchAll();

$orderItems = [];
if (!empty($orders)) {
    $ids = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $itemStmt = $pdo->prepare(
        "SELECT oi.*, b.title, b.author
         FROM order_items oi
         LEFT JOIN books b ON b.id = oi.book_id
         WHERE oi.order_id IN ($placeholders)
         ORDER BY oi.id ASC"
    );
    $itemStmt->execute($ids);

    foreach ($itemStmt->fetchAll() as $item) {
        $orderItems[(int)$item['order_id']][] = $item;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><?= e(t('my_orders')) ?></h1>
        <p class="page-sub"><?= e(t('your_order_history')) ?></p>
    </div>

    <?php if (empty($orders)): ?>
    <div class="empty-state">
        <span class="empty-state__icon"><?= e(t('orders')) ?></span>
        <p><?= e(t('no_orders_placed')) ?></p>
        <a href="<?= e(url('books.php')) ?>" class="btn btn--primary"><?= e(t('browse_books')) ?></a>
    </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $ord): ?>
                <?php
                $orderTotal = (float)($ord['total_amount'] ?? $ord['total_price'] ?? 0);
                $paymentMethod = $ord['payment_method'] ?? 'cash_on_delivery';
                ?>
                <section class="card order-card">
                    <div class="order-card__head">
                        <div>
                            <h2 class="card__title"><?= e(t('order_number')) ?><?= (int)$ord['id'] ?></h2>
                            <p class="text-muted"><?= date('d M Y H:i', strtotime($ord['created_at'])) ?></p>
                        </div>
                        <span class="status status--<?= e($ord['status']) ?>"><?= e(t($ord['status'])) ?></span>
                    </div>

                    <div class="order-meta-grid">
                        <div>
                            <span class="text-muted"><?= e(t('total')) ?></span>
                            <strong><?= e(formatPrice($orderTotal)) ?></strong>
                        </div>
                        <div>
                            <span class="text-muted"><?= e(t('payment')) ?></span>
                            <strong><?= e($paymentMethod === 'cash_on_delivery' ? t('cash_on_delivery') : ucfirst($paymentMethod)) ?></strong>
                        </div>
                    </div>

                    <div class="books-table-wrap">
                        <table class="books-table">
                            <thead>
                                <tr><th><?= e(t('item')) ?></th><th><?= e(t('quantity')) ?></th><th><?= e(t('unit_price')) ?></th><th><?= e(t('total')) ?></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems[(int)$ord['id']] ?? [] as $item): ?>
                                <tr>
                                    <td>
                                        <?= e($item['title'] ?? t('deleted_book')) ?>
                                        <?php if (!empty($item['author'])): ?>
                                            <small><?= e($item['author']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int)$item['quantity'] ?></td>
                                    <td><?= e(formatPrice((float)$item['unit_price'])) ?></td>
                                    <td><?= e(formatPrice((int)$item['quantity'] * (float)$item['unit_price'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
