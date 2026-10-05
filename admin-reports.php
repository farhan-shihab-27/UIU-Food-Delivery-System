<?php
require 'config.php';
require 'auth.php';
require_role('admin');

$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered'")->fetchColumn();
$totalOrders  = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalRunners = (int)$pdo->query("SELECT COUNT(*) FROM runners WHERE status='approved'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Platform Reports</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-logo"><div class="sidebar-logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg></div><span class="sidebar-logo-text">UIU Food Portal</span></div>
      <nav class="sidebar-nav">
        <a href="admin-dashboard.php"         class="sidebar-nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="admin-shop-approvals.php"    class="sidebar-nav-item"><i class="fa-solid fa-store"></i> Shop Approvals</a>
        <a href="admin-runner-approvals.php"  class="sidebar-nav-item"><i class="fa-solid fa-bicycle"></i> Runner Approvals</a>
        <a href="admin-reports.php"           class="sidebar-nav-item active"><i class="fa-solid fa-chart-bar"></i> Reports</a>
        <a href="complaint-management.php"    class="sidebar-nav-item"><i class="fa-solid fa-comment"></i> Complaints</a>

        <a href="notifications.php" class="sidebar-nav-item">
          <i class="fa-solid fa-bell"></i> Notifications
          <?php $__uc = unread_notification_count($pdo, $_SESSION['user_id']); if ($__uc > 0): ?>
            <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:99px;font-size:0.65rem;padding:1px 6px;font-weight:700;"><?= $__uc ?></span>
          <?php endif; ?>
        </a>

        <a href="profile.php"                 class="sidebar-nav-item"><i class="fa-solid fa-user"></i> Profile</a>
      </nav>
      <div class="sidebar-user">
        <img src="<?= e(avatar_url()) ?>" alt="<?= e($_SESSION['name']) ?>" class="sidebar-user-avatar" />
        <div class="sidebar-user-info">
          <p class="sidebar-user-name"><?= e($_SESSION['name']) ?></p>
          <p class="sidebar-user-role">Administrator</p>
          <a href="logout.php" class="sidebar-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <div class="dash-topbar"><div class="dash-topbar-left"><h1 class="dash-topbar-title">Platform Reports</h1></div></div>

      <div class="reports-body" style="padding:24px 32px;">
        <div class="reports-kpi-row">
          <div class="kpi-card"><div class="kpi-icon-wrap">$</div><div class="kpi-label">Total Revenue</div><div class="kpi-value">$<?= number_format($totalRevenue, 0) ?></div></div>
          <div class="kpi-card"><div class="kpi-icon-wrap"><i class="fa-solid fa-bag-shopping"></i></div><div class="kpi-label">Total Orders</div><div class="kpi-value"><?= $totalOrders ?></div></div>
          <div class="kpi-card"><div class="kpi-icon-wrap"><i class="fa-solid fa-users"></i></div><div class="kpi-label">Total Users</div><div class="kpi-value"><?= $totalUsers ?></div></div>
          <div class="kpi-card"><div class="kpi-icon-wrap"><i class="fa-solid fa-bicycle"></i></div><div class="kpi-label">Active Runners</div><div class="kpi-value"><?= $totalRunners ?></div></div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>