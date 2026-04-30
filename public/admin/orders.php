<?php
/**
 * public/admin/orders.php
 * Admin: view all orders and update status.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin('login.php');

$pageTitle = 'All Orders';
$pdo = db();

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status  = $_POST['status'] ?? '';
    $allowed = ['pending','processing','completed','cancelled'];

    if ($orderId && in_array($status, $allowed)) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $orderId]);
        flashSet('success', 'Order #' . $orderId . ' status updated to ' . ucfirst($status) . '.');
    }
    redirect('admin/orders.php');
}

$orders = $pdo->query(
    'SELECT o.*, u.name AS buyer_name, u.email AS buyer_email
     FROM orders o JOIN users u ON o.buyer_id = u.id
     ORDER BY o.created_at DESC'
)->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container admin-page">
    <div class="page-header">
        <h1>All Orders</h1>
        <p class="page-sub"><?= count($orders) ?> total order(s).</p>
    </div>

    <?php if (empty($orders)): ?>
    <div class="empty-state">
        <span class="empty-state__icon">📦</span>
        <p>No orders placed yet.</p>
    </div>
    <?php else: ?>
    <section class="card">
        <table class="books-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Buyer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $ord): ?>
                <tr>
                    <td><strong>#<?= (int)$ord['id'] ?></strong></td>
                    <td><?= e($ord['buyer_name']) ?><br><small><?= e($ord['buyer_email']) ?></small></td>
                    <td><?= formatPrice((float)$ord['total_price']) ?></td>
                    <td><span class="status status--<?= e($ord['status']) ?>"><?= e(ucfirst($ord['status'])) ?></span></td>
                    <td><?= date('d M Y H:i', strtotime($ord['created_at'])) ?></td>
                    <td>
                        <form
                            method="post"
                            class="inline-form"
                            data-confirm="Are you sure you want to cancel this order?"
                            data-confirm-field="status"
                            data-confirm-value="cancelled"
                        >
                            <?= csrfField() ?>
                            <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">
                            <select name="status" class="form-input form-select form-select--sm">
                                <?php foreach (['pending','processing','completed','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $ord['status'] === $s ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn--sm btn--primary">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
