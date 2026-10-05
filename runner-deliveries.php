<?php
require 'config.php';
require 'auth.php';
require_role('runner');

$uid = $_SESSION['user_id'];
$runner = $pdo->query("SELECT * FROM runners WHERE user_id=$uid")->fetch();
$rid = $runner['id'];

$stmt = $pdo->prepare("SELECT o.*, s.name AS shop_name, st.default_address FROM orders o JOIN shops s ON s.id=o.shop_id JOIN students st ON st.id=o.student_id WHERE o.runner_id=? ORDER BY o.delivered_at DESC, o.placed_at DESC LIMIT 50");
$stmt->execute([$rid]); $deliveries = $stmt->fetchAll();

$today = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE runner_id=$rid AND status='delivered' AND DATE(delivered_at)=CURDATE()")->fetchColumn();
$week  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE runner_id=$rid AND status='delivered' AND YEARWEEK(delivered_at)=YEARWEEK(NOW())")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Deliveries</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-logo"><div class="sidebar-logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg></div><span class="sidebar-logo-text">UIU Runner Portal</span></div>
      <nav class="sidebar-nav">
        <a href="runner-dashboard.php"  class="sidebar-nav-item"><i class="fa-solid fa-bicycle"></i> Available Jobs</a>
        <a href="runner-deliveries.php" class="sidebar-nav-item active"><i class="fa-solid fa-clipboard-list"></i> My Deliveries</a>
        <a href="runner-earnings.php"   class="sidebar-nav-item"><i class="fa-solid fa-wallet"></i> Earnings</a>
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
      <div class="runner-topbar"><h1 class="runner-topbar-title">My Deliveries</h1></div>

      <div style="padding:24px 32px;">
        <div class="deliveries-stats-row">
          <div class="runner-stat-card"><p class="runner-stat-label">Today</p><p class="runner-stat-value"><?= $today ?></p><p class="runner-stat-sub">deliveries</p></div>
          <div class="runner-stat-card"><p class="runner-stat-label">This Week</p><p class="runner-stat-value"><?= $week ?></p><p class="runner-stat-sub">deliveries</p></div>
          <div class="runner-stat-card"><p class="runner-stat-label">Total Rating</p><p class="runner-stat-value">★ <?= number_format((float)$runner['rating_avg'],1) ?></p></div>
        </div>

        <h2 class="runner-section-title">Delivery History</h2>
        <?php foreach ($deliveries as $d): ?>
          <div class="delivery-history-card">
            <div>
              <p class="delivery-h-id"><?= e($d['order_code']) ?></p>
              <p class="delivery-h-name"><?= e($d['shop_name']) ?> → <?= e($d['default_address']) ?></p>
            </div>
            <div style="text-align:right;">
              <p class="delivery-h-earn">+$3.50</p>
              <p class="delivery-h-date"><?= $d['delivered_at'] ? date('M d • g:i A', strtotime($d['delivered_at'])) : '—' ?></p>
              <span class="status-badge status-<?= $d['status']==='delivered'?'completed':'open' ?>" style="margin-top:4px;display:inline-block;"><?= ucfirst(str_replace('_',' ',$d['status'])) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$deliveries): ?><p style="color:#888;">No deliveries yet.</p><?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>