<?php
require 'config.php';
require 'auth.php';
require_login();

$uid = $_SESSION['user_id'];
$msg = ''; $err = '';

/* Load user + student info (if student) */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$uid]); $u = $stmt->fetch();

$studentRow = null;
if ($u['role'] === 'student') {
    $s = $pdo->prepare("SELECT * FROM students WHERE user_id=?");
    $s->execute([$uid]); $studentRow = $s->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['change_pass'])) {
        $new = $_POST['new_password'] ?? ''; $conf = $_POST['confirm_password'] ?? '';
        if (strlen($new) < 8)      $err = 'Password must be at least 8 characters.';
        elseif ($new !== $conf)    $err = 'Passwords do not match.';
        else {
            $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            $msg = 'Password updated.';
        }
    }
    elseif (isset($_POST['save_payments'])) {
        $bkash    = trim($_POST['bkash_number']     ?? '');
        $nagad    = trim($_POST['nagad_number']     ?? '');
        $cHolder  = trim($_POST['card_holder']      ?? '');
        $cNum     = trim($_POST['card_number']      ?? '');
        $cExp     = trim($_POST['card_expiry']      ?? '');
        $bName    = trim($_POST['bank_name']        ?? '');
        $bAccName = trim($_POST['bank_account_name']?? '');
        $bAccNum  = trim($_POST['bank_account_num'] ?? '');

        if ($bkash !== '' && !preg_match('/^01[0-9]{9}$/', $bkash))
            $err = 'bKash number must be 11 digits starting with 01.';
        elseif ($nagad !== '' && !preg_match('/^01[0-9]{9}$/', $nagad))
            $err = 'Nagad number must be 11 digits starting with 01.';
        elseif ($cNum !== '' && !preg_match('/^[0-9]{13,19}$/', $cNum))
            $err = 'Card number must be 13–19 digits (no spaces).';
        elseif ($bName !== '' && $bAccNum === '')
            $err = 'Bank account number is required when bank name is filled.';

        if ($err === '') {
            $pdo->prepare("
                INSERT INTO user_payment_methods
                  (user_id, bkash_number, nagad_number, card_holder, card_number, card_expiry,
                   bank_name, bank_account_name, bank_account_num)
                VALUES (?,?,?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE
                  bkash_number      = VALUES(bkash_number),
                  nagad_number      = VALUES(nagad_number),
                  card_holder       = VALUES(card_holder),
                  card_number       = VALUES(card_number),
                  card_expiry       = VALUES(card_expiry),
                  bank_name         = VALUES(bank_name),
                  bank_account_name = VALUES(bank_account_name),
                  bank_account_num  = VALUES(bank_account_num)
            ")->execute([$uid, $bkash, $nagad, $cHolder, $cNum, $cExp, $bName, $bAccName, $bAccNum]);

            $msg = 'Payment methods saved.';
        }
    }
    else {
        $name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $addr = trim($_POST['address'] ?? '');
        if ($name === '') { $err = 'Name cannot be empty.'; }
        else {
            $pdo->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?")->execute([$name, $phone, $uid]);
            if ($studentRow) $pdo->prepare("UPDATE students SET default_address=? WHERE user_id=?")->execute([$addr, $uid]);
            $_SESSION['name'] = $name;
            $msg = 'Profile updated.';
            $u['full_name'] = $name; $u['phone'] = $phone;
            if ($studentRow) $studentRow['default_address'] = $addr;
        }
    }
}

$pmStmt = $pdo->prepare("SELECT * FROM user_payment_methods WHERE user_id=?");
$pmStmt->execute([$uid]);
$pm = $pmStmt->fetch();
if (!$pm) {
    $pm = ['bkash_number'=>'','nagad_number'=>'','card_holder'=>'','card_number'=>'','card_expiry'=>'','bank_name'=>'','bank_account_name'=>'','bank_account_num'=>''];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-logo">
        <div class="sidebar-logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg></div>
        <span class="sidebar-logo-text">UIU Food Portal</span>
      </div>
      <nav class="sidebar-nav">
        <a href="<?= redirect_by_role($u['role']) ?>" class="sidebar-nav-item"><i class="fa-solid fa-house"></i> Home</a>

        <?php if ($u['role'] === 'student'): ?>
          <a href="order-history.php" class="sidebar-nav-item"><i class="fa-solid fa-receipt"></i> My Orders</a>
          <a href="wallet.php"        class="sidebar-nav-item"><i class="fa-solid fa-wallet"></i> Wallet</a>
          <a href="chat.php"          class="sidebar-nav-item"><i class="fa-solid fa-comment"></i> Messages</a>
        <?php elseif ($u['role'] === 'shop_owner'): ?>
          <a href="shop-orders.php"   class="sidebar-nav-item"><i class="fa-solid fa-receipt"></i> Orders</a>
          <a href="shop-menu.php"     class="sidebar-nav-item"><i class="fa-solid fa-utensils"></i> Menu</a>
          <a href="sales-history.php" class="sidebar-nav-item"><i class="fa-solid fa-dollar-sign"></i> Sales</a>
          <a href="shop-reports.php"  class="sidebar-nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>
        <?php elseif ($u['role'] === 'runner'): ?>
          <a href="runner-dashboard.php"  class="sidebar-nav-item"><i class="fa-solid fa-bicycle"></i> Available Jobs</a>
          <a href="runner-deliveries.php" class="sidebar-nav-item"><i class="fa-solid fa-clipboard-list"></i> My Deliveries</a>
          <a href="runner-earnings.php"   class="sidebar-nav-item"><i class="fa-solid fa-wallet"></i> Earnings</a>
          <a href="chat.php"              class="sidebar-nav-item"><i class="fa-solid fa-comment"></i> Messages</a>
        <?php elseif ($u['role'] === 'admin'): ?>
          <a href="admin-dashboard.php"        class="sidebar-nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
          <a href="admin-shop-approvals.php"   class="sidebar-nav-item"><i class="fa-solid fa-store"></i> Shop Approvals</a>
          <a href="admin-runner-approvals.php" class="sidebar-nav-item"><i class="fa-solid fa-bicycle"></i> Runner Approvals</a>
          <a href="admin-reports.php"          class="sidebar-nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>
          <a href="complaint-management.php"   class="sidebar-nav-item"><i class="fa-solid fa-comment"></i> Complaints</a>
        <?php endif; ?>

        <a href="notifications.php" class="sidebar-nav-item">
          <i class="fa-solid fa-bell"></i> Notifications
          <?php $__uc = unread_notification_count($pdo, $_SESSION['user_id']); if ($__uc > 0): ?>
            <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:99px;font-size:0.65rem;padding:1px 6px;font-weight:700;"><?= $__uc ?></span>
          <?php endif; ?>
        </a>

        <a href="profile.php" class="sidebar-nav-item active"><i class="fa-solid fa-user"></i> Profile</a>
      </nav>
      <div class="sidebar-user">
        <img src="<?= e(avatar_url()) ?>" alt="<?= e($_SESSION['name']) ?>" class="sidebar-user-avatar" />
        <div class="sidebar-user-info">
          <p class="sidebar-user-name"><?= e($_SESSION['name']) ?></p>
          <p class="sidebar-user-role"><?= ucfirst(str_replace('_',' ', $u['role'])) ?></p>
          <a href="logout.php" class="sidebar-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <div class="page-header"><div class="page-header-left"><h1 class="page-title">Profile &amp; Settings</h1></div></div>

      <div style="padding:24px 32px;max-width:720px;">
        <?php if ($msg): ?><div style="background:#e8f5e9;color:#2e7d32;padding:10px 14px;border-radius:8px;font-weight:600;margin-bottom:14px;"><?= e($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div style="background:#fdecea;color:#c62828;padding:10px 14px;border-radius:8px;font-weight:600;margin-bottom:14px;"><?= e($err) ?></div><?php endif; ?>

        <form method="post" class="section-card">
          <h2 class="section-card-title">Personal Information</h2>
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-input" value="<?= e($u['full_name']) ?>" required />
          </div>
          <div class="form-group">
            <label class="form-label">Email (read-only)</label>
            <input type="email" class="form-input" value="<?= e($u['email']) ?>" disabled />
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="tel" name="phone" class="form-input" value="<?= e($u['phone']) ?>" />
          </div>
          <?php if ($studentRow): ?>
            <div class="form-group">
              <label class="form-label">Default Delivery Address</label>
              <input type="text" name="address" class="form-input" value="<?= e($studentRow['default_address']) ?>" />
            </div>
          <?php endif; ?>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>

        <form method="post" class="section-card" style="margin-top:16px;">
          <h2 class="section-card-title">Payment Methods</h2>
          <p style="font-size:0.8rem;color:#888;margin-bottom:16px;">
            Save your payment details for faster checkout, refunds and payouts. Leave any method blank if unused.
          </p>

          <div style="border:1px solid var(--color-border-light);border-radius:10px;padding:16px;margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
              <span style="font-weight:700;font-size:0.9rem;">
                <i class="fa-solid fa-mobile-screen-button" style="color:#e2136e;margin-right:6px;"></i> bKash
              </span>
              <?php if ($pm['bkash_number'] !== ''): ?>
                <span style="font-size:0.72rem;color:#2e7d32;font-weight:700;">
                  <i class="fa-solid fa-circle-check"></i> Saved
                </span>
              <?php endif; ?>
            </div>
            <input type="text" name="bkash_number" class="form-input"
                   placeholder="e.g. 017XXXXXXXX"
                   value="<?= e($pm['bkash_number']) ?>" />
          </div>

          <div style="border:1px solid var(--color-border-light);border-radius:10px;padding:16px;margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
              <span style="font-weight:700;font-size:0.9rem;">
                <i class="fa-solid fa-mobile-screen-button" style="color:#f39c12;margin-right:6px;"></i> Nagad
              </span>
              <?php if ($pm['nagad_number'] !== ''): ?>
                <span style="font-size:0.72rem;color:#2e7d32;font-weight:700;">
                  <i class="fa-solid fa-circle-check"></i> Saved
                </span>
              <?php endif; ?>
            </div>
            <input type="text" name="nagad_number" class="form-input"
                   placeholder="e.g. 017XXXXXXXX"
                   value="<?= e($pm['nagad_number']) ?>" />
          </div>

          <div style="border:1px solid var(--color-border-light);border-radius:10px;padding:16px;margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
              <span style="font-weight:700;font-size:0.9rem;">
                <i class="fa-solid fa-credit-card" style="color:#2980b9;margin-right:6px;"></i> Debit Card
              </span>
              <?php if ($pm['card_number'] !== ''): ?>
                <span style="font-size:0.72rem;color:#2e7d32;font-weight:700;">
                  <i class="fa-solid fa-circle-check"></i> Saved
                </span>
              <?php endif; ?>
            </div>
            <div class="form-group" style="margin-bottom:12px;">
              <label class="form-label">Card Holder Name</label>
              <input type="text" name="card_holder" class="form-input"
                     placeholder="e.g. John Doe"
                     value="<?= e($pm['card_holder']) ?>" />
            </div>
            <div class="form-group" style="margin-bottom:12px;">
              <label class="form-label">Card Number</label>
              <input type="text" name="card_number" class="form-input"
                     placeholder="16 digits, no spaces"
                     value="<?= e($pm['card_number']) ?>"
                     maxlength="19" />
            </div>
            <div class="form-group" style="margin-bottom:0;max-width:160px;">
              <label class="form-label">Expiry (MM/YY)</label>
              <input type="text" name="card_expiry" class="form-input"
                     placeholder="MM/YY"
                     value="<?= e($pm['card_expiry']) ?>"
                     maxlength="5" />
            </div>
          </div>

          <div style="border:1px solid var(--color-border-light);border-radius:10px;padding:16px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
              <span style="font-weight:700;font-size:0.9rem;">
                <i class="fa-solid fa-building-columns" style="color:#8e44ad;margin-right:6px;"></i> Bank Transfer
              </span>
              <?php if ($pm['bank_name'] !== ''): ?>
                <span style="font-size:0.72rem;color:#2e7d32;font-weight:700;">
                  <i class="fa-solid fa-circle-check"></i> Saved
                </span>
              <?php endif; ?>
            </div>
            <div class="form-group" style="margin-bottom:12px;">
              <label class="form-label">Bank Name</label>
              <input type="text" name="bank_name" class="form-input"
                     placeholder="e.g. Dutch Bangla Bank"
                     value="<?= e($pm['bank_name']) ?>" />
            </div>
            <div class="form-group" style="margin-bottom:12px;">
              <label class="form-label">Account Holder Name</label>
              <input type="text" name="bank_account_name" class="form-input"
                     placeholder="Name on the account"
                     value="<?= e($pm['bank_account_name']) ?>" />
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Account Number</label>
              <input type="text" name="bank_account_num" class="form-input"
                     placeholder="Bank account number"
                     value="<?= e($pm['bank_account_num']) ?>" />
            </div>
          </div>

          <button type="submit" name="save_payments" value="1" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk" style="margin-right:6px;"></i>Save Payment Methods
          </button>
        </form>

        <form method="post" class="section-card" style="margin-top:16px;">
          <h2 class="section-card-title">Change Password</h2>
          <input type="hidden" name="change_pass" value="1" />
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-input" required />
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-input" required />
          </div>
          <button type="submit" class="btn btn-outline">Update Password</button>
        </form>

      </div>
    </main>
  </div>
</body>
</html>