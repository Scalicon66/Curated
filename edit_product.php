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

$productId = $_GET['id'] ?? '';
$product = product_find_db($productId);
if (!$product) {
    flash_set('Product not found.');
    redirect('admin.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $pName     = trim($_POST['product_name'] ?? '');
        $pPrice    = (float)($_POST['product_price'] ?? 0);
        $pCategory = trim($_POST['product_category'] ?? '');
        $pShort    = trim($_POST['product_short'] ?? '');
        $pLong     = trim($_POST['product_long'] ?? '');
        $pStock    = (int)($_POST['product_stock'] ?? 0);

        if (!$pName || $pPrice <= 0 || !$pCategory || !$pShort || !$pLong || $pStock < 0) {
            $error = 'Please fill out all fields correctly.';
        } else {
            // Handle optional image upload (only update image if a new one was uploaded)
            $imageUrl = null; // null = keep existing image
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
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
                            $error = 'Failed to save the uploaded image. Check directory permissions.';
                        }
                    }
                } elseif ($fileError === UPLOAD_ERR_INI_SIZE || $fileError === UPLOAD_ERR_FORM_SIZE) {
                    $error = 'The uploaded image is too large.';
                } else {
                    $error = 'Image upload failed with error code: ' . $fileError;
                }
            }

            if (!$error) {
                product_update($productId, $pName, $pPrice, $pCategory, $pShort, $pLong, $imageUrl, $pStock);
                flash_set('Product "' . $pName . '" has been updated!');
                redirect('admin.php');
            }
        }
    }
}

$pageTitle = 'Edit Product — Curated.';
require __DIR__ . '/header.php';
?>

<div class="admin-layout container">
  <a href="admin.php" class="back-link">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Admin Panel
  </a>

  <div class="admin-card" style="margin-top: 24px; max-width: 700px;">
    <div class="card-header">
      <h3>Edit Product</h3>
    </div>
    <div style="padding: 24px;">
      <?php if ($error): ?>
        <div class="demo-note" style="background: var(--warn); color: #fff; text-align: center; margin-bottom: 20px; padding: 12px; border-radius: 8px;">
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <!-- Current Image Preview -->
      <div style="margin-bottom: 24px; text-align: center;">
        <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>"
             style="width: 120px; height: 120px; object-fit: cover; border-radius: var(--radius); box-shadow: var(--shadow-sm); background: var(--accent-soft);" />
        <p style="font-size: 12px; color: var(--fg-muted); margin-top: 8px;">Current image</p>
      </div>

      <form method="post" action="edit_product.php?id=<?= e($productId) ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

        <div class="admin-form-grid">
          <div class="field">
            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" name="product_name" required value="<?= e($product['name']) ?>" />
          </div>

          <div class="field-row">
            <div class="field">
              <label for="product_price">Price ($)</label>
              <input type="number" id="product_price" name="product_price" required min="0.01" step="0.01" value="<?= e((string)$product['price']) ?>" />
            </div>
            <div class="field">
              <label for="product_stock">Stock Quantity</label>
              <input type="number" id="product_stock" name="product_stock" required min="0" value="<?= e((string)$product['stock']) ?>" />
            </div>
          </div>

          <div class="field">
            <label for="product_category">Category</label>
            <input type="text" id="product_category" name="product_category" required value="<?= e($product['category']) ?>" />
          </div>

          <div class="field">
            <label for="product_image">Replace Image (optional — leave empty to keep current)</label>
            <input type="file" id="product_image" name="product_image" accept="image/*"
                   style="padding: 10px; border: 1px dashed var(--border); border-radius: 8px; background: var(--bg); width: 100%;" />
          </div>

          <div class="field">
            <label for="product_short">Short Description</label>
            <input type="text" id="product_short" name="product_short" required value="<?= e($product['short_description']) ?>" />
          </div>

          <div class="field">
            <label for="product_long">Long Description</label>
            <textarea id="product_long" name="product_long" required rows="4"
                      style="width: 100%; padding: 12px 14px; border: 1px solid var(--border); border-radius: 8px; font: inherit; background: var(--bg); resize: vertical;"><?= e($product['long_description']) ?></textarea>
          </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 20px;">
          <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Changes
          </button>
          <a href="admin.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
