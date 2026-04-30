<?php
/**
 * includes/functions.php
 * General utility functions used across the project.
 */

require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------
// URL helpers
// ---------------------------------------------------------------

if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $baseUrl = '/Mkatteb/public';
    $publicSegment = '/public';
    $publicPos = strpos($scriptName, $publicSegment . '/');

    if ($publicPos !== false) {
        $baseUrl = substr($scriptName, 0, $publicPos + strlen($publicSegment));
    } elseif (substr($scriptName, -strlen($publicSegment)) === $publicSegment) {
        $baseUrl = $scriptName;
    }

    define('BASE_URL', rtrim($baseUrl, '/'));
}

/** Build an application URL under the public base path */
function url(string $path = ''): string {
    $path = trim($path);

    if ($path === '') {
        return BASE_URL . '/';
    }

    if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $path) || preg_match('#^(?:mailto|tel|data):#i', $path)) {
        return $path;
    }

    if ($path[0] === '#') {
        return $path;
    }

    $path = str_replace('\\', '/', $path);
    $base = rtrim(BASE_URL, '/');

    if ($path === $base || strpos($path, $base . '/') === 0) {
        return $path;
    }

    if ($path === '/public') {
        $path = '';
    } elseif (strpos($path, '/public/') === 0) {
        $path = substr($path, strlen('/public/'));
    } else {
        $path = ltrim($path, '/');
    }

    return $base . ($path !== '' ? '/' . $path : '');
}

/** Build a URL for public/assets files */
function asset(string $path = ''): string {
    return url('assets/' . ltrim($path, '/'));
}

/** Build a URL for uploaded files */
function uploadUrl(string $path = ''): string {
    return url('uploads/' . ltrim($path, '/'));
}

/** Build a filesystem path for uploaded files */
function uploadPath(string $path = ''): string {
    $path = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    return __DIR__ . '/../public/uploads' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
}

/** Ensure the uploads subdirectory exists and return its filesystem path */
function ensureUploadDir(string $path = ''): string {
    $dir = uploadPath($path);

    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Failed to create uploads directory.');
    }

    return $dir;
}

/** Backward-compatible alias used by older templates */
function app_url(string $path = ''): string {
    return url($path);
}

/** Backward-compatible alias used by older templates */
function asset_url(string $path = ''): string {
    return asset($path);
}

// ---------------------------------------------------------------
// Output helpers
// ---------------------------------------------------------------

/** Escape a string for safe HTML output */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** Redirect and exit */
function redirect(string $url): never {
    header('Location: ' . url($url));
    exit;
}

/** Flash message (store in session to show once) */
function flashSet(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Retrieve and clear flash message */
function flashGet(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** Render flash message HTML */
function flashRender(): string {
    $flash = flashGet();
    if (!$flash) return '';

    $typeClass = match($flash['type']) {
        'success' => 'flash--success',
        'error'   => 'flash--error',
        'warning' => 'flash--warning',
        default   => 'flash--info',
    };

    return sprintf(
        '<div class="flash %s" role="alert">%s</div>',
        $typeClass,
        e($flash['message'])
    );
}

// ---------------------------------------------------------------
// Books helpers
// ---------------------------------------------------------------

/** Build WHERE and ORDER BY clauses for book listings */
function buildBookFilterParts(array $filters): array {
    $where = ['b.is_active = 1'];
    $params = [];
    $intParams = [];
    $decimalParams = [];

    $categoryId = (int)($filters['category_id'] ?? 0);
    if ($categoryId > 0) {
        $where[] = 'b.category_id = :category_id';
        $params[':category_id'] = $categoryId;
        $intParams[] = ':category_id';
    }

    $search = trim((string)($filters['search'] ?? ''));
    if ($search !== '') {
        $where[] = '(b.title LIKE :search_title OR b.author LIKE :search_author)';
        $params[':search_title'] = '%' . $search . '%';
        $params[':search_author'] = '%' . $search . '%';
    }

    $bookType = trim((string)($filters['book_type'] ?? ''));
    if ($bookType === 'new') {
        $bookType = 'official';
    }
    if (in_array($bookType, ['official', 'used'], true)) {
        $where[] = 'b.book_type = :book_type';
        $params[':book_type'] = $bookType;
    }

    $condition = trim((string)($filters['condition'] ?? ''));
    if ($condition === 'good') {
        $condition = 'used';
    }
    if (in_array($condition, ['new', 'like_new', 'used', 'damaged'], true)) {
        $where[] = 'b.condition_type = :condition';
        $params[':condition'] = $condition;
    }

    if (isset($filters['min_price']) && is_numeric($filters['min_price']) && (float)$filters['min_price'] >= 0) {
        $where[] = 'b.price >= :min_price';
        $params[':min_price'] = (float)$filters['min_price'];
        $decimalParams[] = ':min_price';
    }

    if (isset($filters['max_price']) && is_numeric($filters['max_price']) && (float)$filters['max_price'] >= 0) {
        $where[] = 'b.price <= :max_price';
        $params[':max_price'] = (float)$filters['max_price'];
        $decimalParams[] = ':max_price';
    }

    $availability = trim((string)($filters['availability'] ?? ''));
    if (in_array($availability, ['available', 'in_stock'], true)) {
        $where[] = 'b.stock > 0';
    } elseif ($availability === 'out_of_stock') {
        $where[] = 'b.stock <= 0';
    }

    if (isset($filters['rating']) && is_numeric($filters['rating'])) {
        $rating = max(0, min(5, (float)$filters['rating']));
        if ($rating > 0) {
            $where[] = 'COALESCE(r.avg_rating, 0) >= :rating';
            $params[':rating'] = $rating;
            $decimalParams[] = ':rating';
        }
    }

    $sort = trim((string)($filters['sort'] ?? 'newest'));
    $orderBy = match($sort) {
        'views' => 'b.views DESC, b.created_at DESC',
        'price_asc' => 'b.price ASC, b.created_at DESC',
        'price_desc' => 'b.price DESC, b.created_at DESC',
        'title_az' => 'b.title ASC, b.author ASC',
        'rating' => 'avg_rating DESC, b.created_at DESC',
        'oldest' => 'b.created_at ASC',
        default => 'b.created_at DESC',
    };

    return [
        'where' => implode(' AND ', $where),
        'params' => $params,
        'intParams' => $intParams,
        'decimalParams' => $decimalParams,
        'orderBy' => $orderBy,
        'ratingJoin' => "LEFT JOIN (
                SELECT book_id, AVG(rating) AS avg_rating
                FROM reviews
                WHERE is_visible = 1
                GROUP BY book_id
            ) r ON r.book_id = b.id",
    ];
}

/** Bind generated book filter parameters to a PDO statement */
function bindBookFilterParams(PDOStatement $stmt, array $parts): void {
    foreach ($parts['params'] as $name => $value) {
        if (in_array($name, $parts['intParams'], true)) {
            $stmt->bindValue($name, $value, PDO::PARAM_INT);
            continue;
        }

        if (in_array($name, $parts['decimalParams'] ?? [], true)) {
            $stmt->bindValue($name, (string)$value, PDO::PARAM_STR);
            continue;
        }

        $stmt->bindValue($name, $value, PDO::PARAM_STR);
    }
}

/** Get all active books with optional filters */
function getBooks(array $filters = [], int $limit = 20, int $offset = 0): array {
    $parts = buildBookFilterParts($filters);

    $sql = "SELECT b.*, c.name AS category_name, u.name AS seller_name, COALESCE(r.avg_rating, 0) AS avg_rating
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN users u ON b.seller_id = u.id
            {$parts['ratingJoin']}
            WHERE {$parts['where']}
            ORDER BY {$parts['orderBy']}
            LIMIT :limit OFFSET :offset";

    $stmt = db()->prepare($sql);
    bindBookFilterParams($stmt, $parts);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/** Count active books matching optional filters */
function countBooks(array $filters = []): int {
    $parts = buildBookFilterParts($filters);

    $sql = "SELECT COUNT(*)
            FROM books b
            {$parts['ratingJoin']}
            WHERE {$parts['where']}";

    $stmt = db()->prepare($sql);
    bindBookFilterParams($stmt, $parts);
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

/** Get the highest active book price for the browse price slider */
function getActiveBookMaxPrice(): float {
    $stmt = db()->query('SELECT COALESCE(MAX(price), 0) FROM books WHERE is_active = 1');
    return (float)$stmt->fetchColumn();
}

/** Get a single book by ID */
function getBook(int $id): ?array {
    $stmt = db()->prepare(
        "SELECT b.*, c.name AS category_name, u.name AS seller_name, u.email AS seller_email
         FROM books b
         LEFT JOIN categories c ON b.category_id = c.id
         LEFT JOIN users u ON b.seller_id = u.id
         WHERE b.id = ?"
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** Ensure the multiple-image table exists for current and fresh databases */
function ensureBookImagesTable(): void {
    static $ready = false;
    if ($ready) return;

    db()->exec(
        "CREATE TABLE IF NOT EXISTS book_images (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            book_id INT UNSIGNED NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            is_primary TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_book_images_book (book_id),
            CONSTRAINT fk_book_images_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $ready = true;
}

/** Copy a legacy books.cover_image value into book_images if needed */
function syncLegacyBookImage(int $bookId, ?string $cover): void {
    $cover = trim((string)$cover);
    if ($bookId <= 0 || $cover === '') return;

    ensureBookImagesTable();
    $count = db()->prepare('SELECT COUNT(*) FROM book_images WHERE book_id = ?');
    $count->execute([$bookId]);

    if ((int)$count->fetchColumn() === 0) {
        $stmt = db()->prepare('INSERT INTO book_images (book_id, image_path, is_primary) VALUES (?, ?, 1)');
        $stmt->execute([$bookId, $cover]);
    }
}

/** Get all images for a book, with an optional legacy cover fallback */
function getBookImages(int $bookId, ?string $legacyCover = null): array {
    if ($bookId <= 0) return [];

    ensureBookImagesTable();
    $stmt = db()->prepare('SELECT * FROM book_images WHERE book_id = ? ORDER BY is_primary DESC, id ASC');
    $stmt->execute([$bookId]);
    $images = $stmt->fetchAll();

    if (empty($images) && trim((string)$legacyCover) !== '') {
        return [[
            'id' => null,
            'book_id' => $bookId,
            'image_path' => $legacyCover,
            'is_primary' => 1,
            'created_at' => null,
        ]];
    }

    return $images;
}

/** Return the primary image path for a book row */
function getPrimaryBookImagePath(array $book): ?string {
    $images = getBookImages((int)($book['id'] ?? 0), $book['cover_image'] ?? null);
    return $images[0]['image_path'] ?? ($book['cover_image'] ?? null);
}

/** Get all categories */
function getCategories(): array {
    $stmt = db()->prepare('SELECT * FROM categories ORDER BY sort_order ASC, name ASC');
    $stmt->execute();
    return $stmt->fetchAll();
}

/** Get category by slug */
function getCategoryBySlug(string $slug): ?array {
    $stmt = db()->prepare('SELECT * FROM categories WHERE slug = ?');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/** Condition label */
function conditionLabel(string $condition): string {
    return match($condition) {
        'new'      => 'New',
        'like_new' => 'Like New',
        'used'     => 'Used',
        'damaged'  => 'Damaged',
        default    => ucfirst($condition),
    };
}

/** Format price in BHD */
function formatPrice(float $price): string {
    return 'BD ' . number_format($price, 3);
}

/** Book cover image URL (with fallback) */
function bookCoverUrl(?string $cover, string $title = 'No cover'): string {
    $cover = trim((string)$cover);

    if ($cover !== '') {
        if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $cover) || preg_match('#^data:#i', $cover)) {
            return $cover;
        }

        $cover = str_replace('\\', '/', $cover);
        $cover = preg_replace('#^/?(?:public/)?uploads/#', '', $cover);

        if (file_exists(uploadPath($cover))) {
            return uploadUrl($cover);
        }
    }

    $label = function_exists('mb_substr') ? mb_substr($title, 0, 10) : substr($title, 0, 10);
    $label = trim($label) !== '' ? $label : 'No cover';

    return 'https://placehold.co/160x220/e8dcc8/7a6652?text=' . rawurlencode($label);
}

/** Book cover URL from a full book row */
function bookCoverUrlFromBook(array $book): string {
    return bookCoverUrl(getPrimaryBookImagePath($book), (string)($book['title'] ?? 'No cover'));
}

/** Normalize a multi-file upload field into a simple file list */
function normalizeUploadedFiles(array $field): array {
    if (!isset($field['name'])) return [];

    if (!is_array($field['name'])) {
        return [$field];
    }

    $files = [];
    foreach ($field['name'] as $index => $name) {
        if ($name === '' || ($field['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $files[] = [
            'name' => $name,
            'type' => $field['type'][$index] ?? '',
            'tmp_name' => $field['tmp_name'][$index] ?? '',
            'error' => $field['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $field['size'][$index] ?? 0,
        ];
    }

    return $files;
}

/** Validate, move, and store uploaded book images */
function saveBookImages(int $bookId, array $files, int $maxImages = 5): array {
    ensureBookImagesTable();

    $errors = [];
    $saved = [];
    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $maxSize = 3 * 1024 * 1024;

    $countStmt = db()->prepare('SELECT COUNT(*) FROM book_images WHERE book_id = ?');
    $countStmt->execute([$bookId]);
    $existingCount = (int)$countStmt->fetchColumn();

    if ($existingCount + count($files) > $maxImages) {
        $errors[] = 'You can upload up to ' . $maxImages . ' images per book.';
        return ['errors' => $errors, 'saved' => $saved];
    }

    $uploadDir = ensureUploadDir('books');
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($files as $file) {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'One image could not be uploaded. Please try again.';
            continue;
        }

        if (($file['size'] ?? 0) > $maxSize) {
            $errors[] = basename($file['name']) . ' is larger than 3MB.';
            continue;
        }

        $mime = $finfo->file($file['tmp_name']);
        if (!isset($allowedMimeTypes[$mime])) {
            $errors[] = basename($file['name']) . ' must be a JPG, PNG, or WebP image.';
            continue;
        }

        $filename = uniqid('book_', true) . '.' . $allowedMimeTypes[$mime];
        $relativePath = 'books/' . $filename;
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $errors[] = 'Failed to save ' . basename($file['name']) . '.';
            continue;
        }

        $saved[] = $relativePath;
    }

    if ($errors) {
        foreach ($saved as $path) {
            $fullPath = uploadPath($path);
            if (is_file($fullPath)) {
                unlink($fullPath);
            }
        }
        return ['errors' => $errors, 'saved' => $saved];
    }

    $hasPrimary = false;
    $primaryStmt = db()->prepare('SELECT COUNT(*) FROM book_images WHERE book_id = ? AND is_primary = 1');
    $primaryStmt->execute([$bookId]);
    $hasPrimary = (int)$primaryStmt->fetchColumn() > 0;

    $insert = db()->prepare('INSERT INTO book_images (book_id, image_path, is_primary) VALUES (?, ?, ?)');
    foreach ($saved as $index => $path) {
        $isPrimary = (!$hasPrimary && $index === 0) ? 1 : 0;
        $insert->execute([$bookId, $path, $isPrimary]);
        if ($isPrimary) {
            db()->prepare('UPDATE books SET cover_image = ? WHERE id = ?')->execute([$path, $bookId]);
            $hasPrimary = true;
        }
    }

    return ['errors' => [], 'saved' => $saved];
}

// ---------------------------------------------------------------
// Cart helpers
// ---------------------------------------------------------------

function getCartUniqueCount(int $userId): int {
    $stmt = db()->prepare('SELECT COUNT(*) FROM cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function getCartItems(int $userId): array {
    $stmt = db()->prepare(
        "SELECT ci.book_id, ci.quantity, b.*, c.name AS category_name
         FROM cart_items ci
         JOIN books b ON b.id = ci.book_id
         LEFT JOIN categories c ON c.id = b.category_id
         WHERE ci.user_id = ?
         ORDER BY ci.added_at DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function addToCart(int $userId, int $bookId, int $quantity = 1): array {
    $book = getBook($bookId);
    if (!$book || empty($book['is_active'])) {
        return ['error' => 'This book is not available.'];
    }

    $stock = (int)$book['stock'];
    if ($stock < 1) {
        return ['error' => 'This book is out of stock.'];
    }

    $quantity = max(1, min($quantity, $stock));

    $existing = db()->prepare('SELECT quantity FROM cart_items WHERE user_id = ? AND book_id = ?');
    $existing->execute([$userId, $bookId]);
    $currentQuantity = (int)($existing->fetchColumn() ?: 0);
    $newQuantity = min($stock, $currentQuantity + $quantity);

    $stmt = db()->prepare(
        'INSERT INTO cart_items (user_id, book_id, quantity)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), added_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$userId, $bookId, $newQuantity]);

    return ['ok' => true, 'quantity' => $newQuantity];
}

function updateCartQuantity(int $userId, int $bookId, int $quantity): array {
    if ($quantity <= 0) {
        removeCartItem($userId, $bookId);
        return ['ok' => true, 'removed' => true];
    }

    $book = getBook($bookId);
    if (!$book || empty($book['is_active'])) {
        removeCartItem($userId, $bookId);
        return ['error' => 'That book is no longer available and was removed from your cart.'];
    }

    $quantity = min($quantity, (int)$book['stock']);
    if ($quantity < 1) {
        removeCartItem($userId, $bookId);
        return ['error' => 'That book is out of stock and was removed from your cart.'];
    }

    $stmt = db()->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND book_id = ?');
    $stmt->execute([$quantity, $userId, $bookId]);

    return ['ok' => true, 'quantity' => $quantity];
}

function removeCartItem(int $userId, int $bookId): void {
    $stmt = db()->prepare('DELETE FROM cart_items WHERE user_id = ? AND book_id = ?');
    $stmt->execute([$userId, $bookId]);
}

function clearCart(int $userId): void {
    $stmt = db()->prepare('DELETE FROM cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);
}

// ---------------------------------------------------------------
// User helpers
// ---------------------------------------------------------------

/** Get user by ID */
function getUserById(int $id): ?array {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** Get books listed by a specific user */
function getUserBooks(int $userId): array {
    $stmt = db()->prepare(
        "SELECT b.*, c.name AS category_name
         FROM books b
         LEFT JOIN categories c ON b.category_id = c.id
         WHERE b.seller_id = ?
         ORDER BY b.created_at DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// ---------------------------------------------------------------
// Notification helpers
// ---------------------------------------------------------------

function addNotification(int $userId, string $type, string $message, ?string $link = null): void {
    $stmt = db()->prepare(
        'INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $type, $message, $link]);
}

function getUnreadCount(int $userId): int {
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
