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
      
      <div class="profile-actions">
        <a href="logout.php" class="btn btn-secondary">Log Out</a>
      </div>
    </div>

    <!-- Recent Activity / Empty State -->
    <div class="profile-card">
      <h3>Recent Orders</h3>
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
    </div>

  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
