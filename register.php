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
        $username = trim($_POST['username'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = (string)($_POST['password'] ?? '');
        $age = (int)($_POST['age'] ?? 0);
        $gender = $_POST['gender'] ?? '';
        $type = 'User';
        
        if (!$email || strlen($password) < 6 || !$username || $age <= 0 || !in_array($gender, ['Male', 'Female'])) {
            $error = 'Please fill out all fields correctly.';
        } else {
            if (user_register($username, $email, $password, $age, $gender, $type)) {
                // Auto login after registration
                user_login($email, $password);
                flash_set('Account created successfully. Welcome, ' . e($username) . '!');
                redirect('index.php');
            } else {
                $error = 'An account with that email already exists.';
            }
        }
    }
}

$pageTitle = 'Register — Curated.';
require __DIR__ . '/header.php';
?>

<div class="auth-layout">
  <div class="auth-header">
    <a href="index.php" style="text-decoration: none;"><h2>Curated</h2></a>
  </div>

  <div class="auth-container">
    <div style="text-align: center; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 16px;">
      <h3 style="margin: 0; font-size: 24px; font-weight: 700;">Create a new account</h3>
      <p style="margin: 4px 0 0; color: var(--fg-muted); font-size: 15px;">It's quick and easy.</p>
    </div>

    <form method="post" action="register.php" class="form">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
      
      <?php if ($error): ?>
        <div class="demo-note" style="background: var(--warn); color: #fff; text-align: center; margin-bottom: 20px;">
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <div class="field-row">
        <div class="field">
          <input type="text" id="username" name="username" required autofocus placeholder="Username" aria-label="Username" />
        </div>

        <div class="field">
          <input type="email" id="email" name="email" required placeholder="Email address" aria-label="Email address" />
        </div>
      </div>

      <div class="field">
        <input type="password" id="password" name="password" required minlength="6" placeholder="New password" aria-label="New password" />
      </div>

      <div class="field-row">
        <div class="field">
          <input type="number" id="age" name="age" required min="1" placeholder="Age" aria-label="Age" />
        </div>

        <div class="field">
          <select id="gender" name="gender" required aria-label="Gender">
            <option value="" disabled selected>Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>
      </div>

      <div style="font-size: 11px; color: var(--fg-muted); margin: 16px 0 20px; line-height: 1.4;">
        By clicking Sign Up, you agree to our Terms, Privacy Policy and Cookies Policy. You may receive SMS notifications from us and can opt out at any time.
      </div>

      <div style="text-align: center;">
        <button type="submit" class="btn btn-primary" style="background: var(--accent); border: none; padding: 10px 40px; width: auto;">Sign Up</button>
      </div>
    </form>

    <div class="auth-footer" style="border: none; padding-top: 16px;">
      <a href="login.php" style="color: var(--accent); text-decoration: none; font-weight: 600;">Already have an account?</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
