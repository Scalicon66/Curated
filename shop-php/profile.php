<?php
require_once __DIR__ . '/lib.php';

$email = user_current();
if (!$email) {
    redirect('login.php');
}

$user = user_details($email);
if (!$user) {
    user_logout();
    redirect('login.php');
}

$orders = get_user_orders($email);

$pageTitle = 'Profile — Curated.';
require __DIR__ . '/header.php';

// Generate initials for the avatar (e.g., "John Doe" -> "JD", or "johndoe" -> "J")
$nameParts = explode(' ', trim($user['username']));
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $initials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
}

// Format the date nicely
$memberSince = date('F j, Y', strtotime($user['created_at']));
?>

<div class="profile-layout container">
  <div class="profile-header">
    <div class="profile-avatar">
      <?= e($initials) ?>
    </div>
    <h2>Welcome back, <?= e($user['username']) ?>!</h2>
    <p>Manage your account details and view your recent activity.</p>
  </div>

  <div class="profile-grid">
    
    <!-- User Details Card -->
    <div class="profile-card">
      <h3>Account Details</h3>
      <div class="profile-info-grid">
        <div class="info-item">
          <span class="info-label">Email Address</span>
          <span class="info-value"><?= e($user['email']) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Age</span>
          <span class="info-value"><?= e((string)$user['age']) ?> years old</span>
        </div>
        <div class="info-item">
          <span class="info-label">Gender</span>
          <span class="info-value"><?= e(ucfirst($user['gender'])) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Account Type</span>
          <span class="info-value"><?= e($user['type']) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Member Since</span>
          <span class="info-value"><?= e($memberSince) ?></span>
        </div>
      </div>
      
      <div class="profile-actions" style="margin-top: 32px;">
        <a href="logout.php" class="btn btn-secondary" style="width: 100%;">Log Out</a>
      </div>
    </div>

    <!-- Recent Activity / Orders -->
    <div class="profile-card">
      <h3>Recent Orders</h3>
      <?php if (empty($orders)): ?>
        <div class="empty" style="box-shadow: none; margin: 0; padding: 40px 24px; background: transparent;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="3" y1="9" x2="21" y2="9"></line>
            <line x1="9" y1="21" x2="9" y2="9"></line>
          </svg>
          <h3 style="border: none; margin-bottom: 8px;">No orders yet</h3>
          <p>You haven't placed any orders. Discover our curated collection.</p>
          <a href="index.php" class="btn btn-primary" style="display: inline-flex;">Start Shopping</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="admin-table" style="font-size: 13px;">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Items</th>
                <th>Total</th>
                <th>Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
                <tr>
                  <td><strong><?= e($order['id']) ?></strong></td>
                  <td>
                    <?php foreach ($order['items'] as $item): ?>
                      <div style="font-size: 11px; white-space: nowrap;">
                        <?= e($item['product_name']) ?> (<?= (int)$item['quantity'] ?>)
                      </div>
                    <?php endforeach; ?>
                  </td>
                  <td><?= money($order['total_amount']) ?></td>
                  <td style="white-space: nowrap;"><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                  <td>
                    <span class="badge" style="background: var(--accent-soft); color: var(--accent);">
                      <?= e($order['status']) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
