<?php
require 'config.php';
require 'auth.php';
require_role('shop_owner');

$uid = $_SESSION['user_id'];
$shop = $pdo->query("SELECT * FROM shops WHERE owner_id=$uid")->fetch();
$shopId = $shop['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle'])) {
        $pdo->prepare("UPDATE menu_items SET is_available = 1 - is_available WHERE id=? AND shop_id=?")->execute([(int)$_POST['toggle'], $shopId]);
    } elseif (isset($_POST['delete'])) {
        $pdo->prepare("DELETE FROM menu_items WHERE id=? AND shop_id=?")->execute([(int)$_POST['delete'], $shopId]);
    }
    header('Location: shop-menu.php'); exit;
}

$items = $pdo->query("SELECT * FROM menu_items WHERE shop_id=$shopId ORDER BY category, name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Menu Management</title>
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
        <a href="shop-menu.php"      class="sidebar-nav-item active"><i class="fa-solid fa-utensils"></i> Menu</a>
        <a href="sales-history.php"  class="sidebar-nav-item"><i class="fa-solid fa-dollar-sign"></i> Sales</a>
        <a href="shop-reports.php"   class="sidebar-nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>

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
      <div class="dash-topbar">
        <div class="dash-topbar-left"><h1 class="dash-topbar-title">Menu Management</h1></div>
        <a href="shop-add-item.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add New Item</a>
      </div>

      <div class="shop-body">
        <div class="shop-menu-grid">
          <?php foreach ($items as $it): ?>
            <div class="menu-mgmt-card">
              <div class="menu-mgmt-img"><img src="<?= e($it['image_url'] ?: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&q=80') ?>" alt="<?= e($it['name']) ?>" /></div>
              <div class="menu-mgmt-controls">
                <div style="flex:1;">
                  <p style="font-size:0.9rem;font-weight:700;"><?= e($it['name']) ?></p>
                  <p style="font-size:0.75rem;color:#666;">
                    <?= ucfirst($it['category']) ?> •
                    <span style="color:var(--color-primary);font-weight:600;">$<?= number_format((float)$it['price'],2) ?></span> •
                    Stock: <strong><?= (int)$it['stock_quantity'] ?></strong>
                  </p>
                </div>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="toggle" value="<?= (int)$it['id'] ?>" />
                  <button class="btn btn-<?= $it['is_available']?'outline':'danger' ?> btn-sm" type="submit">
                    <?= $it['is_available'] ? 'Available' : 'Sold Out' ?>
                  </button>
                </form>
              </div>
              <form method="post" style="padding:0 16px 16px;" onsubmit="return confirm('Delete this item?');">
                <input type="hidden" name="delete" value="<?= (int)$it['id'] ?>" />
                <button type="submit" class="tbl-btn-reject" style="width:100%;text-align:center;">Delete</button>
              </form>
            </div>
          <?php endforeach; ?>
          <?php if (!$items): ?><p style="color:#888;">No menu items yet.</p><?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</body>
</html>