<?php
require 'config.php';
require 'auth.php';
require_role('shop_owner');

$uid = $_SESSION['user_id'];
$shop = $pdo->query("SELECT * FROM shops WHERE owner_id=$uid")->fetch();
$shopId = $shop['id'];

/* Handle actions */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($oid) {
        $new = ['accept'=>'accepted', 'reject'=>'rejected', 'ready'=>'ready'][$action] ?? null;
        if ($new) {
            $pdo->prepare("UPDATE orders SET status=? WHERE id=? AND shop_id=?")->execute([$new, $oid, $shopId]);
            $pdo->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?,?,?)")->execute([$oid, $new, $uid]);

            /* Fetch the student's user_id + order code for notifications */
            $infoStmt = $pdo->prepare("
                SELECT o.order_code, u.id AS student_user_id
                FROM orders o
                JOIN students st ON st.id = o.student_id
                JOIN users    u  ON u.id = st.user_id
                WHERE o.id = ? AND o.shop_id = ?
            ");
            $infoStmt->execute([$oid, $shopId]);
            $info = $infoStmt->fetch();

            if ($info) {
                if ($new === 'accepted') {
                    notify($pdo, $info['student_user_id'],
                        'Order Accepted',
                        $shop['name'] . ' accepted your order ' . $info['order_code'],
                        'order', 'track-order.php?order_id=' . $oid);
                } elseif ($new === 'ready') {
                    notify($pdo, $info['student_user_id'],
                        'Order Ready for Pickup',
                        'Your order ' . $info['order_code'] . ' is ready — a runner will pick it up soon.',
                        'order', 'track-order.php?order_id=' . $oid);

                    /* Notify all approved + online-ish runners that a new job exists */
                    $runners = $pdo->query("SELECT user_id FROM runners WHERE status='approved'")->fetchAll();
                    foreach ($runners as $r) {
                        notify($pdo, $r['user_id'],
                            'New Delivery Available',
                            'Order at ' . $shop['name'] . ' is ready for pickup (+$3.50)',
                            'order', 'runner-dashboard.php');
                    }
                } elseif ($new === 'rejected') {
                    notify($pdo, $info['student_user_id'],
                        'Order Rejected',
                        'Unfortunately ' . $shop['name'] . ' rejected your order ' . $info['order_code'] . '.',
                        'order', 'order-history.php');
                }
            }
        }
    }
    header('Location: shop-orders.php'); exit;
}

$orders = $pdo->query("SELECT o.*, u.full_name AS customer FROM orders o JOIN students st ON st.id=o.student_id JOIN users u ON u.id=st.user_id WHERE o.shop_id=$shopId ORDER BY o.placed_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Shop Orders</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-logo"><div class="sidebar-logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg></div><span class="sidebar-logo-text">UIU Food Portal</span></div>
      <nav class="sidebar-nav">
        <a href="shop-dashboard.php" class="sidebar-nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="shop-orders.php"    class="sidebar-nav-item active"><i class="fa-solid fa-receipt"></i> Orders</a>
        <a href="shop-menu.php"      class="sidebar-nav-item"><i class="fa-solid fa-utensils"></i> Menu</a>
        <a href="sales-history.php"  class="sidebar-nav-item"><i class="fa-solid fa-dollar-sign"></i> Sales</a>
        <a href="shop-reports.php"   class="sidebar-nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>

        <!-- ========== NOTIFICATIONS BELL ========== -->
        <a href="notifications.php" class="sidebar-nav-item">
          <i class="fa-solid fa-bell"></i> Notifications
          <?php $__uc = unread_notification_count($pdo, $_SESSION['user_id']); if ($__uc > 0): ?>
            <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:99px;font-size:0.65rem;padding:1px 6px;font-weight:700;"><?= $__uc ?></span>
          <?php endif; ?>
        </a>
        <!-- ========================================= -->

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
      <div class="dash-topbar"><div class="dash-topbar-left"><h1 class="dash-topbar-title">Orders</h1></div></div>
      <div class="shop-body">
        <div class="section-card">
          <h2 class="section-card-title">All Orders</h2>
          <table class="data-table">
            <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <td class="col-id"><?= e($o['order_code']) ?></td>
                  <td><?= e($o['customer']) ?></td>
                  <td class="col-price">$<?= number_format((float)$o['total'],2) ?></td>
                  <td><span class="status-badge status-<?= $o['status']==='pending'?'pending':($o['status']==='delivered'?'completed':'preparing') ?>"><?= strtoupper($o['status']) ?></span></td>
                  <td style="display:flex;gap:6px;">
                    <?php if ($o['status'] === 'pending'): ?>
                      <form method="post" style="display:inline;"><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="action" value="accept"><button class="tbl-btn-accept">Accept</button></form>
                      <form method="post" style="display:inline;"><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="action" value="reject"><button class="tbl-btn-reject">Reject</button></form>
                    <?php elseif (in_array($o['status'], ['accepted','preparing'])): ?>
                      <form method="post" style="display:inline;"><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="action" value="ready"><button class="tbl-btn-pickup">Ready for Pickup</button></form>
                    <?php else: ?>
                      <span class="tbl-no-action">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>