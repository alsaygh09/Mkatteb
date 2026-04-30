<?php
/**
 * public/books.php
 * Full book listing with search and filters.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = t('browse_books');
$categories = getCategories();

$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]) ?: 0;

$searchTerm = trim($_GET['search'] ?? ($_GET['q'] ?? ''));

$typeFilter = trim($_GET['type'] ?? ($_GET['book_type'] ?? ''));
if ($typeFilter === 'new') {
    $typeFilter = 'official';
}
if (!in_array($typeFilter, ['', 'official', 'used'], true)) {
    $typeFilter = '';
}

$conditionFilter = trim($_GET['condition'] ?? '');
if (!in_array($conditionFilter, ['', 'new', 'like_new', 'good', 'used', 'damaged'], true)) {
    $conditionFilter = '';
}
$conditionForQuery = $conditionFilter === 'good' ? 'used' : $conditionFilter;

$availabilityFilter = trim($_GET['availability'] ?? '');
if ($availabilityFilter === 'available') {
    $availabilityFilter = 'in_stock';
}
if (!in_array($availabilityFilter, ['', 'in_stock', 'out_of_stock'], true)) {
    $availabilityFilter = '';
}

$sortFilter = trim($_GET['sort'] ?? 'newest');
if (!in_array($sortFilter, ['newest', 'price_asc', 'price_desc', 'title_az'], true)) {
    $sortFilter = 'newest';
}

$maxPriceRaw = trim((string)($_GET['max_price'] ?? ''));
$maxPriceFilter = null;
if ($maxPriceRaw !== '' && is_numeric($maxPriceRaw) && (float)$maxPriceRaw >= 0) {
    $maxPriceFilter = (float)$maxPriceRaw;
}

$activeMaxPrice = getActiveBookMaxPrice();
$sliderMaxPrice = max(1.0, ceil($activeMaxPrice), $maxPriceFilter !== null ? ceil($maxPriceFilter) : 0);
$sliderValue = $maxPriceFilter ?? $sliderMaxPrice;

$filters = [
    'category_id' => $categoryId,
    'search' => $searchTerm,
    'book_type' => $typeFilter,
    'max_price' => $maxPriceFilter,
    'condition' => $conditionForQuery,
    'availability' => $availabilityFilter,
    'sort' => $sortFilter,
];

$books = getBooks($filters, 24);
$totalResults = countBooks($filters);

$selectedCategory = null;
foreach ($categories as $category) {
    if ((int)$category['id'] === $categoryId) {
        $selectedCategory = $category;
        break;
    }
}

$typeOptions = [
    '' => t('all_types'),
    'official' => t('official_new'),
    'used' => t('used'),
];

$conditionOptions = [
    '' => t('all_conditions'),
    'new' => t('condition_new'),
    'like_new' => t('condition_like_new'),
    'good' => t('condition_good_used'),
    'damaged' => t('condition_damaged'),
];

$availabilityOptions = [
    '' => t('any_availability'),
    'in_stock' => t('in_stock'),
    'out_of_stock' => t('sold_out'),
];

$sortOptions = [
    'newest' => t('newest'),
    'price_asc' => t('price_low_high'),
    'price_desc' => t('price_high_low'),
    'title_az' => t('title_az'),
];

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><?= e(t('browse_books')) ?></h1>
        <p class="page-sub">
            <?= (int)$totalResults ?> <?= e(t('result_s_found')) ?>
            <?php if ($selectedCategory): ?>
                <?= e(t('in_category')) ?> <?= e($selectedCategory['name']) ?>
            <?php endif; ?>
            <?php if ($searchTerm !== ''): ?>
                <?= e(t('for_search')) ?> "<?= e($searchTerm) ?>"
            <?php endif; ?>
        </p>
    </div>

    <form action="<?= e(url('books.php')) ?>" method="get" class="book-filter-panel">
        <div class="book-filter-grid">
            <label class="form-group book-filter-field book-filter-field--wide">
                <span class="form-label"><?= e(t('search')) ?></span>
                <input type="search" name="search" class="form-input" value="<?= e($searchTerm) ?>" placeholder="<?= e(t('search_title_author')) ?>">
            </label>

            <input type="hidden" name="category" value="<?= (int)$categoryId ?>">

            <label class="form-group">
                <span class="form-label"><?= e(t('book_type')) ?></span>
                <select name="type" class="form-input form-select">
                    <?php foreach ($typeOptions as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $typeFilter === $value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="form-group">
                <span class="form-label"><?= e(t('condition')) ?></span>
                <select name="condition" class="form-input form-select">
                    <?php foreach ($conditionOptions as $value => $label): ?>
                        <?php $isSelected = $conditionFilter === $value || ($value === 'good' && $conditionFilter === 'used'); ?>
                        <option value="<?= e($value) ?>" <?= $isSelected ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="form-group">
                <span class="form-label"><?= e(t('availability')) ?></span>
                <select name="availability" class="form-input form-select">
                    <?php foreach ($availabilityOptions as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $availabilityFilter === $value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="form-group">
                <span class="form-label"><?= e(t('sort_by')) ?></span>
                <select name="sort" class="form-input form-select">
                    <?php foreach ($sortOptions as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $sortFilter === $value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="form-group book-filter-price">
                <div class="price-filter-head">
                    <label for="maxPrice" class="form-label"><?= e(t('max_price')) ?></label>
                    <span id="maxPriceOutput" class="price-range-value" data-price-output>
                        <?= e(t('up_to_price')) ?> <?= e(number_format((float)$sliderValue, 3)) ?>
                    </span>
                </div>
                <input
                    type="range"
                    id="maxPrice"
                    name="max_price"
                    min="0"
                    max="<?= e((string)$sliderMaxPrice) ?>"
                    step="0.500"
                    value="<?= e(number_format((float)$sliderValue, 3, '.', '')) ?>"
                    class="price-range"
                    data-price-slider
                    data-output="#maxPriceOutput"
                    data-currency="BD"
                    data-prefix="<?= e(t('up_to_price')) ?>"
                >
            </div>
        </div>

        <div class="book-filter-actions">
            <button type="submit" class="btn btn--primary"><?= e(t('apply_filters')) ?></button>
            <a href="<?= e(url('books.php')) ?>" class="btn btn--outline"><?= e(t('clear_filters')) ?></a>
        </div>
    </form>

    <div class="categories-grid categories-grid--browse">
        <?php foreach ($categories as $category): ?>
            <a href="<?= e(url('books.php?category=' . (int)$category['id'])) ?>"
               class="category-chip<?= (int)$category['id'] === $categoryId ? ' active' : '' ?>">
                <span class="category-chip__icon"><?= e($category['icon'] ?? '') ?></span>
                <span class="category-chip__name"><?= e($category['name']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($books)): ?>
        <div class="empty-state">
            <p><?= e(t('no_books_match_filters')) ?></p>
            <a href="<?= e(url('books.php')) ?>" class="btn btn--primary"><?= e(t('view_all_books')) ?></a>
        </div>
    <?php else: ?>
        <div class="books-grid">
            <?php foreach ($books as $book):
                $bookUrl = url('book.php?id=' . (int)$book['id']);
                $cover = bookCoverUrlFromBook($book);
            ?>
            <article class="book-card">
                <a href="<?= e($bookUrl) ?>" class="book-card__cover-link">
                    <img src="<?= e($cover) ?>" alt="<?= e($book['title']) ?>" class="book-card__cover" loading="lazy">
                    <span class="book-badge book-badge--<?= e($book['book_type']) ?>">
                        <?= e($book['book_type'] === 'official' ? t('official') : t('used')) ?>
                    </span>
                </a>
                <div class="book-card__body">
                    <a href="<?= e($bookUrl) ?>" class="book-card__title"><?= e($book['title']) ?></a>
                    <p class="book-card__author"><?= e($book['author']) ?></p>
                    <div class="book-card__footer">
                        <span class="book-card__price"><?= formatPrice((float)$book['price']) ?></span>
                        <a href="<?= e($bookUrl) ?>" class="btn btn--primary btn--sm"><?= e(t('view')) ?></a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
