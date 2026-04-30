<?php
/**
 * public/admin/users.php
 * Admin user management — view, block/unblock.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin('login.php');

$pageTitle = 'Manage Users';
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
            flashSet('success', 'User blocked successfully.');
        } elseif ($action === 'unblock') {
            $pdo->prepare('UPDATE users SET is_blocked = 0 WHERE id = ?')->execute([$targetId]);
            flashSet('success', 'User unblocked.');
        } elseif ($action === 'delete') {
            // Safety: don't delete admin accounts
            $check = $pdo->prepare('SELECT role FROM users WHERE id = ?');
            $check->execute([$targetId]);
            $row = $check->fetch();
            if ($row && $row['role'] !== 'admin') {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);
                flashSet('success', 'User deleted.');
            } else {
                flashSet('error', 'Cannot delete an admin account.');
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
        <h1>Manage Users</h1>
        <p class="page-sub"><?= count($users) ?> user(s) found.</p>
    </div>

    <!-- Search -->
    <form method="get" class="search-bar-form">
        <input type="search" name="q" class="form-input" value="<?= e($search) ?>"
               placeholder="Search by name or email…">
        <button type="submit" class="btn btn--primary">Search</button>
        <?php if ($search): ?>
        <a href="<?= e(url('admin/users.php')) ?>" class="btn btn--outline">Clear</a>
        <?php endif; ?>
    </form>

    <section class="card">
        <table class="books-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Books Listed</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
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
                            <span class="text-muted">(you)</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge-role badge-role--<?= e($u['role']) ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                    <td><?= (int)$bc ?></td>
                    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?= $u['is_blocked']
                            ? '<span class="status status--blocked">Blocked</span>'
                            : '<span class="status status--active">Active</span>' ?>
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
                                    data-confirm="Are you sure you want to unblock this user?"
                                >
                                    Unblock
                                </button>
                            <?php else: ?>
                                <button
                                    name="action"
                                    value="block"
                                    class="btn btn--sm btn--warning"
                                    data-confirm="Are you sure you want to block this user?"
                                >
                                    Block
                                </button>
                            <?php endif; ?>
                            <?php if ($u['role'] !== 'admin'): ?>
                            <button
                                name="action"
                                value="delete"
                                class="btn btn--sm btn--danger"
                                data-confirm="Are you sure you want to delete this user? This action cannot be undone."
                            >
                                Delete
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
