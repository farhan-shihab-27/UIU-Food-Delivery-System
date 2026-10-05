<?php
require 'config.php';
require 'auth.php';
require_role('student');

$uid = $_SESSION['user_id'];
$me = $pdo->query("SELECT id FROM students WHERE user_id=$uid")->fetch();
$sid = $me['id'];

$filter = $_GET['filter'] ?? 'all';
$where = "WHERE o.student_id = $sid";
if ($filter === 'completed')  $where .= " AND o.status='delivered'";
if ($filter === 'inprogress') $where .= " AND o.status IN ('pending','accepted','preparing','ready','picked_up','on_the_way')";
if ($filter === 'cancelled')  $where .= " AND o.status IN ('cancelled','rejected')";

$orders = $pdo->query("SELECT o.*, s.name AS shop_name, s.image_url AS shop_image FROM orders o JOIN shops s ON s.id=o.shop_id $where ORDER BY o.placed_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Orders</title>
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
        <a href="order-history.php"       class="sidebar-nav-item active"><i class="fa-solid fa-receipt"></i> My Orders</a>
        <a href="wallet.php"              class="sidebar-nav-item"><i class="fa-solid fa-wallet"></i> Wallet</a>
        <a href="chat.php"                class="sidebar-nav-item"><i class="fa-solid fa-comment"></i> Messages</a>
        <a href="submit-complaint.php"    class="sidebar-nav-item"><i class="fa-solid fa-triangle-exclamation"></i> Complaints</a>

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
      <div class="page-header"><div class="page-header-left"><h1 class="page-title">Order History</h1></div></div>

      <div class="order-history-layout">
        <div class="orders-panel">
          <div class="order-tabs">
            <a href="?filter=all"        class="order-tab <?= $filter==='all'?'active':'' ?>">All Orders</a>
            <a href="?filter=completed"  class="order-tab <?= $filter==='completed'?'active':'' ?>">Completed</a>
            <a href="?filter=inprogress" class="order-tab <?= $filter==='inprogress'?'active':'' ?>">In Progress</a>
            <a href="?filter=cancelled"  class="order-tab <?= $filter==='cancelled'?'active':'' ?>">Cancelled</a>
          </div>

          <?php if (!$orders): ?>
            <div style="text-align:center;padding:60px 20px;">
              <h3 style="color:#888;margin-bottom:12px;">No orders found</h3>
              <a href="student-dashboard.php" class="btn btn-primary">Browse Restaurants</a>
            </div>
          <?php else: foreach ($orders as $o):
            $its = $pdo->prepare("SELECT item_name, quantity FROM order_items WHERE order_id=?");
            $its->execute([$o['id']]); $its = $its->fetchAll();
            $summary = [];
            foreach ($its as $i) $summary[] = $i['quantity'] . 'x ' . $i['item_name'];

            $canComplain = !in_array($o['status'], ['cancelled','rejected'], true);
          ?>
            <div class="order-item-card">
              <div class="order-item-header">
                <img src="<?= e($o['shop_image'] ?: 'https://images.unsplash.com/photo-1603360946369-dc9bb6258143?w=120&q=80') ?>"
                     alt="<?= e($o['shop_name']) ?>"
                     class="order-item-img" />
                <div class="order-item-info">
                  <p class="order-item-name"><?= e($o['shop_name']) ?></p>
                  <p class="order-item-meta"><?= date('M d, Y', strtotime($o['placed_at'])) ?> • Order <?= e($o['order_code']) ?></p>
                </div>
                <div class="order-item-status-wrap">
                  <span class="badge badge-<?= $o['status']==='delivered'?'success':($o['status']==='cancelled'?'danger':'warning') ?>"><?= ucfirst(str_replace('_',' ',$o['status'])) ?></span>
                </div>
              </div>
              <p class="order-item-dishes"><?= e(implode(', ', $summary)) ?></p>
              <div class="order-item-footer">
                <span class="order-item-price">$<?= number_format((float)$o['total'],2) ?></span>
                <div class="order-item-actions">
                  <a href="track-order.php?order_id=<?= (int)$o['id'] ?>" class="order-action-btn order-action-btn-outline">Track Order</a>
                  <?php if ($o['status'] === 'delivered'): ?>
                    <a href="rate-order.php?order_id=<?= (int)$o['id'] ?>" class="order-action-btn order-action-btn-filled">Rate Order</a>
                  <?php endif; ?>
                  <?php if ($canComplain): ?>
                    <a href="submit-complaint.php?order_id=<?= (int)$o['id'] ?>"
                       class="order-action-btn order-action-btn-outline"
                       style="border-color:#e74c3c;color:#e74c3c;">
                      <i class="fa-solid fa-triangle-exclamation" style="margin-right:4px;font-size:0.7rem;"></i>Complain
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>

        <div class="order-details-panel">
          <div class="order-details-header">
            <h2 class="order-details-title">Order Details</h2>
          </div>
          <div style="padding:20px;text-align:center;color:var(--color-text-muted);">
            <i class="fa-solid fa-receipt" style="font-size:2rem;opacity:0.3;margin-bottom:12px;display:block;"></i>
            <p style="font-size:0.85rem;">Select an order to view details</p>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>