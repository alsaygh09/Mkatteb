<?php
/**
 * public/admin/books.php
 * Admin view and management of all books on the platform.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin('login.php');

$pageTitle = t('manage_books');
$pdo = db();

// Handle toggle active / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $bookId = (int)($_POST['book_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($bookId) {
        if ($action === 'toggle') {
            $pdo->prepare('UPDATE books SET is_active = NOT is_active WHERE id = ?')->execute([$bookId]);
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM books WHERE id = ?')->execute([$bookId]);
            flashSet('success', t('flash_book_deleted'));
        }
    }
    redirect('admin/books.php');
}

// Filters
$search   = trim($_GET['q']    ?? '');
$typeF    = trim($_GET['type'] ?? '');
$params   = [];
$where    = ['1=1'];

if ($search) {
    $where[]  = '(b.title LIKE ? OR b.author LIKE ?)';
    $t = '%' . $search . '%';
    $params[] = $t;
    $params[] = $t;
}
if (in_array($typeF, ['official', 'used'])) {
    $where[]  = 'b.book_type = ?';
    $params[] = $typeF;
}

$whereStr = implode(' AND ', $where);
$stmt = $pdo->prepare(
    "SELECT b.*, u.name AS seller_name, c.name AS category_name
     FROM books b
     LEFT JOIN users u ON b.seller_id = u.id
     LEFT JOIN categories c ON b.category_id = c.id
     WHERE {$whereStr}
     ORDER BY b.created_at DESC"
);
$stmt->execute($params);
$books = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container admin-page">
    <div class="page-header">
        <h1><?= e(t('manage_books')) ?></h1>
        <div class="page-header__actions">
            <a href="<?= e(url('add-book.php')) ?>" class="btn btn--primary">+ <?= e(t('add_official_book')) ?></a>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" class="filter-bar">
        <input type="search" name="q" class="form-input" value="<?= e($search) ?>"
               placeholder="<?= e(t('search_title_author')) ?>">
        <select name="type" class="form-input form-select">
            <option value=""><?= e(t('all_types_admin')) ?></option>
            <option value="official" <?= $typeF === 'official' ? 'selected' : '' ?>><?= e(bookTypeLabel('official')) ?></option>
            <option value="used"     <?= $typeF === 'used'     ? 'selected' : '' ?>><?= e(bookTypeLabel('used')) ?></option>
        </select>
        <button type="submit" class="btn btn--primary"><?= e(t('filter')) ?></button>
        <a href="<?= e(url('admin/books.php')) ?>" class="btn btn--outline"><?= e(t('clear')) ?></a>
    </form>

    <p class="result-count"><?= count($books) ?> <?= e(t('book_s_found')) ?></p>

    <section class="card">
        <table class="books-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= e(t('title')) ?></th>
                    <th><?= e(t('author')) ?></th>
                    <th><?= e(t('type')) ?></th>
                    <th><?= e(t('language')) ?></th>
                    <th><?= e(t('price')) ?></th>
                    <th><?= e(t('stock')) ?></th>
                    <th><?= e(t('seller')) ?></th>
                    <th><?= e(t('status')) ?></th>
                    <th><?= e(t('actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                <tr class="<?= !$book['is_active'] ? 'row--inactive' : '' ?>">
                    <td><?= (int)$book['id'] ?></td>
                    <td>
                        <a href="<?= e(url('book.php?id=' . (int)$book['id'])) ?>"><?= e($book['title']) ?></a>
                    </td>
                    <td><?= e($book['author']) ?></td>
                    <td>
                        <span class="book-badge book-badge--<?= e($book['book_type']) ?>">
                            <?= e(bookTypeLabel($book['book_type'])) ?>
                        </span>
                    </td>
                    <td><?= e(bookLanguageLabel($book['book_language'] ?? 'english')) ?></td>
                    <td><?= formatPrice((float)$book['price']) ?></td>
                    <td><?= (int)$book['stock'] ?></td>
                    <td><?= e($book['seller_name'] ?? '—') ?></td>
                    <td>
                        <?php if ($book['is_active']): ?>
                            <span class="status status--active"><?= e(statusLabel('active')) ?></span>
                        <?php else: ?>
                            <span class="status status--blocked"><?= e(statusLabel('hidden')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <form method="post" class="inline-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
                            <a href="<?= e(url('add-book.php?edit=' . (int)$book['id'])) ?>" class="btn btn--sm btn--outline"><?= e(t('edit')) ?></a>
                            <button
                                name="action"
                                value="toggle"
                                class="btn btn--sm btn--warning"
                                <?php if ($book['is_active']): ?>
                                    data-confirm="<?= e(t('confirm_hide_book')) ?>"
                                <?php endif; ?>
                            >
                                <?= e($book['is_active'] ? t('hide') : t('show')) ?>
                            </button>
                            <button
                                name="action"
                                value="delete"
                                class="btn btn--sm btn--danger"
                                data-confirm="<?= e(t('confirm_delete_book')) ?>"
                            >
                                <?= e(t('delete')) ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
