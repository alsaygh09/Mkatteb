<?php
/**
 * public/admin/dashboard.php
 * Admin overview dashboard.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin('login.php');

$pageTitle = 'Admin Dashboard';
$pdo = db();

// Quick stats
$totalUsers  = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn();
$totalBooks  = $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$totalOrders = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$openReports = $pdo->query('SELECT COUNT(*) FROM reports WHERE status = "open"')->fetchColumn();

// Recent users
$recentUsers = $pdo->query(
    'SELECT * FROM users ORDER BY created_at DESC LIMIT 5'
)->fetchAll();

// Recent orders
$recentOrders = $pdo->query(
    'SELECT o.*, u.name AS buyer_name FROM orders o
     JOIN users u ON o.buyer_id = u.id
     ORDER BY o.created_at DESC LIMIT 5'
)->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container admin-page">
    <div class="page-header">
        <h1>Admin Dashboard</h1>
        <p class="page-sub">Welcome back, <?= e($_SESSION['user_name']) ?>.</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <span class="stat-card__icon">👥</span>
            <div>
                <div class="stat-card__value"><?= (int)$totalUsers ?></div>
                <div class="stat-card__label">Registered Users</div>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-card__icon">📚</span>
            <div>
                <div class="stat-card__value"><?= (int)$totalBooks ?></div>
                <div class="stat-card__label">Total Books</div>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-card__icon">🛒</span>
            <div>
                <div class="stat-card__value"><?= (int)$totalOrders ?></div>
                <div class="stat-card__label">Orders Placed</div>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-card__icon">🚩</span>
            <div>
                <div class="stat-card__value"><?= (int)$openReports ?></div>
                <div class="stat-card__label">Open Reports</div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="admin-shortcuts">
        <a href="<?= e(url('admin/books.php')) ?>" class="shortcut-card">📖 Manage Books</a>
        <a href="<?= e(url('admin/users.php')) ?>" class="shortcut-card">👥 Manage Users</a>
        <a href="<?= e(url('admin/orders.php')) ?>" class="shortcut-card">📦 View Orders</a>
        <a href="<?= e(url('admin/categories.php')) ?>" class="shortcut-card">🏷️ Categories</a>
        <a href="<?= e(url('add-book.php')) ?>" class="shortcut-card">➕ Add Official Book</a>
    </div>

    <div class="admin-tables">

        <!-- Recent Users -->
        <section class="card">
            <div class="card__head">
                <h2 class="card__title">Recent Registrations</h2>
                <a href="<?= e(url('admin/users.php')) ?>" class="btn btn--sm btn--outline">All Users</a>
            </div>
            <table class="books-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td><?= e($u['name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e(ucfirst($u['role'])) ?></td>
                        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td><?= $u['is_blocked']
                            ? '<span class="status status--blocked">Blocked</span>'
                            : '<span class="status status--active">Active</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Recent Orders -->
        <section class="card">
            <div class="card__head">
                <h2 class="card__title">Recent Orders</h2>
                <a href="<?= e(url('admin/orders.php')) ?>" class="btn btn--sm btn--outline">All Orders</a>
            </div>
            <?php if (empty($recentOrders)): ?>
            <p class="text-muted p-4">No orders yet.</p>
            <?php else: ?>
            <table class="books-table">
                <thead><tr><th>Order #</th><th>Buyer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($recentOrders as $ord): ?>
                    <tr>
                        <td>#<?= (int)$ord['id'] ?></td>
                        <td><?= e($ord['buyer_name']) ?></td>
                        <td><?= formatPrice((float)$ord['total_price']) ?></td>
                        <td><span class="status status--<?= e($ord['status']) ?>"><?= e(ucfirst($ord['status'])) ?></span></td>
                        <td><?= date('d M Y', strtotime($ord['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>

    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
