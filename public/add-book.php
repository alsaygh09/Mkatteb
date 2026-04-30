<?php
/**
 * public/add-book.php
 * List or edit a used book (for regular users).
 * Admin can also add official books here.
 * Full implementation in Phase 3 — basic form provided now.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('login.php');

$pageTitle  = 'Add a Book';
$categories = getCategories();
$errors     = [];
$pdo        = db();

// Edit mode
$editBook   = null;
$editId     = (int)($_GET['edit'] ?? 0);
if ($editId) {
    $editBook = getBook($editId);
    // Only admin or the owner can edit
    if (!$editBook || ($editBook['seller_id'] !== currentUserId() && !isAdmin())) {
        flashSet('error', 'You do not have permission to edit this book.');
        redirect('account.php');
    }
    syncLegacyBookImage((int)$editBook['id'], $editBook['cover_image'] ?? null);
    $pageTitle = 'Edit Book';
}

$bookImages = $editBook ? getBookImages((int)$editBook['id'], $editBook['cover_image'] ?? null) : [];

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verifyCsrf();
    $delId = (int)($_POST['book_id'] ?? 0);
    $check = getBook($delId);
    if ($check && ($check['seller_id'] === currentUserId() || isAdmin())) {
        $pdo->prepare('DELETE FROM books WHERE id = ?')->execute([$delId]);
        flashSet('success', 'Book listing deleted.');
        redirect(isAdmin() ? 'admin/books.php' : 'account.php');
    }
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add', 'edit'])) {
    verifyCsrf();

    $title     = trim($_POST['title']       ?? '');
    $author    = trim($_POST['author']      ?? '');
    $price     = (float)($_POST['price']    ?? 0);
    $stock     = (int)($_POST['stock']      ?? 1);
    $catId     = (int)($_POST['category_id']?? 0) ?: null;
    $condition = $_POST['condition_type']   ?? 'used';
    $desc      = trim($_POST['description'] ?? '');
    $bookType  = isAdmin() ? ($_POST['book_type'] ?? 'official') : 'used';
    $uploadedImages = normalizeUploadedFiles($_FILES['book_images'] ?? []);

    // Validation
    if (strlen($title) < 2)  $errors[] = 'Title is required.';
    if (strlen($author) < 2) $errors[] = 'Author is required.';
    if ($price <= 0)         $errors[] = 'Price must be greater than zero.';

    if (empty($errors)) {
        if ($_POST['action'] === 'add') {
            $stmt = $pdo->prepare(
                'INSERT INTO books (seller_id, category_id, title, author, description,
                                    price, stock, condition_type, book_type, cover_image)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                currentUserId(), $catId, $title, $author, $desc,
                $price, $stock, $condition, $bookType, null
            ]);
            $savedBookId = (int)$pdo->lastInsertId();
        } else {
            $savedBookId = (int)$_POST['book_id'];
            $stmt = $pdo->prepare(
                'UPDATE books SET category_id=?, title=?, author=?, description=?,
                                  price=?, stock=?, condition_type=?, book_type=?
                 WHERE id=?'
            );
            $stmt->execute([
                $catId, $title, $author, $desc,
                $price, $stock, $condition, $bookType, $savedBookId
            ]);
        }

        if ($editBook) {
            syncLegacyBookImage($savedBookId, $editBook['cover_image'] ?? null);
        }

        $imageResult = $uploadedImages ? saveBookImages($savedBookId, $uploadedImages, 5) : ['errors' => [], 'saved' => []];
        if (!empty($imageResult['errors'])) {
            flashSet('error', implode(' ', $imageResult['errors']));
        } else {
            flashSet('success', $_POST['action'] === 'add' ? 'Book listed successfully!' : 'Book updated.');
        }

        redirect(isAdmin() ? 'admin/books.php' : 'account.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><?= $editBook ? 'Edit Book Listing' : 'List a Book for Sale' ?></h1>
        <?php if (!isAdmin()): ?>
        <p class="page-sub">You can only list used books. Admin adds official store books.</p>
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

            <!-- Admin-only: book type -->
            <?php if (isAdmin()): ?>
            <div class="form-group">
                <label class="form-label">Book Type</label>
                <select name="book_type" class="form-input form-select">
                    <option value="official" <?= ($editBook['book_type'] ?? '') === 'official' ? 'selected' : '' ?>>🏪 Official Store Book</option>
                    <option value="used"     <?= ($editBook['book_type'] ?? '') === 'used'     ? 'selected' : '' ?>>♻️ Used Book</option>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group form-group--grow">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-input" required
                           value="<?= e($editBook['title'] ?? '') ?>" placeholder="Book title">
                </div>
                <div class="form-group form-group--grow">
                    <label class="form-label">Author *</label>
                    <input type="text" name="author" class="form-input" required
                           value="<?= e($editBook['author'] ?? '') ?>" placeholder="Author name">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-group--grow">
                    <label class="form-label">Price (BD) *</label>
                    <input type="number" name="price" class="form-input" required
                           min="0.001" step="0.001"
                           value="<?= number_format((float)($editBook['price'] ?? 0), 3) ?>">
                </div>
                <div class="form-group form-group--grow">
                    <label class="form-label">Stock / Quantity</label>
                    <input type="number" name="stock" class="form-input" min="0"
                           value="<?= (int)($editBook['stock'] ?? 1) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-group--grow">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-input form-select">
                        <option value="">— Select category —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>"
                            <?= (int)($editBook['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['icon'] ?? '') ?> <?= e($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group form-group--grow">
                    <label class="form-label">Condition</label>
                    <select name="condition_type" class="form-input form-select">
                        <?php foreach (['new' => 'New', 'like_new' => 'Like New', 'used' => 'Used', 'damaged' => 'Damaged'] as $val => $lbl): ?>
                        <option value="<?= $val ?>"
                            <?= ($editBook['condition_type'] ?? 'used') === $val ? 'selected' : '' ?>>
                            <?= $lbl ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="4"
                          placeholder="Short description of the book…"><?= e($editBook['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Book Photos</label>
                <?php if (!empty($bookImages)): ?>
                <div class="image-preview-grid current-cover">
                    <?php foreach ($bookImages as $image): ?>
                    <img src="<?= e(bookCoverUrl($image['image_path'], $editBook['title'])) ?>" alt="Current book photo">
                    <?php endforeach; ?>
                </div>
                <span class="form-hint">Existing photos are kept. New photos are added up to 5 total.</span>
                <?php endif; ?>
                <label class="drop-zone" for="bookImagesInput">
                    <span class="drop-zone__title">Drop images here or click to choose</span>
                    <span class="drop-zone__hint">JPG, PNG, or WebP - max 3MB each - up to 5 images</span>
                    <input id="bookImagesInput" type="file" name="book_images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
                </label>
                <div class="image-preview-grid" data-image-preview></div>
            </div>

            <div class="form-row">
                <button type="submit" class="btn btn--primary">
                    <?= $editBook ? 'Save Changes' : 'List Book' ?>
                </button>
                <a href="<?= e(url(isAdmin() ? 'admin/books.php' : 'account.php')) ?>" class="btn btn--outline">Cancel</a>
            </div>
        </form>

        <!-- Delete -->
        <?php if ($editBook): ?>
        <form method="post" class="delete-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="book_id" value="<?= (int)$editBook['id'] ?>">
            <button
                type="submit"
                class="btn btn--danger"
                data-confirm="Are you sure you want to delete this book listing? This action cannot be undone."
            >
                🗑️ Delete This Listing
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
