<?php
require 'config.php';
require 'auth.php';
require_role('shop_owner');

$uid = $_SESSION['user_id'];
$shop = $pdo->query("SELECT * FROM shops WHERE owner_id=$uid")->fetch();
$shopId = $shop['id'];

$totalRevenue = (float)$pdo->query("
    SELECT COALESCE(SUM(oi.subtotal), 0)
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.shop_id = $shopId
      AND o.status = 'delivered'
")->fetchColumn();

$totalOrders  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE shop_id=$shopId")->fetchColumn();
$avgRating    = (float)$shop['rating_avg'];

$byCat = $pdo->query("
    SELECT mi.category, SUM(oi.subtotal) AS rev
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN menu_items mi ON mi.id = oi.menu_item_id
    WHERE o.shop_id = $shopId
      AND o.status = 'delivered'
    GROUP BY mi.category
    ORDER BY rev DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Shop Reports</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-logo"><div class="sidebar-logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg></div><span class="sidebar-logo-text">UIU Food Portal</span></div>
      <nav class="sidebar-nav">
        <a href="shop-dashboard.php" class="sidebar-nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="shop-orders.php"    class="sidebar-nav-item"><i class="fa-solid fa-receipt"></i> Orders</a>
        <a href="shop-menu.php"      class="sidebar-nav-item"><i class="fa-solid fa-utensils"></i> Menu</a>
        <a href="sales-history.php"  class="sidebar-nav-item"><i class="fa-solid fa-dollar-sign"></i> Sales</a>
        <a href="shop-reports.php"   class="sidebar-nav-item active"><i class="fa-solid fa-chart-bar"></i> Reports</a>

        <a href="notifications.php" class="sidebar-nav-item">
          <i class="fa-solid fa-bell"></i> Notifications
          <?php $__uc = unread_notification_count($pdo, $_SESSION['user_id']); if ($__uc > 0): ?>
            <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:99px;font-size:0.65rem;padding:1px 6px;font-weight:700;"><?= $__uc ?></span>
          <?php endif; ?>
        </a>

        <a href="profile.php"        class="sidebar-nav-item"><i class="fa-solid fa-user"></i> Profile</a>
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
      <div class="dash-topbar"><div class="dash-topbar-left"><h1 class="dash-topbar-title">Business Reports</h1></div></div>

      <div class="shop-body">
        <div class="dash-stats-row">
          <div class="dash-stat-card"><p class="dash-stat-label">Total Revenue</p><p class="dash-stat-value">$<?= number_format($totalRevenue, 2) ?></p></div>
          <div class="dash-stat-card"><p class="dash-stat-label">Total Orders</p><p class="dash-stat-value"><?= $totalOrders ?></p></div>
          <div class="dash-stat-card"><p class="dash-stat-label">Avg Rating</p><p class="dash-stat-value"><?= number_format($avgRating, 1) ?></p></div>
          <div class="dash-stat-card"><p class="dash-stat-label">Reviews</p><p class="dash-stat-value"><?= (int)$shop['rating_count'] ?></p></div>
        </div>

        <div class="section-card">
          <h2 class="section-card-title">Revenue by Category</h2>
          <?php foreach ($byCat as $c): ?>
            <div style="display:flex;align-items:center;gap:16px;margin:10px 0;">
              <span style="width:90px;font-size:0.8rem;color:#666;text-align:right;"><?= e(ucfirst($c['category'])) ?></span>
              <div style="flex:1;background:#f0f0f0;height:16px;border-radius:99px;overflow:hidden;">
                <div style="width:<?= $totalRevenue > 0 ? min(100, round($c['rev']/$totalRevenue*100)) : 0 ?>%;height:100%;background:var(--color-primary);border-radius:99px;"></div>
              </div>
              <span style="width:70px;font-size:0.85rem;font-weight:700;">$<?= number_format((float)$c['rev'], 2) ?></span>
            </div>
          <?php endforeach; ?>
          <?php if (!$byCat): ?><p style="color:#888;">No data yet.</p><?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</body>
</html>