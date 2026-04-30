<?php
/**
 * public/books.php
 * Full book listing with search and filters.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Browse Books';
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
    '' => 'All types',
    'official' => 'Official / New',
    'used' => 'Used',
];

$conditionOptions = [
    '' => 'All conditions',
    'new' => 'New',
    'like_new' => 'Like New',
    'good' => 'Good / Used',
    'damaged' => 'Damaged',
];

$availabilityOptions = [
    '' => 'Any availability',
    'in_stock' => 'In Stock',
    'out_of_stock' => 'Sold Out',
];

$sortOptions = [
    'newest' => 'Newest',
    'price_asc' => 'Price Low to High',
    'price_desc' => 'Price High to Low',
    'title_az' => 'Title A-Z',
];

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Browse Books</h1>
        <p class="page-sub">
            <?= (int)$totalResults ?> result(s) found
            <?php if ($selectedCategory): ?>
                in <?= e($selectedCategory['name']) ?>
            <?php endif; ?>
            <?php if ($searchTerm !== ''): ?>
                for "<?= e($searchTerm) ?>"
            <?php endif; ?>
        </p>
    </div>

    <form action="<?= e(url('books.php')) ?>" method="get" class="book-filter-panel">
        <div class="book-filter-grid">
            <label class="form-group book-filter-field book-filter-field--wide">
                <span class="form-label">Search</span>
                <input type="search" name="search" class="form-input" value="<?= e($searchTerm) ?>" placeholder="Title or author">
            </label>

            <input type="hidden" name="category" value="<?= (int)$categoryId ?>">

            <label class="form-group">
                <span class="form-label">Book Type</span>
                <select name="type" class="form-input form-select">
                    <?php foreach ($typeOptions as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $typeFilter === $value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="form-group">
                <span class="form-label">Condition</span>
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
                <span class="form-label">Availability</span>
                <select name="availability" class="form-input form-select">
                    <?php foreach ($availabilityOptions as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $availabilityFilter === $value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="form-group">
                <span class="form-label">Sort By</span>
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
                    <label for="maxPrice" class="form-label">Max Price</label>
                    <span id="maxPriceOutput" class="price-range-value" data-price-output>
                        Up to BD <?= e(number_format((float)$sliderValue, 3)) ?>
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
                >
            </div>
        </div>

        <div class="book-filter-actions">
            <button type="submit" class="btn btn--primary">Apply Filters</button>
            <a href="<?= e(url('books.php')) ?>" class="btn btn--outline">Clear Filters</a>
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
            <p>No books match your filters.</p>
            <a href="<?= e(url('books.php')) ?>" class="btn btn--primary">View All Books</a>
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
                        <?= $book['book_type'] === 'official' ? 'Official' : 'Used' ?>
                    </span>
                </a>
                <div class="book-card__body">
                    <a href="<?= e($bookUrl) ?>" class="book-card__title"><?= e($book['title']) ?></a>
                    <p class="book-card__author"><?= e($book['author']) ?></p>
                    <div class="book-card__footer">
                        <span class="book-card__price"><?= formatPrice((float)$book['price']) ?></span>
                        <a href="<?= e($bookUrl) ?>" class="btn btn--primary btn--sm">View</a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
