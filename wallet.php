<?php
require 'config.php';
require 'auth.php';
require_role('student');

$uid = $_SESSION['user_id'];
$me  = $pdo->query("SELECT s.id AS sid, s.wallet_balance, s.student_id FROM students s WHERE s.user_id=$uid")->fetch();
$sid = $me['sid'];

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amt    = (float)($_POST['amount'] ?? 0);
    $method = $_POST['method'] ?? 'bkash';

    if ($amt <= 0)          $err = 'Please enter a valid amount.';
    elseif ($amt > 50000)   $err = 'Maximum top-up is $50,000.';
    else {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE students SET wallet_balance = wallet_balance + ? WHERE id=?")->execute([$amt, $sid]);
        $pdo->prepare("INSERT INTO wallet_transactions (student_id, type, amount, direction, method, description) VALUES (?, 'topup', ?, 'credit', ?, 'Wallet top-up')")
            ->execute([$sid, $amt, $method]);
        $pdo->commit();
        $msg = '$' . number_format($amt, 2) . ' added to your wallet.';
        $me['wallet_balance'] += $amt;

        notify($pdo, $uid, 'Wallet Topped Up',
            '$' . number_format($amt, 2) . ' added via ' . ucfirst($method) . '.',
            'system', 'wallet.php');
    }
}

$txns = $pdo->prepare("SELECT * FROM wallet_transactions WHERE student_id=? ORDER BY created_at DESC LIMIT 20");
$txns->execute([$sid]); $txns = $txns->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Wallet</title>
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
        <a href="student-dashboard.php" class="sidebar-nav-item"><i class="fa-solid fa-house"></i> Home</a>
        <a href="order-history.php"       class="sidebar-nav-item"><i class="fa-solid fa-receipt"></i> My Orders</a>
        <a href="wallet.php"              class="sidebar-nav-item active"><i class="fa-solid fa-wallet"></i> Wallet</a>
        <a href="chat.php"                class="sidebar-nav-item"><i class="fa-solid fa-comment"></i> Messages</a>

        <a href="notifications.php" class="sidebar-nav-item">
          <i class="fa-solid fa-bell"></i> Notifications
          <?php $__uc = unread_notification_count($pdo, $_SESSION['user_id']); if ($__uc > 0): ?>
            <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:99px;font-size:0.65rem;padding:1px 6px;font-weight:700;"><?= $__uc ?></span>
          <?php endif; ?>
        </a>

        <a href="profile.php"             class="sidebar-nav-item"><i class="fa-solid fa-user"></i> Profile</a>
      </nav>
      <div class="sidebar-user">
        <img src="<?= e(avatar_url()) ?>" alt="<?= e($_SESSION['name']) ?>" class="sidebar-user-avatar" />
        <div class="sidebar-user-info">
          <p class="sidebar-user-name"><?= e($_SESSION['name']) ?></p>
          <p class="sidebar-user-role">Student Account</p>
          <a href="logout.php" class="sidebar-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <div class="page-header">
        <div class="page-header-left"><h1 class="page-title">My Wallet</h1></div>
      </div>

      <div class="wallet-body" style="padding: 24px 32px; max-width: 900px;">
        <?php if ($msg): ?>
          <div style="background:#e8f5e9;color:#2e7d32;padding:10px 14px;border-radius:8px;font-weight:600;margin-bottom:14px;"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
          <div style="background:#fdecea;color:#c62828;padding:10px 14px;border-radius:8px;font-weight:600;margin-bottom:14px;"><?= e($err) ?></div>
        <?php endif; ?>

        <div class="wallet-hero">
          <p class="wallet-hero-label"><i class="fa-solid fa-wallet"></i> UIU Student Wallet</p>
          <p class="wallet-hero-balance"><span>$</span><?= number_format((float)$me['wallet_balance'], 2) ?></p>
          <p class="wallet-hero-card-number">Student ID • <?= e($me['student_id']) ?> • <?= e($_SESSION['name']) ?></p>
        </div>

        <div class="section-card">
          <h2 class="section-card-title">Add Funds</h2>
          <form method="post" style="display:flex;gap:10px;flex-wrap:wrap;">
            <input type="number" name="amount" class="form-input" placeholder="Enter amount (max 50000)" min="1" max="50000" required style="flex:1;min-width:180px;" />
            <select name="method" class="form-input" style="width:150px;">
              <option value="bkash">bKash</option>
              <option value="nagad">Nagad</option>
              <option value="card">Debit Card</option>
              <option value="bank">Bank Transfer</option>
            </select>
            <button type="submit" class="btn btn-primary">Add Funds</button>
          </form>
        </div>

        <div class="section-card" style="margin-top:16px;">
          <h2 class="section-card-title">Recent Transactions</h2>
          <table class="data-table">
            <thead><tr><th>Date</th><th>Description</th><th>Method</th><th style="text-align:right;">Amount</th></tr></thead>
            <tbody>
              <?php foreach ($txns as $t): ?>
                <tr>
                  <td style="font-size:0.85rem;color:#666;"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                  <td><?= e($t['description'] ?: ucfirst(str_replace('_',' ',$t['type']))) ?></td>
                  <td style="font-size:0.85rem;"><?= e(ucfirst($t['method'])) ?></td>
                  <td style="text-align:right;font-weight:700;color:<?= $t['direction']==='credit'?'#27ae60':'#e74c3c' ?>;">
                    <?= $t['direction']==='credit' ? '+' : '−' ?>$<?= number_format((float)$t['amount'],2) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$txns): ?>
                <tr><td colspan="4" style="text-align:center;padding:30px;color:#888;">No transactions yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>