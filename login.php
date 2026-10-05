<?php
require 'config.php';
require 'auth.php';

if (is_logged_in()) { header('Location: ' . redirect_by_role($_SESSION['role'])); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        $error = 'Please fill in both fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } elseif (!$user['is_active']) {
            $error = 'Your account has been deactivated.';
        } else {
            if ($user['role'] === 'shop_owner') {
                $s = $pdo->prepare("SELECT status FROM shops WHERE owner_id=? LIMIT 1");
                $s->execute([$user['id']]);
                $row = $s->fetch();
                if ($row && $row['status'] === 'pending')  $error = 'Your shop is still pending admin approval.';
                if ($row && $row['status'] === 'rejected') $error = 'Your shop application was rejected.';
            }
            if ($user['role'] === 'runner') {
                $r = $pdo->prepare("SELECT status FROM runners WHERE user_id=? LIMIT 1");
                $r->execute([$user['id']]);
                $row = $r->fetch();
                if ($row && $row['status'] === 'pending')  $error = 'Your runner account is pending approval.';
                if ($row && $row['status'] === 'rejected') $error = 'Your runner application was rejected.';
            }
        }

        if ($error === '') {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['name']          = $user['full_name'];
            $_SESSION['email']         = $user['email'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['profile_image'] = $user['profile_image'];   
            header('Location: ' . redirect_by_role($user['role']));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UIU Food Delivery — Login</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="login-page">
    <div class="login-card">
      <div class="login-brand-icon">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 11l19-9-9 19-2-8-8-2z" />
        </svg>
      </div>

      <h1 class="login-title">Welcome Back</h1>
      <p class="login-subtitle">Sign in to your UIU Food Delivery account</p>

      <?php if ($error): ?>
        <div style="background:#fdecea;color:#c62828;padding:10px 14px;border-radius:8px;font-size:0.85rem;font-weight:600;margin-bottom:14px;">
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" action="login.php">
        <div class="form-group">
          <label class="form-label" for="loginEmail">University Email Address</label>
          <div class="input-icon-wrap">
            <span class="input-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                <polyline points="22,6 12,13 2,6" />
              </svg>
            </span>
            <input type="email" id="loginEmail" name="email" class="form-input" placeholder="student@uiu.edu" required />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="loginPassword">Password</label>
          <div class="input-icon-wrap">
            <span class="input-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
              </svg>
            </span>
            <input type="password" id="loginPassword" name="password" class="form-input" placeholder="Enter your password" required />
          </div>
        </div>

        <div class="login-extras">
          <label class="remember-me">
            <input type="checkbox" class="remember-checkbox" checked />
            <span class="remember-label">Remember me</span>
          </label>
          <a href="#" class="forgot-link">Forgot Password?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-bottom: var(--space-4);">Sign In</button>
      </form>

      <p class="login-register" style="margin-top: var(--space-5); text-align: center;">
        Don't have an account? <a href="register.php">Register</a>
      </p>
    </div>
  </div>
</body>
</html>