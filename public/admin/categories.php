<?php
/**
 * public/admin/categories.php
 * Admin: add, edit, delete categories.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin('login.php');

$pageTitle = 'Manage Categories';
$pdo = db();
$errors = [];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name  = trim($_POST['name']  ?? '');
        $desc  = trim($_POST['desc']  ?? '');
        $icon  = trim($_POST['icon']  ?? '📚');
        $order = (int)($_POST['sort_order'] ?? 0);
        $slug  = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));

        if (strlen($name) < 2) {
            $errors[] = 'Category name must be at least 2 characters.';
        } else {
            if ($action === 'add') {
                $stmt = $pdo->prepare(
                    'INSERT INTO categories (name, slug, description, icon, sort_order)
                     VALUES (?, ?, ?, ?, ?)'
                );
                try {
                    $stmt->execute([$name, $slug, $desc, $icon, $order]);
                    flashSet('success', 'Category added.');
                } catch (PDOException $e) {
                    $errors[] = 'Category name or slug already exists.';
                }
            } elseif ($action === 'edit') {
                $id = (int)($_POST['category_id'] ?? 0);
                $stmt = $pdo->prepare(
                    'UPDATE categories SET name=?, slug=?, description=?, icon=?, sort_order=? WHERE id=?'
                );
                $stmt->execute([$name, $slug, $desc, $icon, $order, $id]);
                flashSet('success', 'Category updated.');
            }
            if (empty($errors)) redirect('admin/categories.php');
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['category_id'] ?? 0);
        // Check if books exist in this category
        $count = $pdo->prepare('SELECT COUNT(*) FROM books WHERE category_id = ?');
        $count->execute([$id]);
        if ($count->fetchColumn() > 0) {
            flashSet('error', 'Cannot delete: books exist in this category. Reassign them first.');
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flashSet('success', 'Category deleted.');
        }
        redirect('admin/categories.php');
    }
}

$categories = getCategories();

// Editing mode
$editCat = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editCat = $stmt->fetch() ?: null;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container admin-page">
    <div class="page-header">
        <h1>Manage Categories</h1>
    </div>

    <?php if ($errors): ?>
    <div class="flash flash--error"><?php foreach ($errors as $e): ?><p><?= e($e) ?></p><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="admin-split">

        <!-- Category List -->
        <section class="card">
            <h2 class="card__title">All Categories</h2>
            <table class="books-table">
                <thead><tr><th>Icon</th><th>Name</th><th>Slug</th><th>Order</th><th>Books</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($categories as $cat):
                        $bc = $pdo->prepare('SELECT COUNT(*) FROM books WHERE category_id = ?');
                        $bc->execute([$cat['id']]);
                        $cnt = $bc->fetchColumn();
                    ?>
                    <tr>
                        <td><?= e($cat['icon'] ?? '') ?></td>
                        <td><?= e($cat['name']) ?></td>
                        <td><code><?= e($cat['slug']) ?></code></td>
                        <td><?= (int)$cat['sort_order'] ?></td>
                        <td><?= (int)$cnt ?></td>
                        <td class="actions">
                            <a href="?edit=<?= (int)$cat['id'] ?>" class="btn btn--sm btn--outline">Edit</a>
                            <?php if ($cnt == 0): ?>
                            <form method="post" class="inline-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>">
                                <button
                                    name="action"
                                    value="delete"
                                    class="btn btn--sm btn--danger"
                                    data-confirm="Are you sure you want to delete this category? This action cannot be undone."
                                >
                                    Delete
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Add / Edit Form -->
        <section class="card">
            <h2 class="card__title"><?= $editCat ? 'Edit Category' : 'Add Category' ?></h2>
            <form method="post" class="auth-form">
                <?= csrfField() ?>
                <?php if ($editCat): ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="category_id" value="<?= (int)$editCat['id'] ?>">
                <?php else: ?>
                    <input type="hidden" name="action" value="add">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-input" required
                           value="<?= e($editCat['name'] ?? '') ?>" placeholder="e.g. Science Fiction">
                </div>
                <div class="form-group">
                    <label class="form-label">Icon (Emoji)</label>
                    <input type="text" name="icon" class="form-input"
                           value="<?= e($editCat['icon'] ?? '📚') ?>" placeholder="📚">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="desc" class="form-input" rows="2"
                              placeholder="Short description"><?= e($editCat['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-input"
                           value="<?= (int)($editCat['sort_order'] ?? 0) ?>">
                </div>
                <div class="form-row">
                    <button type="submit" class="btn btn--primary">
                        <?= $editCat ? 'Update Category' : 'Add Category' ?>
                    </button>
                    <?php if ($editCat): ?>
                        <a href="<?= e(url('admin/categories.php')) ?>" class="btn btn--outline">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
