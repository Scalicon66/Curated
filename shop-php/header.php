<?php
$flash = flash_pop();
$count = cart_count();
$isAdmin = user_current() ? (user_details(user_current())['type'] ?? '') === 'Admin' : false;

$currentPage = basename($_SERVER['SCRIPT_NAME']);
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle ?? 'Curated. — Essential Objects') ?></title>
  <meta name="description" content="A small curated boutique of thoughtfully designed objects for daily rituals." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a href="index.php" class="logo">Curated.</a>
      <nav class="nav">
        <a href="index.php" class="<?= isActive('index.php') ?>">Shop All</a>
        <?php if (user_current()): ?>
          <?php if ($isAdmin): ?>
            <a href="admin.php" class="nav-admin <?= isActive('admin.php') ?>">Admin Panel</a>
          <?php endif; ?>
          <a href="profile.php" class="<?= isActive('profile.php') ?>">Profile</a>
          <a href="logout.php">Logout</a>
        <?php else: ?>
          <a href="login.php" class="<?= isActive('login.php') ?>">Log In</a>
          <a href="register.php" class="<?= isActive('register.php') ?>">Sign Up</a>
        <?php endif; ?>
      </nav>
      <a href="cart.php" class="cart-link" aria-label="Cart">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <?php if ($count > 0): ?>
          <span class="cart-badge"><?= (int)$count ?></span>
        <?php endif; ?>
      </a>
    </div>
  </header>

  <main class="container">
  <?php if ($flash): ?>
    <div id="serverFlash" data-message="<?= e($flash) ?>" hidden></div>
  <?php endif; ?>
