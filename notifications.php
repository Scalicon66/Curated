<?php
require_once __DIR__ . '/lib.php';

$userEmail = user_current();
if (!$userEmail) {
    redirect('login.php');
}

$isAdmin = (user_details($userEmail)['type'] ?? '') === 'Admin';
$notifications = notify_get($isAdmin ? null : $userEmail, 20);

// Handle Mark All as Read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    if (csrf_check($_POST['csrf'] ?? '')) {
        notify_mark_all_read($isAdmin ? null : $userEmail);
        flash_set('All notifications marked as read.');
        redirect('notifications.php');
    }
}

$pageTitle = 'Notifications — Curated.';
require __DIR__ . '/header.php';
?>

<div class="notifications-page container" style="max-width: 800px; margin-top: 40px; margin-bottom: 80px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; border-bottom: 1px solid var(--border); padding-bottom: 16px;">
    <div>
      <h2 style="margin: 0; font-family: 'Playfair Display', serif; font-size: 32px;">Notifications</h2>
      <p style="color: var(--fg-muted); margin-top: 4px; font-size: 14px;">Stay updated with your orders and account activity.</p>
    </div>
    <?php if (!empty($notifications)): ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
        <input type="hidden" name="action" value="mark_all_read" />
        <button type="submit" class="btn-ghost" style="font-size: 13px; font-weight: 600;">Mark all as read</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (empty($notifications)): ?>
    <div style="text-align: center; padding: 80px 24px; background: var(--bg-soft); border-radius: 16px; border: 1px dashed var(--border);">
      <div style="margin-bottom: 20px; color: var(--border);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="64" height="64"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
      </div>
      <h3 style="margin-bottom: 8px;">No notifications yet</h3>
      <p style="color: var(--fg-muted);">When you receive updates about your orders, they'll appear here.</p>
      <a href="index.php" class="btn btn-primary" style="margin-top: 24px; display: inline-flex;">Continue Shopping</a>
    </div>
  <?php else: ?>
    <div class="notification-list" style="display: flex; flex-direction: column; gap: 16px;">
      <?php foreach ($notifications as $n): ?>
        <div class="notification-card <?= $n['is_read'] ? '' : 'unread' ?>" 
             style="padding: 20px; border-radius: 12px; border: 1px solid <?= $n['is_read'] ? 'var(--border)' : 'var(--accent-soft)' ?>; 
                    background: <?= $n['is_read'] ? 'var(--bg)' : 'rgba(var(--accent-rgb), 0.03)' ?>; 
                    position: relative; transition: transform 0.2s ease;">
          <?php if (!$n['is_read']): ?>
            <div style="position: absolute; top: 20px; right: 20px; width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
          <?php endif; ?>
          
          <div style="display: flex; gap: 16px;">
            <div style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 10px; background: var(--accent-soft); color: var(--accent); display: flex; align-items: center; justify-content: center;">
               <?php if (strpos($n['title'], 'Order') !== false): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
               <?php elseif (strpos($n['title'], 'Stock') !== false): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
               <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
               <?php endif; ?>
            </div>
            <div style="flex-grow: 1;">
              <h4 style="margin: 0; font-size: 16px; font-weight: 600;"><?= e($n['title']) ?></h4>
              <p style="margin: 4px 0 12px; color: var(--fg-muted); line-height: 1.5; font-size: 14px;"><?= e($n['message']) ?></p>
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 11px; color: var(--fg-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">
                  <?= date('M j, g:i a', strtotime($n['created_at'])) ?>
                </span>
                <?php if ($n['link']): ?>
                  <a href="<?= e($n['link']) ?>" class="link-arrow" style="font-size: 13px; font-weight: 600; color: var(--accent); text-decoration: none;">
                    View Details
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" style="margin-left: 4px; vertical-align: middle;"><polyline points="9 18 15 12 9 6"/></svg>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<style>
  .notification-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
  }
  .notification-card.unread {
    border-left: 3px solid var(--accent) !important;
  }
</style>

<?php require __DIR__ . '/footer.php'; ?>
