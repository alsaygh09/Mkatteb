<?php
/**
 * public/admin/users.php
 * Admin user management — view, block/unblock.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin('login.php');

$pageTitle = t('manage_users');
$pdo = db();

// Handle block/unblock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $targetId = (int)($_POST['user_id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    // Prevent admin from blocking themselves
    if ($targetId && $targetId !== currentUserId()) {
        if ($action === 'block') {
            $pdo->prepare('UPDATE users SET is_blocked = 1 WHERE id = ?')->execute([$targetId]);
            flashSet('success', t('flash_user_blocked'));
        } elseif ($action === 'unblock') {
            $pdo->prepare('UPDATE users SET is_blocked = 0 WHERE id = ?')->execute([$targetId]);
            flashSet('success', t('flash_user_unblocked'));
        } elseif ($action === 'delete') {
            // Safety: don't delete admin accounts
            $check = $pdo->prepare('SELECT role FROM users WHERE id = ?');
            $check->execute([$targetId]);
            $row = $check->fetch();
            if ($row && $row['role'] !== 'admin') {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);
                flashSet('success', t('flash_user_deleted'));
            } else {
                flashSet('error', t('error_delete_admin'));
            }
        }
    }
    redirect('admin/users.php');
}

// Search
$search = trim($_GET['q'] ?? '');
$params = [];
$where  = '';
if ($search) {
    $where    = 'WHERE name LIKE ? OR email LIKE ?';
    $term     = '%' . $search . '%';
    $params   = [$term, $term];
}

$stmt  = $pdo->prepare("SELECT * FROM users {$where} ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container admin-page">
    <div class="page-header">
        <h1><?= e(t('manage_users')) ?></h1>
        <p class="page-sub"><?= count($users) ?> <?= e(t('user_s_found')) ?></p>
    </div>

    <!-- Search -->
    <form method="get" class="search-bar-form">
        <input type="search" name="q" class="form-input" value="<?= e($search) ?>"
               placeholder="<?= e(t('search_name_email')) ?>">
        <button type="submit" class="btn btn--primary"><?= e(t('search')) ?></button>
        <?php if ($search): ?>
        <a href="<?= e(url('admin/users.php')) ?>" class="btn btn--outline"><?= e(t('clear')) ?></a>
        <?php endif; ?>
    </form>

    <section class="card">
        <table class="books-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= e(t('name')) ?></th>
                    <th><?= e(t('email')) ?></th>
                    <th><?= e(t('role')) ?></th>
                    <th><?= e(t('books_listed')) ?></th>
                    <th><?= e(t('joined')) ?></th>
                    <th><?= e(t('status')) ?></th>
                    <th><?= e(t('actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u):
                    $bookCount = $pdo->prepare('SELECT COUNT(*) FROM books WHERE seller_id = ?');
                    $bookCount->execute([$u['id']]);
                    $bc = $bookCount->fetchColumn();
                ?>
                <tr class="<?= $u['is_blocked'] ? 'row--blocked' : '' ?>">
                    <td><?= (int)$u['id'] ?></td>
                    <td>
                        <?= e($u['name']) ?>
                        <?php if ($u['id'] === currentUserId()): ?>
                            <span class="text-muted">(<?= e(t('you')) ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge-role badge-role--<?= e($u['role']) ?>"><?= e(roleLabel($u['role'])) ?></span></td>
                    <td><?= (int)$bc ?></td>
                    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['is_blocked']): ?>
                            <span class="status status--blocked"><?= e(statusLabel('blocked')) ?></span>
                        <?php else: ?>
                            <span class="status status--active"><?= e(statusLabel('active')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <?php if ($u['id'] !== currentUserId()): ?>
                        <form method="post" class="inline-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <?php if ($u['is_blocked']): ?>
                                <button
                                    name="action"
                                    value="unblock"
                                    class="btn btn--sm btn--primary"
                                    data-confirm="<?= e(t('confirm_unblock_user')) ?>"
                                >
                                    <?= e(t('unblock')) ?>
                                </button>
                            <?php else: ?>
                                <button
                                    name="action"
                                    value="block"
                                    class="btn btn--sm btn--warning"
                                    data-confirm="<?= e(t('confirm_block_user')) ?>"
                                >
                                    <?= e(t('block')) ?>
                                </button>
                            <?php endif; ?>
                            <?php if ($u['role'] !== 'admin'): ?>
                            <button
                                name="action"
                                value="delete"
                                class="btn btn--sm btn--danger"
                                data-confirm="<?= e(t('confirm_delete_user')) ?>"
                            >
                                <?= e(t('delete')) ?>
                            </button>
                            <?php endif; ?>
                        </form>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
