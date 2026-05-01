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
  <div class="auth-container">
    <div class="auth-header">
      <h2>Welcome Back</h2>
      <p>Log in to your account to continue</p>
    </div>

    <form method="post" action="login.php" class="form">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
      
      <?php if ($error): ?>
        <div class="demo-note" style="background: var(--warn); color: #fff; text-align: center;">
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required autofocus placeholder="hello@example.com" />
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required placeholder="••••••••" />
      </div>

      <button type="submit" class="btn btn-primary">Log In</button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="register.php">Sign up</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
