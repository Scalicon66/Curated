<?php
require_once __DIR__ . '/lib.php';

$query = trim((string)($_GET['q'] ?? ''));
$category = (string)($_GET['cat'] ?? 'All');

$visible = array_filter($PRODUCTS, function ($p) use ($query, $category) {
    $matchesCat = ($category === 'All' || $p['category'] === $category);
    if (!$matchesCat) return false;
    if ($query === '') return true;
    $q = mb_strtolower($query);
    return str_contains(mb_strtolower($p['name']), $q)
        || str_contains(mb_strtolower($p['shortDescription']), $q)
        || str_contains(mb_strtolower($p['category']), $q);
});

$pageTitle = 'Curated. — Essential Objects';
include __DIR__ . '/header.php';
?>

<section class="hero">
  <h1>Essential Objects.</h1>
  <p>Thoughtfully designed, carefully selected tools for your daily rituals.</p>
</section>

<form class="toolbar" method="get" action="index.php" id="filterForm">
  <div class="search">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="search" id="searchInput" name="q" placeholder="Search products..." value="<?= e($query) ?>" autocomplete="off" />
  </div>
  <input type="hidden" name="cat" value="<?= e($category) ?>" id="catInput" />
  <div class="filters">
    <?php foreach (array_merge(['All'], all_categories()) as $cat): ?>
      <a class="chip<?= $cat === $category ? ' active' : '' ?>"
         href="index.php?<?= http_build_query(['cat' => $cat, 'q' => $query]) ?>">
        <?= e($cat) ?>
      </a>
    <?php endforeach; ?>
  </div>
  <noscript><button type="submit" class="btn btn-secondary">Search</button></noscript>
</form>

<div class="grid" id="grid">
  <?php $i = 0; $cart = cart_items(); foreach ($visible as $p): ?>
    <?php $qtyInCart = $cart[$p['id']] ?? 0; ?>
    <a href="product.php?id=<?= e($p['id']) ?>"
       class="card"
       style="animation-delay: <?= $i * 50 ?>ms"
       data-name="<?= e(mb_strtolower($p['name'])) ?>"
       data-desc="<?= e(mb_strtolower($p['shortDescription'])) ?>"
       data-cat="<?= e($p['category']) ?>">
      <div class="card-image">
        <img src="<?= e($p['imageUrl']) ?>" alt="<?= e($p['name']) ?>" loading="lazy" />
      </div>
      <div class="card-body">
        <span class="card-category"><?= e($p['category']) ?></span>
        <h3 class="card-name"><?= e($p['name']) ?></h3>
        <p class="card-desc"><?= e($p['shortDescription']) ?></p>
        <div class="card-bottom">
          <span class="price"><?= money($p['price']) ?></span>
          <?php if (!$p['inStock']): ?>
            <span class="out-of-stock">Sold out</span>
          <?php else: ?>
            <span class="stock-count">
              <?= (int)($p['stock'] ?? 0) ?> in stock
            </span>
          <?php endif; ?>
          <?php if ($qtyInCart > 0): ?>
            <span class="in-cart-badge"><?= $qtyInCart ?> in cart</span>
          <?php endif; ?>
        </div>
      </div>
    </a>
  <?php $i++; endforeach; ?>
  <?php if (empty($visible)): ?>
    <div class="empty" style="grid-column: 1 / -1;">
      <h3>Nothing matches that.</h3>
      <p>Try a different search term or category.</p>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
