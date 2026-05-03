<?php
require_once __DIR__ . '/lib.php';

if (user_current()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = (string)($_POST['password'] ?? '');
        
        if (!$email || empty($password)) {
            $error = 'Please provide a valid email and password.';
        } else {
            if (user_login($email, $password)) {
                flash_set('Welcome back, ' . e($email) . '!');
                redirect('index.php');
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

$pageTitle = 'Login — Curated.';
require __DIR__ . '/header.php';
?>

<div class="auth-layout">
  <div class="auth-header">
    <a href="index.php" style="text-decoration: none;"><h2>Curated</h2></a>
  </div>

  <div class="auth-container">
    <form method="post" action="login.php" class="form">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
      
      <?php if ($error): ?>
        <div class="demo-note" style="background: var(--warn); color: #fff; text-align: center; margin-bottom: 20px;">
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <div class="field">
        <input type="email" id="email" name="email" required autofocus placeholder="Email address" aria-label="Email address" />
      </div>

      <div class="field">
        <input type="password" id="password" name="password" required placeholder="Password" aria-label="Password" />
      </div>

      <button type="submit" class="btn btn-primary">Log In</button>
      
      <div style="text-align: center; margin-top: 16px;">
        <a href="#" style="color: var(--accent); font-size: 14px; text-decoration: none;">Forgotten password?</a>
      </div>

      <div class="divider"></div>

      <a href="register.php" class="btn btn-secondary">Create new account</a>
    </form>
  </div>
  
</div>

<?php require __DIR__ . '/footer.php'; ?>
