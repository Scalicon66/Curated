<?php
$flash = flash_pop();
$count = cart_count();
$userEmail = user_current();
$isAdmin = $userEmail ? (user_details($userEmail)['type'] ?? '') === 'Admin' : false;
$unreadNotifications = notify_count_unread($isAdmin ? null : $userEmail);

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
    /* Premium Brand Preloader */
    #preloader {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: var(--bg);
      display: flex;
      flex-direction: column;
      align-items: center; justify-content: center;
      z-index: 99999;
      transition: opacity 0.8s cubic-bezier(0.65, 0, 0.35, 1), transform 0.8s cubic-bezier(0.65, 0, 0.35, 1);
    }
    #preloader.fade-out { 
      opacity: 0; 
      transform: translateY(-30px);
      pointer-events: none; 
    }
    .loader-content {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 16px;
    }
    .loader-logo {
      font-family: 'Playfair Display', serif;
      font-size: 48px;
      font-weight: 700;
      color: var(--accent);
      display: flex;
      overflow: hidden;
    }
    .loader-logo span {
      display: inline-block;
      opacity: 0;
      transform: translateY(100%);
      animation: letterReveal 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
    }
    @keyframes letterReveal {
      to { opacity: 1; transform: translateY(0); }
    }
    .loader-logo span:nth-child(1) { animation-delay: 0.1s; }
    .loader-logo span:nth-child(2) { animation-delay: 0.15s; }
    .loader-logo span:nth-child(3) { animation-delay: 0.2s; }
    .loader-logo span:nth-child(4) { animation-delay: 0.25s; }
    .loader-logo span:nth-child(5) { animation-delay: 0.3s; }
    .loader-logo span:nth-child(6) { animation-delay: 0.35s; }
    .loader-logo span:nth-child(7) { animation-delay: 0.4s; }

    .loader-progress {
      width: 120px;
      height: 1px;
      background: var(--accent-soft);
      position: relative;
      overflow: hidden;
    }
    .loader-progress::after {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: var(--accent);
      transform: translateX(-100%);
      animation: progressMove 1.5s infinite ease-in-out;
    }
    @keyframes progressMove {
      0% { transform: translateX(-100%); }
      50% { transform: translateX(0); }
      100% { transform: translateX(100%); }
    }

    /* RTL specific overrides if needed */
    [dir="rtl"] .cart-badge { left: -8px; right: auto; }
    [dir="rtl"] .search-form button { left: 6px; right: auto; }
    [dir="rtl"] .search-form input { padding-left: 45px; padding-right: 16px; }
  </style>
</head>
<body>
  <!-- Page Preloader -->
  <div id="preloader">
    <div class="loader-content">
      <div class="loader-logo">
        <span>C</span><span>u</span><span>r</span><span>a</span><span>t</span><span>e</span><span>d</span>
      </div>
      <div class="loader-progress"></div>
    </div>
  </div>

  <script>
    window.addEventListener('load', function() {
      const preloader = document.getElementById('preloader');
      if (preloader) {
        // Lock scroll during preloader
        document.body.style.overflow = 'hidden';
        
        // Ensure a minimum display time for the beautiful animation
        const minTime = 800; 
        const startTime = window.performance.now();
        
        const hidePreloader = () => {
          const currentTime = window.performance.now();
          const elapsed = currentTime - startTime;
          const delay = Math.max(0, minTime - elapsed);
          
          setTimeout(() => {
            preloader.classList.add('fade-out');
            document.body.style.overflow = ''; // Unlock scroll
            setTimeout(() => preloader.remove(), 800);
          }, delay);
        };

        hidePreloader();
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
        <a href="index.php" class="logo">Curated</a>
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
      <div style="display: flex; align-items: center; gap: 16px;">
        <a href="notifications.php" class="cart-link" aria-label="Notifications" style="position: relative;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
          <?php if ($unreadNotifications > 0): ?>
            <span class="cart-badge" style="background: var(--accent);"><?= (int)$unreadNotifications ?></span>
          <?php endif; ?>
        </a>
        <a href="cart.php" class="cart-link" aria-label="<?= t('cart') ?>" style="position: relative;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <?php if ($count > 0): ?>
            <span class="cart-badge"><?= (int)$count ?></span>
          <?php endif; ?>
        </a>
      </div>
    </div>
  </header>

  <main class="container">
  <?php if ($flash): ?>
    <div id="serverFlash" data-message="<?= e($flash) ?>" hidden></div>
  <?php endif; ?>
