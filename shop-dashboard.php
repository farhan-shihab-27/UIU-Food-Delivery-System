<?php
require 'config.php';
require 'auth.php';
require_role('shop_owner');

$uid = $_SESSION['user_id'];
$shop = $pdo->query("SELECT * FROM shops WHERE owner_id=$uid")->fetch();
if (!$shop) die('Shop not found.');
$shopId = $shop['id'];

$todayOrders  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE shop_id=$shopId AND DATE(placed_at)=CURDATE()")->fetchColumn();
$todayRevenue = (float)$pdo->query("
    SELECT COALESCE(SUM(oi.subtotal), 0)
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.shop_id = $shopId
      AND o.status = 'delivered'
      AND DATE(o.placed_at) = CURDATE()
")->fetchColumn();
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE shop_id=$shopId AND status='pending'")->fetchColumn();

$recent = $pdo->query("SELECT o.*, u.full_name AS customer FROM orders o JOIN students st ON st.id=o.student_id JOIN users u ON u.id=st.user_id WHERE o.shop_id=$shopId ORDER BY o.placed_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Shop Dashboard</title>
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
        <a href="shop-dashboard.php"   class="sidebar-nav-item active"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="shop-orders.php"      class="sidebar-nav-item"><i class="fa-solid fa-receipt"></i> Orders</a>
        <a href="shop-menu.php"        class="sidebar-nav-item"><i class="fa-solid fa-utensils"></i> Menu</a>
        <a href="sales-history.php"    class="sidebar-nav-item"><i class="fa-solid fa-dollar-sign"></i> Sales</a>
        <a href="shop-reports.php"     class="sidebar-nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>

        <a href="notifications.php" class="sidebar-nav-item">
          <i class="fa-solid fa-bell"></i> Notifications
          <?php $__uc = unread_notification_count($pdo, $_SESSION['user_id']); if ($__uc > 0): ?>
            <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:99px;font-size:0.65rem;padding:1px 6px;font-weight:700;"><?= $__uc ?></span>
          <?php endif; ?>
        </a>

        <a href="profile.php"          class="sidebar-nav-item"><i class="fa-solid fa-user"></i> Profile</a>
      </nav>
      <div class="sidebar-user">
        <img src="<?= e(avatar_url()) ?>" alt="<?= e($_SESSION['name']) ?>" class="sidebar-user-avatar" />
        <div class="sidebar-user-info">
          <p class="sidebar-user-name"><?= e($_SESSION['name']) ?></p>
          <p class="sidebar-user-role">Shop Owner</p>
          <a href="logout.php" class="sidebar-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <div class="dash-topbar">
        <div class="dash-topbar-left"><h1 class="dash-topbar-title">Shop Dashboard</h1></div>
      </div>

      <div class="shop-body">
        <?php if ($shop['status'] === 'pending'): ?>
          <div style="background:#fff3e0;color:#e65100;padding:12px 16px;border-radius:8px;font-weight:600;margin-bottom:16px;">
            ⏳ Your shop is pending admin approval.
          </div>
        <?php elseif ($shop['status'] === 'rejected'): ?>
          <div style="background:#fdecea;color:#c62828;padding:12px 16px;border-radius:8px;font-weight:600;margin-bottom:16px;">
            ❌ Your shop was rejected. Note: <?= e($shop['rejection_note'] ?: 'N/A') ?>
          </div>
        <?php endif; ?>

        <div class="dash-stats-row">
          <div class="dash-stat-card"><p class="dash-stat-label">Today's Orders</p><p class="dash-stat-value"><?= $todayOrders ?></p></div>
          <div class="dash-stat-card"><p class="dash-stat-label">Today's Revenue</p><p class="dash-stat-value green">$<?= number_format($todayRevenue, 2) ?></p></div>
          <div class="dash-stat-card"><p class="dash-stat-label">Pending Orders</p><p class="dash-stat-value"><?= $pendingCount ?></p></div>
          <div class="dash-stat-card"><p class="dash-stat-label">Rating</p><p class="dash-stat-value">★ <?= number_format((float)$shop['rating_avg'], 1) ?></p></div>
        </div>

        <div class="section-card">
          <h2 class="section-card-title">Recent Customer Orders</h2>
          <table class="data-table">
            <thead><tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $o): ?>
                <tr>
                  <td class="col-id"><?= e($o['order_code']) ?></td>
                  <td><?= e($o['customer']) ?></td>
                  <td class="col-price">$<?= number_format((float)$o['total'],2) ?></td>
                  <td><span class="status-badge status-<?= $o['status']==='delivered'?'completed':($o['status']==='pending'?'pending':'preparing') ?>"><?= strtoupper($o['status']) ?></span></td>
                  <td><a href="shop-orders.php" class="tbl-btn-view-outline">Manage</a></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$recent): ?>
                <tr><td colspan="5" style="text-align:center;padding:30px;color:#888;">No orders yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>