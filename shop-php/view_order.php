<?php
require_once __DIR__ . '/lib.php';

$email = user_current();
if (!$email) {
    redirect('login.php');
}

$user = user_details($email);
if (!$user || $user['type'] !== 'Admin') {
    flash_set('Access Denied.');
    redirect('index.php');
}

$orderId = $_GET['id'] ?? '';
$order = get_order_details($orderId);

if (!$order) {
    flash_set('Order not found.');
    redirect('admin.php');
}

$pageTitle = 'Order Details — ' . $orderId;
require __DIR__ . '/header.php';
?>

<div class="admin-layout container">
  <a href="admin.php" class="back-link">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Admin Panel
  </a>

  <div class="admin-header" style="margin-top: 24px;">
    <div class="admin-title">
      <h2>Order #<?= e($order['id']) ?></h2>
      <p>Placed on <?= date('F j, Y \a\t g:i a', strtotime($order['created_at'])) ?></p>
    </div>
    <div>
        <?php 
            $statusColor = 'var(--accent-soft)'; 
            $textColor = 'var(--accent)';
            if ($order['status'] === 'Delivered') { $statusColor = '#dcfce7'; $textColor = '#166534'; }
            if ($order['status'] === 'Cancelled') { $statusColor = '#fce8e6'; $textColor = 'var(--warn)'; }
            if ($order['status'] === 'Processing') { $statusColor = '#e0f2fe'; $textColor = '#0369a1'; }
        ?>
        <span class="badge" style="background: <?= $statusColor ?>; color: <?= $textColor ?>; padding: 8px 16px; font-size: 14px;">
            <?= e($order['status']) ?>
        </span>
    </div>
  </div>

  <div class="profile-grid">
    
    <!-- Customer Details -->
    <div class="profile-card">
      <h3>Customer Information</h3>
      <div class="profile-info-grid">
        <div class="info-item">
          <span class="info-label">Full Name</span>
          <span class="info-value"><?= e($order['full_name']) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Email (Account)</span>
          <span class="info-value"><?= e($order['user_email'] ?: 'Guest') ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Phone Number</span>
          <span class="info-value"><?= e($order['phone']) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">WhatsApp</span>
          <span class="info-value"><?= e($order['whatsapp']) ?></span>
        </div>
      </div>
    </div>

    <!-- Shipping Address -->
    <div class="profile-card">
      <h3>Shipping Address</h3>
      <div class="profile-info-grid">
        <div class="info-item">
          <span class="info-label">Governorate</span>
          <span class="info-value"><?= e($order['governorate']) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">City</span>
          <span class="info-value"><?= e($order['city']) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Street Address</span>
          <span class="info-value"><?= e($order['address']) ?></span>
        </div>
      </div>
    </div>

  </div>

  <!-- Order Items -->
  <div class="admin-card" style="margin-top: 32px;">
    <div class="card-header">
      <h3>Items Ordered</h3>
    </div>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Product Name</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Line Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($order['items'] as $item): ?>
          <tr>
            <td><strong><?= e($item['product_name']) ?></strong></td>
            <td><?= (int)$item['quantity'] ?></td>
            <td><?= money($item['price']) ?></td>
            <td><?= money($item['price'] * $item['quantity']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: var(--bg);">
                <td colspan="3" style="text-align: right; font-weight: 600; padding: 16px 24px;">Grand Total:</td>
                <td style="font-weight: 700; font-size: 18px; color: var(--accent); padding: 16px 24px;">
                    <?= money($order['total_amount']) ?>
                </td>
            </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <!-- Status Management -->
  <div class="admin-card" style="margin-top: 32px; background: var(--accent-soft);">
    <div style="padding: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h4 style="margin: 0 0 4px;">Update Order Status</h4>
            <p style="margin: 0; font-size: 13px; color: var(--fg-muted);">Change the progress of this order.</p>
        </div>
        <form method="post" action="admin.php" style="display: flex; gap: 8px;">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
            <input type="hidden" name="action" value="update_order_status" />
            <input type="hidden" name="order_id" value="<?= e($order['id']) ?>" />
            <select name="status" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); font: inherit;">
                <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Processing" <?= $order['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                <option value="Delivered" <?= $order['status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="Cancelled" <?= $order['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <button type="submit" class="btn btn-primary">Update Status</button>
        </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
