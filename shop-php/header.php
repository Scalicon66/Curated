<?php
$flash = flash_pop();
$count = cart_count();
$isAdmin = user_current() ? (user_details(user_current())['type'] ?? '') === 'Admin' : false;

$currentPage = basename($_SERVER['SCRIPT_NAME']);
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}

$lang = lang_current();
$dir = ($lang === 'ar') ? 'rtl' : 'ltr';
?><!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle ?? 'Curated. — Essential Objects') ?></title>
  <meta name="description" content="A small curated boutique of thoughtfully designed objects for daily rituals." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <?php if ($lang === 'ar'): ?>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
  <?php else: ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet" />
  <?php endif; ?>
  <link rel="stylesheet" href="styles.css" />
  <style>
    /* Critical Loader Styles */
    #preloader {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: var(--bg);
      display: flex;
      align-items: center; justify-content: center;
      z-index: 99999;
      transition: opacity 0.4s ease;
    }
    .loader-spinner {
      width: 50px; height: 50px;
      border: 3px solid var(--accent-soft);
      border-top: 3px solid var(--accent);
      border-radius: 50%;
      animation: spin 0.8s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    #preloader.fade-out { opacity: 0; pointer-events: none; }

    /* RTL specific overrides if needed */
    [dir="rtl"] .cart-badge { left: -8px; right: auto; }
    [dir="rtl"] .search-form button { left: 6px; right: auto; }
    [dir="rtl"] .search-form input { padding-left: 45px; padding-right: 16px; }
  </style>
</head>
<body>
  <!-- Page Preloader -->
  <div id="preloader">
    <div class="loader-spinner"></div>
  </div>

  <script>
    window.addEventListener('load', function() {
      const preloader = document.getElementById('preloader');
      if (preloader) {
        setTimeout(() => {
          preloader.classList.add('fade-out');
          setTimeout(() => preloader.remove(), 400);
        }, 400); 
      }
    });

    document.addEventListener('submit', function(e) {
      const submitBtn = e.target.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.classList.add('btn-loading');
      }
    });
  </script>

  <header class="site-header">
    <div class="container header-inner">
      <div style="display: flex; align-items: center; gap: 24px;">
        <a href="index.php" class="logo">Curated.</a>
        <div class="lang-switcher" style="display: flex; gap: 8px; font-size: 12px; font-weight: 600;">
          <a href="?lang=en" style="color: <?= $lang === 'en' ? 'var(--accent)' : 'var(--fg-muted)' ?>; text-decoration: none;">EN</a>
          <span style="color: var(--border);">|</span>
          <a href="?lang=ar" style="color: <?= $lang === 'ar' ? 'var(--accent)' : 'var(--fg-muted)' ?>; text-decoration: none;">AR</a>
        </div>
      </div>

      <nav class="nav">
        <a href="index.php" class="<?= isActive('index.php') ?>"><?= t('shop_all') ?></a>
        <?php if (user_current()): ?>
          <?php if ($isAdmin): ?>
            <a href="admin.php" class="nav-admin <?= isActive('admin.php') ?>"><?= t('admin_panel') ?></a>
          <?php endif; ?>
          <a href="profile.php" class="<?= isActive('profile.php') ?>"><?= t('profile') ?></a>
          <a href="logout.php"><?= t('logout') ?></a>
        <?php else: ?>
          <a href="login.php" class="<?= isActive('login.php') ?>"><?= t('login') ?></a>
          <a href="register.php" class="<?= isActive('register.php') ?>"><?= t('signup') ?></a>
        <?php endif; ?>
      </nav>
      <a href="cart.php" class="cart-link" aria-label="<?= t('cart') ?>">
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
