<?php
require_once __DIR__ . '/lib.php';

$rows = cart_detailed();
if (empty($rows)) {
    redirect('cart.php');
}

$subtotal = cart_subtotal();
$shipping = shipping_cost($subtotal);
$total = $subtotal + $shipping;

$errors = [];
$values = [
    'name' => '', 'email' => '', 'address' => '', 'city' => '',
    'zip' => '', 'card' => '', 'expiry' => '', 'cvc' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Invalid request token.');
    }
    foreach ($values as $k => $_) {
        $values[$k] = trim((string)($_POST[$k] ?? ''));
    }

    if (mb_strlen($values['name']) < 2) $errors['name'] = 'Please enter your name.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
    if (mb_strlen($values['address']) < 4) $errors['address'] = 'Address is required.';
    if (mb_strlen($values['city']) < 2) $errors['city'] = 'City is required.';
    if (mb_strlen($values['zip']) < 3) $errors['zip'] = 'Postal code is required.';
    $cardDigits = preg_replace('/\s+/', '', $values['card']);
    if (!preg_match('/^\d{13,19}$/', $cardDigits ?? '')) $errors['card'] = 'Enter a valid card number.';
    if (!preg_match('/^\d{2}\/\d{2}$/', $values['expiry'])) $errors['expiry'] = 'Use MM/YY format.';
    if (!preg_match('/^\d{3,4}$/', $values['cvc'])) $errors['cvc'] = 'CVC is 3–4 digits.';

    if (empty($errors)) {
        $order = [
            'id' => 'CRT-' . strtoupper(bin2hex(random_bytes(3))),
            'date' => date('c'),
            'name' => $values['name'],
            'email' => $values['email'],
            'items' => array_map(fn($r) => [
                'name' => $r['product']['name'],
                'qty' => $r['qty'],
                'price' => $r['product']['price'],
            ], $rows),
            'total' => $total,
        ];
        $_SESSION['last_order'] = $order;
        cart_clear();
        redirect('confirmation.php');
    }
}

$pageTitle = 'Checkout — Curated.';
include __DIR__ . '/header.php';
?>

<h1 class="page-title">Checkout</h1>

<div class="checkout-layout">
  <form class="form" method="post" action="checkout.php" novalidate>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
    <p class="demo-note">Demo checkout — no real charges.</p>

    <div class="form-section">
      <h3>Contact</h3>
      <div class="field">
        <label>Full name</label>
        <input name="name" type="text" value="<?= e($values['name']) ?>" required />
        <span class="error"><?= e($errors['name'] ?? '') ?></span>
      </div>
      <div class="field">
        <label>Email</label>
        <input name="email" type="email" value="<?= e($values['email']) ?>" required />
        <span class="error"><?= e($errors['email'] ?? '') ?></span>
      </div>
    </div>

    <div class="form-section">
      <h3>Shipping address</h3>
      <div class="field">
        <label>Street address</label>
        <input name="address" type="text" value="<?= e($values['address']) ?>" required />
        <span class="error"><?= e($errors['address'] ?? '') ?></span>
      </div>
      <div class="field-row">
        <div class="field">
          <label>City</label>
          <input name="city" type="text" value="<?= e($values['city']) ?>" required />
          <span class="error"><?= e($errors['city'] ?? '') ?></span>
        </div>
        <div class="field">
          <label>Postal code</label>
          <input name="zip" type="text" value="<?= e($values['zip']) ?>" required />
          <span class="error"><?= e($errors['zip'] ?? '') ?></span>
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3>Payment</h3>
      <div class="field">
        <label>Card number</label>
        <input name="card" type="text" inputmode="numeric" placeholder="4242 4242 4242 4242" value="<?= e($values['card']) ?>" required />
        <span class="error"><?= e($errors['card'] ?? '') ?></span>
      </div>
      <div class="field-row">
        <div class="field">
          <label>Expiry (MM/YY)</label>
          <input name="expiry" type="text" placeholder="12/29" value="<?= e($values['expiry']) ?>" required />
          <span class="error"><?= e($errors['expiry'] ?? '') ?></span>
        </div>
        <div class="field">
          <label>CVC</label>
          <input name="cvc" type="text" inputmode="numeric" placeholder="123" value="<?= e($values['cvc']) ?>" required />
          <span class="error"><?= e($errors['cvc'] ?? '') ?></span>
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%; margin-top: 8px;">
      Place order — <?= money($total) ?>
    </button>
  </form>

  <aside class="summary">
    <h3>Order summary</h3>
    <?php foreach ($rows as $row): ?>
      <div class="summary-row">
        <span><?= e($row['product']['name']) ?> × <?= (int)$row['qty'] ?></span>
        <span><?= money($row['lineTotal']) ?></span>
      </div>
    <?php endforeach; ?>
    <div class="summary-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
    <div class="summary-row"><span>Shipping</span><span><?= $shipping == 0 ? 'Free' : money($shipping) ?></span></div>
    <div class="summary-row total"><span>Total</span><span><?= money($total) ?></span></div>
  </aside>
</div>

<?php include __DIR__ . '/footer.php'; ?>
