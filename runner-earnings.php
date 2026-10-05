<?php
require 'config.php';
require 'auth.php';
require_role('runner');

$uid = $_SESSION['user_id'];
$runner = $pdo->query("SELECT * FROM runners WHERE user_id=$uid")->fetch();
$rid = $runner['id'];

$payouts = $pdo->prepare("SELECT * FROM payouts WHERE runner_id=? ORDER BY requested_at DESC LIMIT 20");
$payouts->execute([$rid]); $payouts = $payouts->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Earnings</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-logo"><div class="sidebar-logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg></div><span class="sidebar-logo-text">UIU Runner Portal</span></div>
      <nav class="sidebar-nav">
        <a href="runner-dashboard.php"  class="sidebar-nav-item"><i class="fa-solid fa-bicycle"></i> Available Jobs</a>
        <a href="runner-deliveries.php" class="sidebar-nav-item"><i class="fa-solid fa-clipboard-list"></i> My Deliveries</a>
        <a href="runner-earnings.php"   class="sidebar-nav-item active"><i class="fa-solid fa-wallet"></i> Earnings</a>
        <a href="chat.php"              class="sidebar-nav-item"><i class="fa-solid fa-comment"></i> Messages</a>

        <a href="notifications.php" class="sidebar-nav-item">
          <i class="fa-solid fa-bell"></i> Notifications
          <?php $__uc = unread_notification_count($pdo, $_SESSION['user_id']); if ($__uc > 0): ?>
            <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:99px;font-size:0.65rem;padding:1px 6px;font-weight:700;"><?= $__uc ?></span>
          <?php endif; ?>
        </a>

        <a href="profile.php"           class="sidebar-nav-item"><i class="fa-solid fa-user"></i> Profile</a>
      </nav>
      <div class="sidebar-user">
        <img src="<?= e(avatar_url()) ?>" alt="<?= e($_SESSION['name']) ?>" class="sidebar-user-avatar" />
        <div class="sidebar-user-info">
          <p class="sidebar-user-name"><?= e($_SESSION['name']) ?></p>
          <p class="sidebar-user-role">Runner</p>
          <a href="logout.php" class="sidebar-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <div class="runner-topbar"><h1 class="runner-topbar-title">Earnings</h1></div>
      <div style="padding:24px 32px;">
        <div class="earnings-hero-card">
          <p class="earnings-total-label"><i class="fa-solid fa-calendar"></i> Total Earnings</p>
          <p class="earnings-total-val">$<?= number_format((float)$runner['total_earnings'],2) ?></p>
          <p class="earnings-period"><?= (int)$runner['total_deliveries'] ?> deliveries completed</p>
          <div style="display:flex;gap:32px;margin-top:20px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.2);">
            <div><p style="font-size:0.75rem;opacity:0.75;">Available</p><p style="font-size:1.3rem;font-weight:800;">$<?= number_format((float)$runner['available_balance'],2) ?></p></div>
            <div><p style="font-size:0.75rem;opacity:0.75;">Pending</p><p style="font-size:1.3rem;font-weight:800;">$<?= number_format((float)$runner['pending_balance'],2) ?></p></div>
          </div>
        </div>

        <div class="earnings-breakdown-grid">
          <div class="earnings-breakdown-card"><p class="earnings-bd-label">Total Deliveries</p><p class="earnings-bd-val"><?= (int)$runner['total_deliveries'] ?></p></div>
          <div class="earnings-breakdown-card"><p class="earnings-bd-label">Rating</p><p class="earnings-bd-val">★ <?= number_format((float)$runner['rating_avg'],1) ?></p></div>
          <div class="earnings-breakdown-card"><p class="earnings-bd-label">Available</p><p class="earnings-bd-val">$<?= number_format((float)$runner['available_balance'],0) ?></p></div>
        </div>

        <div class="section-card">
          <h2 class="section-card-title">Recent Payouts</h2>
          <table class="data-table">
            <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($payouts as $p): ?>
                <tr>
                  <td><?= date('M d, Y', strtotime($p['requested_at'])) ?></td>
                  <td class="col-price">$<?= number_format((float)$p['amount'],2) ?></td>
                  <td><?= ucfirst($p['method']) ?></td>
                  <td><span class="status-badge status-<?= $p['status']==='completed'?'completed':'open' ?>"><?= ucfirst($p['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$payouts): ?><tr><td colspan="4" style="text-align:center;color:#888;padding:30px;">No payouts yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>