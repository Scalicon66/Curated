<?php
require_once __DIR__ . '/lib.php';

$email = user_current();
if (!$email) {
    redirect('login.php');
}

$user = user_details($email);
if (!$user || $user['type'] !== 'Admin') {
    flash_set('Access Denied. You do not have permission to view this page.');
    redirect('index.php');
}

$error = '';
$success = '';

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // --- ADD PRODUCT ---
        if ($_POST['action'] === 'add_product') {
            $pName     = trim($_POST['product_name'] ?? '');
            $pPrice    = (float)($_POST['product_price'] ?? 0);
            $pCategory = trim($_POST['product_category'] ?? '');
            $pShort    = trim($_POST['product_short'] ?? '');
            $pLong     = trim($_POST['product_long'] ?? '');
            $pStock    = (int)($_POST['product_stock'] ?? 0);

            if (!$pName || $pPrice <= 0 || !$pCategory || !$pShort || !$pLong || $pStock < 0) {
                $error = 'Please fill out all product fields correctly.';
            } else {
                // Handle image upload
                $imageUrl = '';
                if (isset($_FILES['product_image'])) {
                    $fileError = $_FILES['product_image']['error'];
                    
                    if ($fileError === UPLOAD_ERR_OK) {
                        $uploadDir = __DIR__ . '/images/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (!in_array($ext, $allowed)) {
                            $error = 'Invalid image format. Use JPG, PNG, GIF, or WEBP.';
                        } else {
                            $fileName = 'product-' . bin2hex(random_bytes(8)) . '.' . $ext;
                            $destPath = $uploadDir . $fileName;
                            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $destPath)) {
                                $imageUrl = 'images/' . $fileName;
                            } else {
                                $error = 'Failed to save the uploaded image to the server. Check directory permissions.';
                            }
                        }
                    } elseif ($fileError === UPLOAD_ERR_NO_FILE) {
                        $error = 'Please select a product image to upload.';
                    } elseif ($fileError === UPLOAD_ERR_INI_SIZE || $fileError === UPLOAD_ERR_FORM_SIZE) {
                        $error = 'The uploaded image is too large.';
                    } else {
                        $error = 'Image upload failed with error code: ' . $fileError;
                    }
                } else {
                    $error = 'No image data received.';
                }

                if (!$error) {
                    product_add($pName, $pPrice, $pCategory, $pShort, $pLong, $imageUrl, $pStock);
                    flash_set('Product "' . $pName . '" has been added successfully!');
                    redirect('admin.php');
                }
            }
        }

        // --- DELETE PRODUCT ---
        if ($_POST['action'] === 'delete_product') {
            $deleteId = $_POST['product_id'] ?? '';
            if ($deleteId) {
                product_delete($deleteId);
                flash_set('Product has been removed.');
                redirect('admin.php');
            }
        }

        // --- UPDATE USER ROLE ---
        if ($_POST['action'] === 'update_user_role') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $newRole = $_POST['new_role'] ?? '';
            if ($userId && in_array($newRole, ['Admin', 'User'])) {
                $currentUser = user_details(user_current());
                if ($currentUser && (int)$currentUser['id'] === $userId) {
                     $error = 'You cannot change your own role.';
                } else {
                    user_update_role($userId, $newRole);
                    flash_set('User role updated successfully.');
                    redirect('admin.php');
                }
            }
        }

        // --- DELETE USER ---
        if ($_POST['action'] === 'delete_user') {
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId) {
                $currentUser = user_details(user_current());
                if ($currentUser && (int)$currentUser['id'] === $userId) {
                     $error = 'You cannot delete your own account.';
                } else {
                    user_delete($userId);
                    flash_set('User has been removed.');
                    redirect('admin.php');
                }
            }
        }

        // --- UPDATE ORDER STATUS ---
        if ($_POST['action'] === 'update_order_status') {
            $orderId = $_POST['order_id'] ?? '';
            $newStatus = $_POST['status'] ?? '';
            if ($orderId && in_array($newStatus, ['Pending', 'Processing', 'Delivered', 'Cancelled'])) {
                order_update_status($orderId, $newStatus);
                flash_set('Order status updated to ' . $newStatus);
                redirect('admin.php');
            }
        }

        // --- DELETE ORDER ---
        if ($_POST['action'] === 'delete_order') {
            $orderId = $_POST['order_id'] ?? '';
            if ($orderId) {
                order_delete($orderId);
                flash_set('Order #' . $orderId . ' has been deleted.');
                redirect('admin.php');
            }
        }

        // --- DELETE ALL ORDERS ---
        if ($_POST['action'] === 'delete_all_orders') {
            order_delete_all();
            flash_set('All orders have been cleared.');
            redirect('admin.php');
        }
        // --- SEND NOTIFICATION ---
        if ($_POST['action'] === 'send_notification') {
            $nTitle   = trim($_POST['notif_title'] ?? 'Announcement');
            $nMessage = trim($_POST['notif_message'] ?? '');
            
            if (!$nMessage) {
                $error = 'Notification message cannot be empty.';
            } else {
                $imageUrl = null;
                if (isset($_FILES['notif_image']) && $_FILES['notif_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/images/';
                    $ext = strtolower(pathinfo($_FILES['notif_image']['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($ext, $allowed)) {
                        $fileName = 'notif-' . bin2hex(random_bytes(8)) . '.' . $ext;
                        $destPath = $uploadDir . $fileName;
                        if (move_uploaded_file($_FILES['notif_image']['tmp_name'], $destPath)) {
                            $imageUrl = 'images/' . $fileName;
                        }
                    }
                }
                
                // Send to all users (null userEmail means system-wide in this context)
                notify_add(null, $nTitle, $nMessage, null, $imageUrl);
                flash_set('Notification sent to all users!');
                redirect('admin.php');
            }
        }
    }
}

// Reload data
$allUsers = get_all_users();
$allOrders = get_all_orders();
$monthlySales = get_monthly_sales();
$totalUsers = count($allUsers);
$totalOrders = count($allOrders);
$pendingOrders = array_filter($allOrders, fn($o) => $o['status'] === 'Pending');
$totalProducts = count($PRODUCTS);
$lowStockProducts = array_filter($PRODUCTS, fn($p) => ($p['stock'] ?? 0) <= 10);

$pageTitle = 'Admin Dashboard — Curated.';
require __DIR__ . '/header.php';
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="admin-layout container">
  <div class="admin-header">
    <div class="admin-title">
      <h2>Admin Dashboard</h2>
    </div>
    <div class="admin-stats">
      <div class="stat-card">
        <span class="stat-value"><?= e((string)$totalOrders) ?></span>
        <span class="stat-label">Total Orders</span>
      </div>
      <div class="stat-card">
        <span class="stat-value" style="color: var(--accent);"><?= e((string)count($pendingOrders)) ?></span>
        <span class="stat-label">Pending</span>
      </div>
      <div class="stat-card">
        <span class="stat-value"><?= e((string)$totalProducts) ?></span>
        <span class="stat-label">Products</span>
      </div>
      <div class="stat-card">
        <span class="stat-value" style="color: var(--warn);"><?= e((string)count($lowStockProducts)) ?></span>
        <span class="stat-label">Low Stock</span>
      </div>
    </div>
  </div>

  <!-- Sales Analytics Chart -->
  <div class="admin-card" style="margin-bottom: 32px;">
    <div class="card-header">
      <h3>Revenue Overview</h3>
    </div>
    <div style="padding: 24px;">
      <?php if (empty($monthlySales)): ?>
        <p style="text-align: center; color: var(--fg-muted); padding: 40px 0;">No sales data available yet for analytics.</p>
      <?php else: ?>
        <canvas id="salesChart" style="max-height: 280px; width: 100%;"></canvas>
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('salesChart').getContext('2d');
            
            new Chart(ctx, {
              type: 'bar',
              data: {
                labels: <?= json_encode(array_map(fn($s) => date('M Y', strtotime($s['month'] . '-01')), $monthlySales)) ?>,
                datasets: [{
                  label: 'Revenue ($)',
                  data: <?= json_encode(array_map(fn($s) => (float)$s['total'], $monthlySales)) ?>,
                  backgroundColor: 'rgba(0, 0, 0, 0.05)',
                  borderColor: '#000000',
                  borderWidth: 1.5,
                  borderRadius: 8, // Modern rounded bars
                  hoverBackgroundColor: '#000000',
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: { display: false },
                  tooltip: {
                    backgroundColor: '#111',
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                      label: function(context) {
                        return 'Revenue: $' + context.parsed.y.toLocaleString();
                      }
                    }
                  }
                },
                scales: {
                  y: {
                    beginAtZero: true,
                    grid: { color: '#f0f0f0', drawBorder: false },
                    ticks: { font: { size: 11, family: 'Inter' } }
                  },
                  x: {
                    grid: { display: false },
                    ticks: { font: { size: 11, family: 'Inter' } }
                  }
                }
              }
            });
          });
        </script>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Orders Table -->
  <div class="admin-card" style="margin-bottom: 32px;">
    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
      <h3>Recent Orders</h3>
      <?php if (!empty($allOrders)): ?>
        <form method="post" action="admin.php" onsubmit="return confirm('EXTREME CAUTION: Are you sure you want to delete ALL orders? This cannot be undone.');">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
          <input type="hidden" name="action" value="delete_all_orders" />
          <button type="submit" class="btn btn-secondary" style="color: var(--warn); font-size: 11px; padding: 6px 12px;">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="margin-right: 4px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Delete All Orders
          </button>
        </form>
      <?php endif; ?>
    </div>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Items</th>
            <th>Total</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allOrders as $order): ?>
          <tr>
            <td><a href="view_order.php?id=<?= e($order['id']) ?>" style="color: var(--accent); text-decoration: underline;"><strong><?= e($order['id']) ?></strong></a></td>
            <td><?= e($order['user_email'] ?: 'Guest') ?></td>
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
              <?php 
                $statusColor = 'var(--accent-soft)'; 
                $textColor = 'var(--accent)';
                if ($order['status'] === 'Delivered') { $statusColor = '#dcfce7'; $textColor = '#166534'; }
                if ($order['status'] === 'Cancelled') { $statusColor = '#fce8e6'; $textColor = 'var(--warn)'; }
                if ($order['status'] === 'Processing') { $statusColor = '#e0f2fe'; $textColor = '#0369a1'; }
              ?>
              <span class="badge" style="background: <?= $statusColor ?>; color: <?= $textColor ?>;">
                <?= e($order['status']) ?>
              </span>
            </td>
            <td style="white-space: nowrap;">
              <form method="post" action="admin.php" style="display: inline-flex; gap: 4px; align-items: center;">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="action" value="update_order_status" />
                <input type="hidden" name="order_id" value="<?= e($order['id']) ?>" />
                <select name="status" style="font-size: 11px; padding: 4px; border-radius: 4px; border: 1px solid var(--border);">
                  <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="Processing" <?= $order['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                  <option value="Delivered" <?= $order['status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                  <option value="Cancelled" <?= $order['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-primary" style="padding: 4px 8px; font-size: 11px;">Update</button>
              </form>
              
              <form method="post" action="admin.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this order?');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="action" value="delete_order" />
                <input type="hidden" name="order_id" value="<?= e($order['id']) ?>" />
                <button type="submit" class="btn-delete" title="Delete order" style="margin-left: 8px;">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($allOrders)): ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 32px; color: var(--fg-muted);">No orders yet.</td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add Product Form -->
  <div class="admin-card" style="margin-bottom: 32px;">
    <div class="card-header">
      <h3>Add New Product</h3>
    </div>
    <div style="padding: 24px;">
      <?php if ($error): ?>
        <div class="demo-note" style="background: var(--warn); color: #fff; text-align: center; margin-bottom: 20px; padding: 12px; border-radius: 8px;">
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" action="admin.php" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
        <input type="hidden" name="action" value="add_product" />

        <div class="admin-form-grid">
          <div class="field">
            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" name="product_name" required placeholder="e.g. Bamboo Desk Organizer" />
          </div>

          <div class="field-row">
            <div class="field">
              <label for="product_price">Price ($)</label>
              <input type="number" id="product_price" name="product_price" required min="0.01" step="0.01" placeholder="49.99" />
            </div>
            <div class="field">
              <label for="product_stock">Stock Quantity</label>
              <input type="number" id="product_stock" name="product_stock" required min="0" placeholder="25" />
            </div>
          </div>

          <div class="field">
            <label for="product_category">Category</label>
            <input type="text" id="product_category" name="product_category" required placeholder="e.g. Workspace, Tech, Home, Accessories" />
          </div>

          <div class="field">
            <label for="product_image">Product Image</label>
            <input type="file" id="product_image" name="product_image" accept="image/*" required
                   style="padding: 10px; border: 1px dashed var(--border); border-radius: 8px; background: var(--bg); width: 100%;" />
          </div>

          <div class="field">
            <label for="product_short">Short Description</label>
            <input type="text" id="product_short" name="product_short" required placeholder="A brief tagline for the product card" />
          </div>

          <div class="field">
            <label for="product_long">Long Description</label>
            <textarea id="product_long" name="product_long" required rows="4" placeholder="Full product description for the detail page..."
                      style="width: 100%; padding: 12px 14px; border: 1px solid var(--border); border-radius: 8px; font: inherit; background: var(--bg); resize: vertical;"></textarea>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 16px;">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Product
        </button>
      </form>
    </div>
  </div>

  <!-- Send Notification Form -->
  <div class="admin-card" style="margin-bottom: 32px;">
    <div class="card-header">
      <h3>Send System Notification</h3>
    </div>
    <div style="padding: 24px;">
      <p style="font-size: 13px; color: var(--fg-muted); margin-bottom: 20px;">
        This will send a notification to all users. You can include an optional image.
      </p>
      <form method="post" action="admin.php" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
        <input type="hidden" name="action" value="send_notification" />

        <div class="admin-form-grid">
          <div class="field">
            <label for="notif_title">Notification Title</label>
            <input type="text" id="notif_title" name="notif_title" required placeholder="e.g. New Collection Available!" />
          </div>

          <div class="field">
            <label for="notif_image">Notification Image (Optional)</label>
            <input type="file" id="notif_image" name="notif_image" accept="image/*"
                   style="padding: 10px; border: 1px dashed var(--border); border-radius: 8px; background: var(--bg); width: 100%;" />
          </div>

          <div class="field" style="grid-column: 1 / -1;">
            <label for="notif_message">Message Content</label>
            <textarea id="notif_message" name="notif_message" required rows="3" placeholder="Write your notification message here..."
                      style="width: 100%; padding: 12px 14px; border: 1px solid var(--border); border-radius: 8px; font: inherit; background: var(--bg); resize: vertical;"></textarea>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 16px; background: var(--accent);">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="margin-right: 8px;"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
          Send Notification
        </button>
      </form>
    </div>
  </div>

  <!-- Product Inventory Table -->
  <div class="admin-card" style="margin-bottom: 32px;">
    <div class="card-header">
      <h3>Product Inventory</h3>
    </div>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Image</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($PRODUCTS as $p): ?>
          <tr>
            <td>
              <img src="<?= e($p['imageUrl']) ?>" alt="<?= e($p['name']) ?>"
                   style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px; background: var(--accent-soft);" />
            </td>
            <td><strong><?= e($p['name']) ?></strong></td>
            <td><?= e($p['category']) ?></td>
            <td><?= money($p['price']) ?></td>
            <td>
              <?php $stock = (int)($p['stock'] ?? 0); ?>
              <span style="font-weight: 600; color: <?= $stock <= 5 ? 'var(--warn)' : ($stock <= 10 ? '#b07a1a' : 'var(--accent)') ?>">
                <?= $stock ?>
              </span>
            </td>
            <td>
              <?php if (!$p['inStock'] || $stock === 0): ?>
                <span class="badge" style="background:#fce8e6; color:var(--warn);">Out of Stock</span>
              <?php elseif ($stock <= 10): ?>
                <span class="badge" style="background:#fef3cd; color:#856404;">Low Stock</span>
              <?php else: ?>
                <span class="badge" style="background:#dcfce7; color:#166534;">In Stock</span>
              <?php endif; ?>
            </td>
            <td style="white-space: nowrap;">
              <a href="edit_product.php?id=<?= e($p['id']) ?>" class="btn-edit" title="Edit product">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </a>
              <form method="post" action="admin.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="action" value="delete_product" />
                <input type="hidden" name="product_id" value="<?= e($p['id']) ?>" />
                <button type="submit" class="btn-delete" title="Delete product">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                  </svg>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($PRODUCTS)): ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 32px; color: var(--fg-muted);">
              No products found. Add your first product above!
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Registered Users Table -->
  <div class="admin-card">
    <div class="card-header">
      <h3>Registered Users</h3>
    </div>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Email</th>
            <th>Age / Gender</th>
            <th>Role</th>
            <th>Joined</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allUsers as $u): ?>
          <tr>
            <td>#<?= e((string)$u['id']) ?></td>
            <td>
              <div class="td-user">
                <a href="profile.php?email=<?= urlencode($u['email']) ?>" style="color: var(--accent); text-decoration: none; font-weight: 600;">
                  <?= e($u['username']) ?>
                </a>
              </div>
            </td>
            <td><?= e($u['email']) ?></td>
            <td><?= e((string)$u['age']) ?> / <?= e(ucfirst($u['gender'])) ?></td>
            <td>
              <span class="badge badge-<?= strtolower($u['type']) ?>">
                <?= e($u['type']) ?>
              </span>
            </td>
            <td><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
            <td style="white-space: nowrap;">
              <form method="post" action="admin.php" style="display:inline;">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="user_id" value="<?= e((string)$u['id']) ?>" />
                <input type="hidden" name="action" value="update_user_role" />
                <?php if ($u['type'] === 'Admin'): ?>
                    <input type="hidden" name="new_role" value="User" />
                    <button type="submit" class="btn btn-secondary btn-sm" style="margin-right: 4px;">Demote</button>
                <?php else: ?>
                    <input type="hidden" name="new_role" value="Admin" />
                    <button type="submit" class="btn btn-primary btn-sm" style="margin-right: 4px;">Promote</button>
                <?php endif; ?>
              </form>
              <form method="post" action="admin.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="user_id" value="<?= e((string)$u['id']) ?>" />
                <input type="hidden" name="action" value="delete_user" />
                <button type="submit" class="btn-delete" title="Delete user">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($allUsers)): ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 32px; color: var(--fg-muted);">
              No users found.
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
