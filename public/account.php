<?php
/**
 * public/account.php
 * User profile and account management.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('login.php');

$pageTitle = t('my_account');
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
        $errors[] = t('error_name_min');
    } else {
        $stmt = db()->prepare(
            'UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?'
        );
        $stmt->execute([$name, $phone, $address, $user['id']]);
        $_SESSION['user_name'] = $name;
        flashSet('success', t('flash_profile_updated'));
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
        $errors[] = t('error_current_password');
    } elseif (strlen($new) < 6) {
        $errors[] = t('error_new_password_min');
    } elseif ($new !== $confirm) {
        $errors[] = t('error_new_passwords_match');
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $user['id']]);
        flashSet('success', t('flash_password_changed'));
        redirect('account.php');
    }
}

// Re-fetch fresh user data
$user = currentUser();

include __DIR__ . '/../includes/header.php';
?>

<div class="container account-page">
    <div class="page-header">
        <h1><?= e(t('my_account')) ?></h1>
        <p class="page-sub"><?= e(sprintf(t('hello_manage_profile'), $user['name'])) ?></p>
    </div>

    <?php if ($errors): ?>
    <div class="flash flash--error">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="account-grid">

        <!-- Profile Info -->
        <section class="card">
            <h2 class="card__title"><?= e(t('profile_information')) ?></h2>
            <form method="post" action="<?= e(url('account.php')) ?>" class="auth-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label class="form-label"><?= e(t('full_name')) ?></label>
                    <input type="text" name="name" class="form-input"
                           value="<?= e($user['name']) ?>" required minlength="2">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('email_address')) ?></label>
                    <input type="email" class="form-input" value="<?= e($user['email']) ?>" disabled>
                    <span class="form-hint"><?= e(t('email_cannot_change')) ?></span>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('phone')) ?></label>
                    <input type="tel" name="phone" class="form-input"
                           value="<?= e($user['phone'] ?? '') ?>" placeholder="+973 3xxx xxxx">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('address')) ?></label>
                    <textarea name="address" class="form-input" rows="2"
                              placeholder="<?= e(t('your_delivery_address')) ?>"><?= e($user['address'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn--primary"><?= e(t('save_changes')) ?></button>
            </form>
        </section>

        <!-- Change Password -->
        <section class="card">
            <h2 class="card__title"><?= e(t('change_password')) ?></h2>
            <form method="post" action="<?= e(url('account.php')) ?>" class="auth-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label class="form-label"><?= e(t('current_password')) ?></label>
                    <input type="password" name="current_password" class="form-input" required autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('new_password')) ?></label>
                    <input type="password" name="new_password" class="form-input" required minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('confirm_new_password')) ?></label>
                    <input type="password" name="confirm_password" class="form-input" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn--primary"><?= e(t('change_password')) ?></button>
            </form>
        </section>

        <!-- Account Details -->
        <section class="card account-meta">
            <h2 class="card__title"><?= e(t('account_details')) ?></h2>
            <dl class="detail-list">
                <dt><?= e(t('role')) ?></dt>
                <dd><span class="badge-role badge-role--<?= e($user['role']) ?>"><?= e(t($user['role'] === 'admin' ? 'admin_role' : 'user_role')) ?></span></dd>
                <dt><?= e(t('member_since')) ?></dt>
                <dd><?= date('d M Y', strtotime($user['created_at'])) ?></dd>
                <dt><?= e(t('status')) ?></dt>
                <dd><?= $user['is_blocked'] ? '<span class="status status--blocked">' . e(t('blocked')) . '</span>' : '<span class="status status--active">' . e(t('active')) . '</span>' ?></dd>
            </dl>
        </section>

    </div><!-- /account-grid -->

    <!-- My Listings -->
    <section class="my-listings">
        <div class="section-header">
            <h2><?= e(t('my_book_listings')) ?></h2>
            <a href="<?= e(url('add-book.php')) ?>" class="btn btn--primary btn--sm">+ <?= e(t('add_new_book')) ?></a>
        </div>

        <?php if (empty($userBooks)): ?>
        <div class="empty-state">
            <span class="empty-state__icon">📦</span>
            <p><?= e(t('no_listed_books')) ?></p>
            <a href="<?= e(url('add-book.php')) ?>" class="btn btn--primary"><?= e(t('list_first_book')) ?></a>
        </div>
        <?php else: ?>
        <div class="books-table-wrap">
            <table class="books-table">
                <thead>
                    <tr>
                        <th><?= e(t('title')) ?></th>
                        <th><?= e(t('price')) ?></th>
                        <th><?= e(t('condition')) ?></th>
                        <th><?= e(t('stock')) ?></th>
                        <th><?= e(t('status')) ?></th>
                        <th><?= e(t('actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userBooks as $book): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('book.php?id=' . (int)$book['id'])) ?>"><?= e($book['title']) ?></a>
                            <small class="text-muted"><?= e(t('by_author')) ?> <?= e($book['author']) ?></small>
                        </td>
                        <td><?= formatPrice((float)$book['price']) ?></td>
                        <td><?= conditionLabel($book['condition_type']) ?></td>
                        <td><?= (int)$book['stock'] ?></td>
                        <td>
                            <?php if ($book['is_active']): ?>
                                <span class="status status--active"><?= e(t('active')) ?></span>
                            <?php else: ?>
                                <span class="status status--blocked"><?= e(t('hidden')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="<?= e(url('add-book.php?edit=' . (int)$book['id'])) ?>" class="btn btn--sm btn--outline"><?= e(t('edit')) ?></a>
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
