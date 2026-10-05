<?php
require 'config.php';
require 'auth.php';
require_role('student');

$uid  = $_SESSION['user_id'];
$u    = $pdo->prepare("SELECT u.*, s.id AS sid, s.wallet_balance, s.default_address FROM users u JOIN students s ON s.user_id=u.id WHERE u.id=?");
$u->execute([$uid]); $me = $u->fetch();

$shops  = $pdo->query("SELECT * FROM shops WHERE status='approved' ORDER BY rating_avg DESC LIMIT 4")->fetchAll();

$orders = $pdo->prepare("SELECT o.*, sh.name AS shop_name FROM orders o JOIN shops sh ON sh.id=o.shop_id WHERE o.student_id=? ORDER BY o.placed_at DESC LIMIT 4");
$orders->execute([$me['sid']]); $orders = $orders->fetchAll();

$recs = $pdo->query("SELECT * FROM menu_items WHERE is_available=1 ORDER BY RAND() LIMIT 3")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UIU Food Portal — Dashboard</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-logo">
        <div class="sidebar-logo-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
            stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg>
        </div>
        <span class="sidebar-logo-text">UIU Food Portal</span>
      </div>
      <nav class="sidebar-nav">
        <a href="student-dashboard.php" class="sidebar-nav-item active"><i class="fa-solid fa-house"></i> Home</a>
        <a href="order-history.php"       class="sidebar-nav-item"><i class="fa-solid fa-receipt"></i> My Orders</a>
        <a href="wallet.php"              class="sidebar-nav-item"><i class="fa-solid fa-wallet"></i> Wallet</a>
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
          <p class="sidebar-user-name"><?= e($me['full_name']) ?></p>
          <p class="sidebar-user-role">Student Account</p>
          <a href="logout.php" class="sidebar-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <div class="dashboard-topbar">
        <a href="index.php" class="topbar-back">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12" /><polyline points="12 19 5 12 12 5" /></svg>
          Back
        </a>
        <div class="topbar-search-wrap">
          <span class="search-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></svg></span>
          <input type="text" class="topbar-search-input" placeholder="Search for restaurant, food or order..." />
        </div>
      </div>

      <h1 class="dashboard-page-title">Dashboard</h1>

      <div class="dashboard-hero">
        <div class="dashboard-hero-content">
          <h2 class="dashboard-hero-title">Welcome back, <?= e(explode(' ', $me['full_name'])[0]) ?>!</h2>
          <p class="dashboard-hero-desc">Hungry between classes? Fellow students are ready to deliver hot food straight to you.</p>
          <p style="color:#fff;font-weight:700;margin-top:12px;font-size:1rem;">
            Wallet Balance: $<?= number_format((float)$me['wallet_balance'], 2) ?>
          </p>
        </div>
        <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=320&q=80&auto=format&fit=crop" alt="Delivery" class="dashboard-hero-img" />
      </div>

      <div class="dashboard-body">
        <div class="db-section-header" style="margin-top: var(--space-6);">
          <h2 class="db-section-title">Nearby Restaurants</h2>
          <a href="#allshops" class="db-section-link">View All</a>
        </div>
        <div class="db-restaurants-grid">
          <?php foreach ($shops as $s): ?>
            <a href="restaurant-menu.php?shop_id=<?= (int)$s['id'] ?>" class="db-restaurant-card">
              <div class="db-restaurant-img">
                <img src="<?= e($s['image_url'] ?: 'https://images.unsplash.com/photo-1552566626-52f8b828add9?w=400&q=80') ?>" alt="<?= e($s['name']) ?>" />
              </div>
              <div class="db-restaurant-body">
                <p class="db-restaurant-name"><?= e($s['name']) ?></p>
                <div class="db-restaurant-meta"><span class="rating">★ <?= number_format((float)$s['rating_avg'], 1) ?></span></div>
                <div class="db-view-menu-btn">View Menu</div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>

        <div class="db-bottom-grid">
          <div>
            <div class="db-section-header"><h2 class="db-section-title">Recent Orders</h2></div>
            <?php if ($orders): foreach ($orders as $o): ?>
              <div class="recent-order-card">
                <div class="recent-order-header">
                  <div>
                    <p class="recent-order-name"><?= e($o['shop_name']) ?></p>
                    <p class="recent-order-items">Order <?= e($o['order_code']) ?></p>
                    <p class="recent-order-time"><?= date('M d, Y • g:i A', strtotime($o['placed_at'])) ?></p>
                  </div>
                  <span class="badge badge-primary"><?= ucfirst(str_replace('_',' ', $o['status'])) ?></span>
                </div>
                <div class="recent-order-footer">
                  <span class="recent-order-total">Total: $<?= number_format((float)$o['total'],2) ?></span>
                  <a href="track-order.php?order_id=<?= (int)$o['id'] ?>" class="btn btn-outline btn-sm">Track</a>
                </div>
              </div>
            <?php endforeach; else: ?>
              <p style="color:#888;">No orders yet.</p>
            <?php endif; ?>
          </div>

          <div>
            <div class="db-section-header"><h2 class="db-section-title">Recommended for You</h2></div>
            <?php foreach ($recs as $r): ?>
              <div class="recommended-item">
                <img src="<?= e($r['image_url'] ?: 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?w=120&q=80') ?>" alt="<?= e($r['name']) ?>" class="recommended-img" />
                <div class="recommended-info">
                  <p class="recommended-name"><?= e($r['name']) ?></p>
                  <div class="recommended-meta">
                    <span class="price">$<?= number_format((float)$r['price'],2) ?></span>
                  </div>
                </div>
                <a href="restaurant-menu.php?shop_id=<?= (int)$r['shop_id'] ?>" class="add-btn-round">+</a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>