<?php
/**
 * public/add-book.php
 * List or edit a used book. Admins can add official books.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('login.php');

$pageTitle  = t('add_book');
$categories = getCategories();
$errors     = [];
$pdo        = db();

$editBook = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId) {
    $editBook = getBook($editId);
    if (!$editBook || ((int)$editBook['seller_id'] !== (int)currentUserId() && !isAdmin())) {
        flashSet('error', t('error_no_permission_edit_book'));
        redirect('account.php');
    }
    syncLegacyBookImage((int)$editBook['id'], $editBook['cover_image'] ?? null);
    $pageTitle = t('edit_book');
}

$bookImages = $editBook ? getBookImages((int)$editBook['id'], $editBook['cover_image'] ?? null) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verifyCsrf();
    $delId = (int)($_POST['book_id'] ?? 0);
    $check = getBook($delId);
    if ($check && ((int)$check['seller_id'] === (int)currentUserId() || isAdmin())) {
        $pdo->prepare('DELETE FROM books WHERE id = ?')->execute([$delId]);
        flashSet('success', t('flash_book_listing_deleted'));
        redirect(isAdmin() ? 'admin/books.php' : 'account.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add', 'edit'], true)) {
    verifyCsrf();

    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 1);
    $catId = (int)($_POST['category_id'] ?? 0) ?: null;
    $condition = $_POST['condition_type'] ?? 'used';
    $desc = trim($_POST['description'] ?? '');
    $bookType = isAdmin() ? ($_POST['book_type'] ?? 'official') : 'used';
    $bookLanguage = trim($_POST['book_language'] ?? 'english');
    $uploadedImages = normalizeUploadedFiles($_FILES['book_images'] ?? []);

    if (!in_array($bookLanguage, ['arabic', 'english'], true)) {
        $bookLanguage = 'english';
    }

    if (strlen($title) < 2) $errors[] = t('error_title_required');
    if (strlen($author) < 2) $errors[] = t('error_author_required');
    if ($price <= 0) $errors[] = t('error_price_positive');

    if (empty($errors)) {
        if ($_POST['action'] === 'add') {
            $stmt = $pdo->prepare(
                'INSERT INTO books (seller_id, category_id, title, author, description,
                                    price, stock, condition_type, book_type, book_language, cover_image)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                currentUserId(), $catId, $title, $author, $desc,
                $price, $stock, $condition, $bookType, $bookLanguage, null
            ]);
            $savedBookId = (int)$pdo->lastInsertId();
        } else {
            $savedBookId = (int)$_POST['book_id'];
            $stmt = $pdo->prepare(
                'UPDATE books SET category_id=?, title=?, author=?, description=?,
                                  price=?, stock=?, condition_type=?, book_type=?, book_language=?
                 WHERE id=?'
            );
            $stmt->execute([
                $catId, $title, $author, $desc,
                $price, $stock, $condition, $bookType, $bookLanguage, $savedBookId
            ]);
        }

        if ($editBook) {
            syncLegacyBookImage($savedBookId, $editBook['cover_image'] ?? null);
        }

        $imageResult = $uploadedImages ? saveBookImages($savedBookId, $uploadedImages, 5) : ['errors' => [], 'saved' => []];
        if (!empty($imageResult['errors'])) {
            flashSet('error', implode(' ', $imageResult['errors']));
        } else {
            flashSet('success', $_POST['action'] === 'add' ? t('flash_book_listed') : t('flash_book_updated'));
        }

        redirect(isAdmin() ? 'admin/books.php' : 'account.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><?= e($editBook ? t('edit_book_listing') : t('list_book_for_sale')) ?></h1>
        <?php if (!isAdmin()): ?>
        <p class="page-sub"><?= e(t('used_books_only_notice')) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($errors): ?>
    <div class="flash flash--error">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="form-page-wrap">
        <form method="post" enctype="multipart/form-data" class="auth-form card">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editBook ? 'edit' : 'add' ?>">
            <?php if ($editBook): ?>
            <input type="hidden" name="book_id" value="<?= (int)$editBook['id'] ?>">
            <?php endif; ?>

            <?php if (isAdmin()): ?>
            <div class="form-group">
                <label class="form-label"><?= e(t('book_type')) ?></label>
                <select name="book_type" class="form-input form-select">
                    <option value="official" <?= ($editBook['book_type'] ?? '') === 'official' ? 'selected' : '' ?>><?= e(t('official_store_book')) ?></option>
                    <option value="used" <?= ($editBook['book_type'] ?? '') === 'used' ? 'selected' : '' ?>><?= e(t('used_book')) ?></option>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label"><?= e(t('book_language')) ?></label>
                <select name="book_language" class="form-input form-select" required>
                    <?php foreach (['arabic', 'english'] as $language): ?>
                    <option value="<?= e($language) ?>" <?= ($editBook['book_language'] ?? 'english') === $language ? 'selected' : '' ?>>
                        <?= e(bookLanguageLabel($language)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group form-group--grow">
                    <label class="form-label"><?= e(t('title')) ?> *</label>
                    <input type="text" name="title" class="form-input" required value="<?= e($editBook['title'] ?? '') ?>" placeholder="<?= e(t('title')) ?>">
                </div>
                <div class="form-group form-group--grow">
                    <label class="form-label"><?= e(t('author')) ?> *</label>
                    <input type="text" name="author" class="form-input" required value="<?= e($editBook['author'] ?? '') ?>" placeholder="<?= e(t('author')) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-group--grow">
                    <label class="form-label"><?= e(t('price_bd')) ?> *</label>
                    <input type="number" name="price" class="form-input" required min="0.001" step="0.001" value="<?= number_format((float)($editBook['price'] ?? 0), 3) ?>">
                </div>
                <div class="form-group form-group--grow">
                    <label class="form-label"><?= e(t('stock_quantity')) ?></label>
                    <input type="number" name="stock" class="form-input" min="0" value="<?= (int)($editBook['stock'] ?? 1) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-group--grow">
                    <label class="form-label"><?= e(t('category')) ?></label>
                    <select name="category_id" class="form-input form-select">
                        <option value=""><?= e(t('select_category')) ?></option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= (int)($editBook['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['icon'] ?? '') ?> <?= e(localizedCategoryName($cat)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group form-group--grow">
                    <label class="form-label"><?= e(t('condition')) ?></label>
                    <select name="condition_type" class="form-input form-select">
                        <?php foreach (['new' => t('condition_new'), 'like_new' => t('condition_like_new'), 'used' => t('condition_used'), 'damaged' => t('condition_damaged')] as $val => $lbl): ?>
                        <option value="<?= e($val) ?>" <?= ($editBook['condition_type'] ?? 'used') === $val ? 'selected' : '' ?>>
                            <?= e($lbl) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= e(t('description')) ?></label>
                <textarea name="description" class="form-input" rows="4" placeholder="<?= e(t('short_description_book')) ?>"><?= e($editBook['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label"><?= e(t('book_photos')) ?></label>
                <?php if (!empty($bookImages)): ?>
                <div class="image-preview-grid current-cover">
                    <?php foreach ($bookImages as $image): ?>
                    <img src="<?= e(bookCoverUrl($image['image_path'], $editBook['title'])) ?>" alt="<?= e(t('current_book_photo')) ?>">
                    <?php endforeach; ?>
                </div>
                <span class="form-hint"><?= e(t('existing_photos_kept')) ?></span>
                <?php endif; ?>
                <label class="drop-zone" for="bookImagesInput">
                    <span class="drop-zone__title"><?= e(t('drop_images_choose')) ?></span>
                    <span class="drop-zone__hint"><?= e(t('upload_hint')) ?></span>
                    <input id="bookImagesInput" type="file" name="book_images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
                </label>
                <div class="image-preview-grid" data-image-preview></div>
            </div>

            <div class="form-row">
                <button type="submit" class="btn btn--primary"><?= e($editBook ? t('save_changes') : t('list_book')) ?></button>
                <a href="<?= e(url(isAdmin() ? 'admin/books.php' : 'account.php')) ?>" class="btn btn--outline"><?= e(t('cancel')) ?></a>
            </div>
        </form>

        <?php if ($editBook): ?>
        <form method="post" class="delete-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="book_id" value="<?= (int)$editBook['id'] ?>">
            <button type="submit" class="btn btn--danger" data-confirm="<?= e(t('confirm_delete_listing')) ?>">
                <?= e(t('delete_listing')) ?>
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
