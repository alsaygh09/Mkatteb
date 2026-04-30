<?php
/**
 * public/admin/books.php
 * Admin view and management of all books on the platform.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin('login.php');

$pageTitle = 'Manage Books';
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
            flashSet('success', 'Book deleted.');
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
        <h1>Manage Books</h1>
        <div class="page-header__actions">
            <a href="<?= e(url('add-book.php')) ?>" class="btn btn--primary">+ Add Official Book</a>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" class="filter-bar">
        <input type="search" name="q" class="form-input" value="<?= e($search) ?>"
               placeholder="Search title or author…">
        <select name="type" class="form-input form-select">
            <option value="">All Types</option>
            <option value="official" <?= $typeF === 'official' ? 'selected' : '' ?>>Official</option>
            <option value="used"     <?= $typeF === 'used'     ? 'selected' : '' ?>>Used</option>
        </select>
        <button type="submit" class="btn btn--primary">Filter</button>
        <a href="<?= e(url('admin/books.php')) ?>" class="btn btn--outline">Clear</a>
    </form>

    <p class="result-count"><?= count($books) ?> book(s) found.</p>

    <section class="card">
        <table class="books-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Type</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Seller</th>
                    <th>Status</th>
                    <th>Actions</th>
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
                            <?= e(ucfirst($book['book_type'])) ?>
                        </span>
                    </td>
                    <td><?= formatPrice((float)$book['price']) ?></td>
                    <td><?= (int)$book['stock'] ?></td>
                    <td><?= e($book['seller_name'] ?? '—') ?></td>
                    <td>
                        <?= $book['is_active']
                            ? '<span class="status status--active">Active</span>'
                            : '<span class="status status--blocked">Hidden</span>' ?>
                    </td>
                    <td class="actions">
                        <form method="post" class="inline-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
                            <a href="<?= e(url('add-book.php?edit=' . (int)$book['id'])) ?>" class="btn btn--sm btn--outline">Edit</a>
                            <button
                                name="action"
                                value="toggle"
                                class="btn btn--sm btn--warning"
                                <?php if ($book['is_active']): ?>
                                    data-confirm="Are you sure you want to hide this book?"
                                <?php endif; ?>
                            >
                                <?= $book['is_active'] ? 'Hide' : 'Show' ?>
                            </button>
                            <button
                                name="action"
                                value="delete"
                                class="btn btn--sm btn--danger"
                                data-confirm="Are you sure you want to delete this book? This action cannot be undone."
                            >
                                Delete
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
