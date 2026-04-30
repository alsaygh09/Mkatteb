<?php
/**
 * public/admin/categories.php
 * Admin: add, edit, delete categories.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin('login.php');

$pageTitle = t('manage_categories');
$pdo = db();
$errors = [];
$defaultIcon = '📚';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $nameAr = trim($_POST['name_ar'] ?? '');
        $desc = trim($_POST['desc'] ?? '');
        $descAr = trim($_POST['desc_ar'] ?? '');
        $icon = trim($_POST['icon'] ?? $defaultIcon);
        $order = (int)($_POST['sort_order'] ?? 0);
        $slug = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)), '-');

        $nameLength = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
        $nameArLength = function_exists('mb_strlen') ? mb_strlen($nameAr) : strlen($nameAr);

        if ($nameLength < 2 || $slug === '') {
            $errors[] = t('error_category_english_name_min');
        }

        if ($nameArLength < 2) {
            $errors[] = t('error_category_arabic_name_min');
        }

        if ($icon === '') {
            $icon = $defaultIcon;
        }

        if (empty($errors)) {
            if ($action === 'add') {
                $stmt = $pdo->prepare(
                    'INSERT INTO categories (name, name_ar, slug, description, description_ar, icon, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );

                try {
                    $stmt->execute([$name, $nameAr, $slug, $desc, $descAr, $icon, $order]);
                    flashSet('success', t('flash_category_added'));
                } catch (PDOException $e) {
                    $errors[] = t('error_category_exists');
                }
            } elseif ($action === 'edit') {
                $id = (int)($_POST['category_id'] ?? 0);
                $stmt = $pdo->prepare(
                    'UPDATE categories
                     SET name = ?, name_ar = ?, slug = ?, description = ?, description_ar = ?, icon = ?, sort_order = ?
                     WHERE id = ?'
                );

                try {
                    $stmt->execute([$name, $nameAr, $slug, $desc, $descAr, $icon, $order, $id]);
                    flashSet('success', t('flash_category_updated'));
                } catch (PDOException $e) {
                    $errors[] = t('error_category_exists');
                }
            }

            if (empty($errors)) {
                redirect('admin/categories.php');
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['category_id'] ?? 0);
        // Check if books exist in this category
        $count = $pdo->prepare('SELECT COUNT(*) FROM books WHERE category_id = ?');
        $count->execute([$id]);

        if ($count->fetchColumn() > 0) {
            flashSet('error', t('error_category_has_books'));
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flashSet('success', t('flash_category_deleted'));
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
        <h1><?= e(t('manage_categories')) ?></h1>
    </div>

    <?php if ($errors): ?>
    <div class="flash flash--error"><?php foreach ($errors as $e): ?><p><?= e($e) ?></p><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="admin-split">

        <!-- Category List -->
        <section class="card">
            <h2 class="card__title"><?= e(t('all_categories')) ?></h2>
            <div class="books-table-wrap">
                <table class="books-table">
                    <thead>
                        <tr>
                            <th><?= e(t('icon')) ?></th>
                            <th><?= e(t('english_name')) ?></th>
                            <th><?= e(t('arabic_name')) ?></th>
                            <th><?= e(t('slug')) ?></th>
                            <th><?= e(t('sort_order')) ?></th>
                            <th><?= e(t('books')) ?></th>
                            <th><?= e(t('actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat):
                            $bc = $pdo->prepare('SELECT COUNT(*) FROM books WHERE category_id = ?');
                            $bc->execute([$cat['id']]);
                            $cnt = $bc->fetchColumn();
                        ?>
                        <tr>
                            <td><?= e($cat['icon'] ?? '') ?></td>
                            <td><?= e($cat['name']) ?></td>
                            <td><?= e($cat['name_ar'] ?? '') ?></td>
                            <td><code><?= e($cat['slug']) ?></code></td>
                            <td><?= (int)$cat['sort_order'] ?></td>
                            <td><?= (int)$cnt ?></td>
                            <td class="actions">
                                <a href="?edit=<?= (int)$cat['id'] ?>" class="btn btn--sm btn--outline"><?= e(t('edit')) ?></a>
                                <?php if ($cnt == 0): ?>
                                <form method="post" class="inline-form">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="category_id" value="<?= (int)$cat['id'] ?>">
                                    <button
                                        name="action"
                                        value="delete"
                                        class="btn btn--sm btn--danger"
                                        data-confirm="<?= e(t('confirm_delete_category')) ?>"
                                    >
                                        <?= e(t('delete')) ?>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Add / Edit Form -->
        <section class="card">
            <h2 class="card__title"><?= e($editCat ? t('edit_category') : t('add_category')) ?></h2>
            <form method="post" class="auth-form">
                <?= csrfField() ?>
                <?php if ($editCat): ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="category_id" value="<?= (int)$editCat['id'] ?>">
                <?php else: ?>
                    <input type="hidden" name="action" value="add">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label"><?= e(t('english_name')) ?></label>
                    <input type="text" name="name" class="form-input" dir="ltr" required
                           value="<?= e($editCat['name'] ?? '') ?>" placeholder="<?= e(t('category_english_name_placeholder')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('arabic_name')) ?></label>
                    <input type="text" name="name_ar" class="form-input" dir="rtl" required
                           value="<?= e($editCat['name_ar'] ?? '') ?>" placeholder="<?= e(t('category_arabic_name_placeholder')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('icon')) ?></label>
                    <input type="text" name="icon" class="form-input"
                           value="<?= e($editCat['icon'] ?? $defaultIcon) ?>" placeholder="<?= e($defaultIcon) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('english_description')) ?></label>
                    <textarea name="desc" class="form-input" dir="ltr" rows="2"
                              placeholder="<?= e(t('category_english_description_placeholder')) ?>"><?= e($editCat['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('arabic_description')) ?></label>
                    <textarea name="desc_ar" class="form-input" dir="rtl" rows="2"
                              placeholder="<?= e(t('category_arabic_description_placeholder')) ?>"><?= e($editCat['description_ar'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('sort_order')) ?></label>
                    <input type="number" name="sort_order" class="form-input"
                           value="<?= (int)($editCat['sort_order'] ?? 0) ?>">
                </div>
                <div class="form-row">
                    <button type="submit" class="btn btn--primary">
                        <?= e($editCat ? t('update_category') : t('add_category')) ?>
                    </button>
                    <?php if ($editCat): ?>
                        <a href="<?= e(url('admin/categories.php')) ?>" class="btn btn--outline"><?= e(t('cancel')) ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
