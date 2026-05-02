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
    'name' => '', 'phone' => '', 'whatsapp' => '',
    'governorate' => '', 'city' => '', 'address' => '',
];

$governorates = [
    'Cairo', 'Alexandria', 'Giza', 'Dakahlia', 'Red Sea', 'Beheira', 'Fayoum', 'Gharbia', 
    'Ismailia', 'Menofia', 'Minya', 'Qalyubia', 'New Valley', 'Sharqia', 'Sohag', 
    'South Sinai', 'Suez', 'Kafr el-Sheikh', 'Matrouh', 'Qena', 'North Sinai', 
    'Beni Suef', 'Damietta', 'Luxor', 'Port Said', 'Asyut', 'Aswan'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Invalid request token.');
    }
    foreach ($values as $k => $_) {
        $values[$k] = trim((string)($_POST[$k] ?? ''));
    }

    if (mb_strlen($values['name']) < 2) $errors['name'] = 'Please enter your full name.';
    if (mb_strlen($values['phone']) < 8) $errors['phone'] = 'Please enter a valid phone number.';
    if (mb_strlen($values['whatsapp']) < 8) $errors['whatsapp'] = 'Please enter your WhatsApp number.';
    if (empty($values['governorate'])) $errors['governorate'] = 'Please select a governorate.';
    if (mb_strlen($values['city']) < 2) $errors['city'] = 'City is required.';
    if (mb_strlen($values['address']) < 5) $errors['address'] = 'Street address is required.';

    if (empty($errors)) {
        $orderId = 'ORD-' . strtoupper(bin2hex(random_bytes(3)));
        
        // Prepare email content
        $to = 'skibidipipidi2@gmail.com';
        $subject = "New Order: $orderId";
        
        $message = "New Order Received!\n\n";
        $message .= "Order ID: $orderId\n";
        $message .= "Customer: {$values['name']}\n";
        $message .= "Phone: {$values['phone']}\n";
        $message .= "WhatsApp: {$values['whatsapp']}\n\n";
        $message .= "Shipping Address:\n";
        $message .= "Governorate: {$values['governorate']}\n";
        $message .= "City: {$values['city']}\n";
        $message .= "Street: {$values['address']}\n\n";
        $message .= "Items Ordered:\n";
        
        foreach ($rows as $row) {
            $message .= "- {$row['product']['name']} x {$row['qty']} (" . money($row['lineTotal']) . ")\n";
        }
        
        $message .= "\nSubtotal: " . money($subtotal) . "\n";
        $message .= "Shipping: " . ($shipping == 0 ? 'Free' : money($shipping)) . "\n";
        $message .= "Total: " . money($total) . "\n";
        
        $headers = "From: noreply@curated.shop\r\n";
        $headers .= "Reply-To: {$to}\r\n";
        
        // Use PHPMailer via helper function
        send_order_email($to, $subject, $message);

        $orderItems = array_map(fn($r) => [
            'name' => $r['product']['name'],
            'qty' => $r['qty'],
            'price' => $r['product']['price'],
        ], $rows);

        $order = [
            'id' => $orderId,
            'date' => date('c'),
            'name' => $values['name'],
            'email' => '', // Email not collected in new form
            'items' => $orderItems,
            'total' => $total,
        ];

        // Save to database
        save_order($orderId, user_current(), $total, $orderItems, $values);

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

    <div class="form-section">
      <h3>Contact Information</h3>
      <div class="field">
        <label>Full Name</label>
        <input name="name" type="text" placeholder="e.g. Ahmed Ali" value="<?= e($values['name']) ?>" required />
        <span class="error"><?= e($errors['name'] ?? '') ?></span>
      </div>
      <div class="field-row">
        <div class="field">
          <label>Phone Number</label>
          <input name="phone" type="tel" placeholder="01xxxxxxxxx" value="<?= e($values['phone']) ?>" required />
          <span class="error"><?= e($errors['phone'] ?? '') ?></span>
        </div>
        <div class="field">
          <label>WhatsApp Number</label>
          <input name="whatsapp" type="tel" placeholder="01xxxxxxxxx" value="<?= e($values['whatsapp']) ?>" required />
          <span class="error"><?= e($errors['whatsapp'] ?? '') ?></span>
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3>Shipping Address</h3>
      <div class="field">
        <label>Governorate</label>
        <select name="governorate">
          <option value="">Select your Governorate</option>
          <?php foreach ($governorates as $gov): ?>
            <option value="<?= e($gov) ?>" <?= $values['governorate'] === $gov ? 'selected' : '' ?>><?= e($gov) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="error"><?= e($errors['governorate'] ?? '') ?></span>
      </div>
      <div class="field">
        <label>City</label>
        <input name="city" type="text" placeholder="City name" value="<?= e($values['city']) ?>" required />
        <span class="error"><?= e($errors['city'] ?? '') ?></span>
      </div>
      <div class="field">
        <label>Street Address</label>
        <input name="address" type="text" placeholder="Apartment, suite, unit, etc." value="<?= e($values['address']) ?>" required />
        <span class="error"><?= e($errors['address'] ?? '') ?></span>
      </div>
    </div>

    <div class="demo-note" style="margin-top: 24px; background: var(--accent-soft); border: 1px solid var(--accent); color: var(--accent);">
      <strong>Cash on Delivery:</strong> You will pay when the product reaches your doorstep.
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%; margin-top: 16px; padding: 16px;">
      Confirm Order — <?= money($total) ?>
    </button>
  </form>

  <aside class="summary">
    <h3>Order Summary</h3>
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

