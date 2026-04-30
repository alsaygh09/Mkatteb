<?php
/**
 * public/admin/dashboard.php
 * Admin overview dashboard.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin('login.php');

$pageTitle = t('admin_dashboard');
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
        <h1><?= e(t('admin_dashboard')) ?></h1>
        <p class="page-sub"><?= e(sprintf(t('welcome_back_name'), $_SESSION['user_name'])) ?></p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <span class="stat-card__icon">👥</span>
            <div>
                <div class="stat-card__value"><?= (int)$totalUsers ?></div>
                <div class="stat-card__label"><?= e(t('registered_users')) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-card__icon">📚</span>
            <div>
                <div class="stat-card__value"><?= (int)$totalBooks ?></div>
                <div class="stat-card__label"><?= e(t('total_books')) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-card__icon">🛒</span>
            <div>
                <div class="stat-card__value"><?= (int)$totalOrders ?></div>
                <div class="stat-card__label"><?= e(t('orders_placed')) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-card__icon">🚩</span>
            <div>
                <div class="stat-card__value"><?= (int)$openReports ?></div>
                <div class="stat-card__label"><?= e(t('open_reports')) ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="admin-shortcuts">
        <a href="<?= e(url('admin/books.php')) ?>" class="shortcut-card">📖 <?= e(t('manage_books')) ?></a>
        <a href="<?= e(url('admin/users.php')) ?>" class="shortcut-card">👥 <?= e(t('manage_users')) ?></a>
        <a href="<?= e(url('admin/orders.php')) ?>" class="shortcut-card">📦 <?= e(t('view_orders')) ?></a>
        <a href="<?= e(url('admin/categories.php')) ?>" class="shortcut-card">🏷️ <?= e(t('categories')) ?></a>
        <a href="<?= e(url('add-book.php')) ?>" class="shortcut-card">➕ <?= e(t('add_official_book')) ?></a>
    </div>

    <div class="admin-tables">

        <!-- Recent Users -->
        <section class="card">
            <div class="card__head">
                <h2 class="card__title"><?= e(t('recent_registrations')) ?></h2>
                <a href="<?= e(url('admin/users.php')) ?>" class="btn btn--sm btn--outline"><?= e(t('all_users')) ?></a>
            </div>
            <table class="books-table">
                <thead><tr><th><?= e(t('name')) ?></th><th><?= e(t('email')) ?></th><th><?= e(t('role')) ?></th><th><?= e(t('joined')) ?></th><th><?= e(t('status')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td><?= e($u['name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e(roleLabel($u['role'])) ?></td>
                        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <?php if ($u['is_blocked']): ?>
                                <span class="status status--blocked"><?= e(statusLabel('blocked')) ?></span>
                            <?php else: ?>
                                <span class="status status--active"><?= e(statusLabel('active')) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Recent Orders -->
        <section class="card">
            <div class="card__head">
                <h2 class="card__title"><?= e(t('recent_orders')) ?></h2>
                <a href="<?= e(url('admin/orders.php')) ?>" class="btn btn--sm btn--outline"><?= e(t('all_orders')) ?></a>
            </div>
            <?php if (empty($recentOrders)): ?>
            <p class="text-muted p-4"><?= e(t('no_orders_yet')) ?></p>
            <?php else: ?>
            <table class="books-table">
                <thead><tr><th><?= e(t('order_number')) ?></th><th><?= e(t('buyer')) ?></th><th><?= e(t('total')) ?></th><th><?= e(t('status')) ?></th><th><?= e(t('date')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($recentOrders as $ord): ?>
                    <tr>
                        <td>#<?= (int)$ord['id'] ?></td>
                        <td><?= e($ord['buyer_name']) ?></td>
                        <td><?= formatPrice((float)$ord['total_price']) ?></td>
                        <td><span class="status status--<?= e($ord['status']) ?>"><?= e(statusLabel($ord['status'])) ?></span></td>
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
