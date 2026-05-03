<?php
require_once __DIR__ . '/lib.php';

// Handle POST actions (server-side cart mutation), then PRG redirect.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Invalid request token.');
    }
    $action = (string)($_POST['action'] ?? '');
    $id = (string)($_POST['id'] ?? '');
    $qty = (int)($_POST['qty'] ?? 1);

    switch ($action) {
        case 'add':
            cart_add($id, max(1, $qty));
            $product = find_product($id);
            if ($product) {
                flash_set('Added ' . $qty . ' × ' . $product['name']);
            }
            redirect('cart.php');
        case 'update':
            cart_update($id, max(0, $qty));
            redirect('cart.php');
        case 'remove':
            cart_remove($id);
            redirect('cart.php');
        case 'clear':
            cart_clear();
            redirect('cart.php');
        default:
            redirect('cart.php');
    }
}

$rows = cart_detailed();
$subtotal = cart_subtotal();
$shipping = shipping_cost($subtotal);
$total = $subtotal + $shipping;

$pageTitle = 'Your cart — Curated.';
include __DIR__ . '/header.php';
?>

<h1 class="page-title">Your cart</h1>

<?php if (empty($rows)): ?>
  <div class="empty">
    <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
    <h3>Your cart is empty.</h3>
    <p>Start by adding something you love.</p>
    <a href="index.php" class="btn btn-primary">Browse the shop</a>
  </div>
<?php else: ?>
  <div class="cart-layout">
    <div class="cart-items">
      <?php foreach ($rows as $row): $p = $row['product']; ?>
        <div class="line-item">
          <a href="product.php?id=<?= e($p['id']) ?>" class="line-item-image">
            <img src="<?= e($p['imageUrl']) ?>" alt="<?= e($p['name']) ?>" />
          </a>
          <div class="line-item-info">
            <h4><?= e($p['name']) ?></h4>
            <span class="price"><?= money($p['price']) ?></span>
            <div class="meta">
              <form method="post" action="cart.php" class="qty-form">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="id" value="<?= e($p['id']) ?>" />
                <div class="qty">
                  <button type="submit" name="qty" value="<?= (int)$row['qty'] - 1 ?>" aria-label="Decrease">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14"/></svg>
                  </button>
                  <span><?= (int)$row['qty'] ?></span>
                  <button type="submit" name="qty" value="<?= (int)$row['qty'] + 1 ?>" aria-label="Increase">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                  </button>
                </div>
              </form>
              <form method="post" action="cart.php" style="display:inline;">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="action" value="remove" />
                <input type="hidden" name="id" value="<?= e($p['id']) ?>" />
                <button type="submit" class="remove">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  Remove
                </button>
              </form>
            </div>
          </div>
          <div class="line-item-total"><?= money($row['lineTotal']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <aside class="summary">
      <h3>Order summary</h3>
      <div class="summary-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
      <div class="summary-row"><span>Shipping</span><span><?= $shipping == 0 ? 'Free' : money($shipping) ?></span></div>
      <div class="summary-row total"><span>Total</span><span><?= money($total) ?></span></div>
      <a href="checkout.php" class="btn btn-primary">
        Checkout
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </a>
    </aside>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
