<?php
/**
 * public/account.php
 * User profile and account management.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('login.php');

$pageTitle = 'My Account';
$user      = currentUser();
$userBooks = getUserBooks($user['id']);
$errors    = [];
$success   = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    verifyCsrf();

    $name    = trim($_POST['name']    ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $address = trim($_POST['address'] ?? '');

    if (strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters.';
    } else {
        $stmt = db()->prepare(
            'UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?'
        );
        $stmt->execute([$name, $phone, $address, $user['id']]);
        $_SESSION['user_name'] = $name;
        flashSet('success', 'Profile updated successfully.');
        redirect('account.php');
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    verifyCsrf();

    $current = trim($_POST['current_password'] ?? '');
    $new     = trim($_POST['new_password']     ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if (!password_verify($current, $user['password'])) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'New passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $user['id']]);
        flashSet('success', 'Password changed successfully.');
        redirect('account.php');
    }
}

// Re-fetch fresh user data
$user = currentUser();

include __DIR__ . '/../includes/header.php';
?>

<div class="container account-page">
    <div class="page-header">
        <h1>My Account</h1>
        <p class="page-sub">Hello, <?= e($user['name']) ?> &mdash; manage your profile and listings below.</p>
    </div>

    <?php if ($errors): ?>
    <div class="flash flash--error">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="account-grid">

        <!-- Profile Info -->
        <section class="card">
            <h2 class="card__title">Profile Information</h2>
            <form method="post" action="<?= e(url('account.php')) ?>" class="auth-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input"
                           value="<?= e($user['name']) ?>" required minlength="2">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-input" value="<?= e($user['email']) ?>" disabled>
                    <span class="form-hint">Email cannot be changed.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-input"
                           value="<?= e($user['phone'] ?? '') ?>" placeholder="+973 3xxx xxxx">
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-input" rows="2"
                              placeholder="Your delivery address"><?= e($user['address'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn--primary">Save Changes</button>
            </form>
        </section>

        <!-- Change Password -->
        <section class="card">
            <h2 class="card__title">Change Password</h2>
            <form method="post" action="<?= e(url('account.php')) ?>" class="auth-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" required autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-input" required minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-input" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn--primary">Change Password</button>
            </form>
        </section>

        <!-- Account Details -->
        <section class="card account-meta">
            <h2 class="card__title">Account Details</h2>
            <dl class="detail-list">
                <dt>Role</dt>
                <dd><span class="badge-role badge-role--<?= e($user['role']) ?>"><?= e(ucfirst($user['role'])) ?></span></dd>
                <dt>Member Since</dt>
                <dd><?= date('d M Y', strtotime($user['created_at'])) ?></dd>
                <dt>Status</dt>
                <dd><?= $user['is_blocked'] ? '<span class="status status--blocked">Blocked</span>' : '<span class="status status--active">Active</span>' ?></dd>
            </dl>
        </section>

    </div><!-- /account-grid -->

    <!-- My Listings -->
    <section class="my-listings">
        <div class="section-header">
            <h2>My Book Listings</h2>
            <a href="<?= e(url('add-book.php')) ?>" class="btn btn--primary btn--sm">+ Add New Book</a>
        </div>

        <?php if (empty($userBooks)): ?>
        <div class="empty-state">
            <span class="empty-state__icon">📦</span>
            <p>You haven't listed any books yet.</p>
            <a href="<?= e(url('add-book.php')) ?>" class="btn btn--primary">List Your First Book</a>
        </div>
        <?php else: ?>
        <div class="books-table-wrap">
            <table class="books-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Condition</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userBooks as $book): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('book.php?id=' . (int)$book['id'])) ?>"><?= e($book['title']) ?></a>
                            <small class="text-muted">by <?= e($book['author']) ?></small>
                        </td>
                        <td><?= formatPrice((float)$book['price']) ?></td>
                        <td><?= conditionLabel($book['condition_type']) ?></td>
                        <td><?= (int)$book['stock'] ?></td>
                        <td>
                            <?php if ($book['is_active']): ?>
                                <span class="status status--active">Active</span>
                            <?php else: ?>
                                <span class="status status--blocked">Hidden</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="<?= e(url('add-book.php?edit=' . (int)$book['id'])) ?>" class="btn btn--sm btn--outline">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

</div><!-- /container -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
