<?php
require_once __DIR__ . '/lib.php';

$order = $_SESSION['last_order'] ?? null;
if (!$order) {
    redirect('index.php');
}

$pageTitle = 'Order confirmed — Curated.';
include __DIR__ . '/header.php';
?>

<div class="confirm">
  <div class="confirm-check">
    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
  </div>
  <h2>Thank you, <?= e(explode(' ', $order['name'])[0]) ?>.</h2>
  <p>Your order has been placed successfully! The product will get to you soon.</p>
  <span class="order-id"><?= e($order['id']) ?></span>

  <div style="max-width: 420px; margin: 0 auto; text-align: left;">
    <?php foreach ($order['items'] as $item): ?>
      <div class="summary-row">
        <span><?= e($item['name']) ?> × <?= (int)$item['qty'] ?></span>
        <span><?= money($item['price'] * $item['qty']) ?></span>
      </div>
    <?php endforeach; ?>
    <div class="summary-row total"><span>Total</span><span><?= money($order['total']) ?></span></div>
  </div>

  <a href="index.php" class="btn btn-primary" style="margin-top: 28px;">Continue shopping</a>
</div>

<?php include __DIR__ . '/footer.php'; ?>
