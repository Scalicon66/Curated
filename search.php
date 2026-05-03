<?php
require_once __DIR__ . '/lib.php';

$query = trim($_GET['q'] ?? '');
$results = [];

if ($query) {
    $results = product_search($query);
}

$pageTitle = 'Search results for "' . e($query) . '" — Curated.';
require __DIR__ . '/header.php';
?>

<div class="search-results-header">
    <h2>Search Results</h2>
    <p><?= count($results) ?> objects found for "<strong><?= e($query) ?></strong>"</p>
</div>

<?php if (empty($results)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="64" height="64">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <h3>No results found</h3>
        <p>We couldn't find any objects matching your search. Try different keywords or browse our full collection.</p>
        <a href="index.php" class="btn btn-primary">Browse All Products</a>
    </div>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($results as $product): ?>
            <div class="product-card">
                <a href="product.php?id=<?= e($product['id']) ?>" class="product-image-link">
                    <img src="<?= e($product['image_url'] ?: 'https://via.placeholder.com/400x500') ?>" alt="<?= e($product['name']) ?>" class="product-image">
                </a>
                <div class="product-info">
                    <div class="product-meta">
                        <span class="product-category"><?= e($product['category']) ?></span>
                    </div>
                    <h3 class="product-title">
                        <a href="product.php?id=<?= e($product['id']) ?>"><?= e($product['name']) ?></a>
                    </h3>
                    <p class="product-short-desc"><?= e($product['short_description']) ?></p>
                    <div class="product-card-footer">
                        <span class="product-price"><?= money($product['price']) ?></span>
                        <form method="post" action="cart.php" style="margin: 0;">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= e($product['id']) ?>">
                            <button type="submit" class="btn-add-cart" aria-label="Add to cart">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
