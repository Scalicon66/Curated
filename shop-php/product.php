<?php
require_once __DIR__ . '/lib.php';

$id = (string)($_GET['id'] ?? '');
$product = find_product($id);

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Not found — Curated.';
    include __DIR__ . '/header.php';
    echo '<div class="empty"><h3>Product not found.</h3><p>It may have moved or sold out.</p><a href="index.php" class="btn btn-primary">Back to shop</a></div>';
    include __DIR__ . '/footer.php';
    exit;
}

$pageTitle = e($product['name']) . ' — Curated.';
include __DIR__ . '/header.php';
?>

<a href="index.php" class="back-link">
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
  Back to shop
</a>

<article class="detail">
  <div class="detail-image">
    <img src="<?= e($product['imageUrl']) ?>" alt="<?= e($product['name']) ?>" />
  </div>
  <div class="detail-info">
    <span class="card-category"><?= e($product['category']) ?></span>
    <h2><?= e($product['name']) ?></h2>
    <span class="price"><?= money($product['price']) ?></span>
    <p><?= e($product['longDescription']) ?></p>

    <form method="post" action="cart.php" class="detail-actions" id="addForm">
      <input type="hidden" name="action" value="add" />
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
      <input type="hidden" name="id" value="<?= e($product['id']) ?>" />

      <div class="qty" data-qty>
        <button type="button" data-qty-dec aria-label="Decrease quantity">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14"/></svg>
        </button>
        <span data-qty-display>1</span>
        <button type="button" data-qty-inc aria-label="Increase quantity">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
        </button>
        <input type="hidden" name="qty" value="1" data-qty-input />
      </div>

      <button type="submit" class="btn btn-primary" <?= $product['inStock'] ? '' : 'disabled' ?>>
        <?= $product['inStock'] ? 'Add to cart' : 'Sold out' ?>
      </button>
    </form>
  </div>
</article>

<?php include __DIR__ . '/footer.php'; ?>
