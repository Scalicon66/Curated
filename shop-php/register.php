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
  <div class="auth-container">
    <div class="auth-header">
      <h2>Create an Account</h2>
      <p>Join us to enjoy a curated shopping experience</p>
    </div>

    <form method="post" action="register.php" class="form">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
      
      <?php if ($error): ?>
        <div class="demo-note" style="background: var(--warn); color: #fff; text-align: center;">
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus placeholder="johndoe" />
      </div>

      <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required placeholder="hello@example.com" />
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="6" placeholder="At least 6 characters" />
      </div>

      <div class="field-row">
        <div class="field">
          <label for="age">Age</label>
          <input type="number" id="age" name="age" required min="1" placeholder="25" />
        </div>

        <div class="field">
          <label for="gender">Gender</label>
          <select id="gender" name="gender" required style="width: 100%; padding: 12px 14px; border: 1px solid var(--border); border-radius: 8px; font: inherit; background: var(--bg); transition: border-color var(--transition), background var(--transition);">
            <option value="" disabled selected>Select...</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>
      </div>



      <button type="submit" class="btn btn-primary">Sign Up</button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="login.php">Log in</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
