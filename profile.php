<?php
require_once __DIR__ . '/lib.php';

$loggedInEmail = user_current();
if (!$loggedInEmail) {
    redirect('login.php');
}

// Support viewing other profiles if the user is an admin
$viewEmail = $_GET['email'] ?? $loggedInEmail;
$currentUser = user_details($loggedInEmail);
$isAdmin = ($currentUser['type'] ?? '') === 'Admin';

if ($viewEmail !== $loggedInEmail && !$isAdmin) {
    flash_set('Access Denied. You do not have permission to view this profile.');
    redirect('profile.php');
}

// Handle actions (delete order, clear history)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash_set('Invalid security token.');
    } else {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'delete_order' && isset($_POST['order_id'])) {
                // Ensure only the owner or an admin can delete
                if (order_delete_user_order($viewEmail, $_POST['order_id'])) {
                    flash_set('Order ' . e($_POST['order_id']) . ' has been cancelled.');
                } else {
                    flash_set('Could not cancel order. It may no longer be in Pending status.');
                }
            } elseif ($_POST['action'] === 'clear_history') {
                if (order_delete_all_user_orders($viewEmail)) {
                    flash_set('Order history has been cleared.');
                }
            }
        }
    }
    redirect('profile.php' . ($viewEmail !== $loggedInEmail ? '?email=' . urlencode($viewEmail) : ''));
}

$user = user_details($viewEmail);
if (!$user) {
    if ($viewEmail === $loggedInEmail) {
        user_logout();
        redirect('login.php');
    } else {
        flash_set('User not found.');
        redirect('admin.php');
    }
}

$orders = get_user_orders($viewEmail);

$pageTitle = ($viewEmail === $loggedInEmail ? 'Your Profile' : e($user['username']) . "'s Profile") . ' — Curated.';
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
    <h2><?= $viewEmail === $loggedInEmail ? 'Welcome back, ' . e($user['username']) . '!' : e($user['username']) . "'s Profile" ?></h2>
    <p><?= $viewEmail === $loggedInEmail ? 'Manage your account details and view your recent activity.' : 'Viewing detailed information and history for this user.' ?></p>
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
      
      <?php if ($viewEmail === $loggedInEmail): ?>
        <div class="profile-actions" style="margin-top: 32px;">
          <a href="logout.php" class="btn btn-secondary" style="width: 100%;">Log Out</a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Recent Activity / Orders -->
    <div class="profile-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
        <h3 style="margin: 0; border: none; padding: 0;">Recent Orders</h3>
        <?php if (!empty($orders)): ?>
          <form method="post" onsubmit="return confirm('Are you sure you want to cancel all your pending orders?');">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
            <input type="hidden" name="action" value="clear_history" />
            <button type="submit" class="btn-ghost" style="color: var(--warn); font-size: 12px; font-weight: 600; padding: 4px 8px;">Cancel Pending Orders</button>
          </form>
        <?php endif; ?>
      </div>
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
                <th style="text-align: right;">Actions</th>
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
                  <td style="text-align: right;">
                    <?php if ($order['status'] === 'Pending'): ?>
                      <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                        <input type="hidden" name="action" value="delete_order" />
                        <input type="hidden" name="order_id" value="<?= e($order['id']) ?>" />
                        <button type="submit" class="btn-delete" title="Cancel Order">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        </button>
                      </form>
                    <?php else: ?>
                      <span style="font-size: 11px; color: var(--fg-muted); font-style: italic;">Locked</span>
                    <?php endif; ?>
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
