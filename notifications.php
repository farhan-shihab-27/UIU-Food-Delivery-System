<?php
require 'config.php';
require 'auth.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$u   = $pdo->query("SELECT * FROM users WHERE id=$uid")->fetch();
if (!$u) { header('Location: logout.php'); exit; }

/* Mark all as read */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$uid]);
    header('Location: notifications.php'); exit;
}

/* Click-through: mark single as read, then redirect */
$readId = (int)($_GET['read'] ?? 0);
if ($readId) {
    $s = $pdo->prepare("SELECT link_url FROM notifications WHERE id = ? AND user_id = ?");
    $s->execute([$readId, $uid]);
    $row = $s->fetch();
    if ($row) {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")
            ->execute([$readId, $uid]);
        if (!empty($row['link_url'])) { header('Location: ' . $row['link_url']); exit; }
    }
    header('Location: notifications.php'); exit;
}

/* Fetch notifications */
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
$stmt->execute([$uid]);
$notifs = $stmt->fetchAll();

$unread = 0;
foreach ($notifs as $n) if (!$n['is_read']) $unread++;

/* Icon + colour by type */
function notif_icon($type) {
    switch ($type) {
        case 'order':     return ['fa-box',                  '#1f7a3e'];
        case 'promo':     return ['fa-tag',                  '#e67e22'];
        case 'complaint': return ['fa-triangle-exclamation', '#e74c3c'];
        default:          return ['fa-circle-info',          '#2980b9'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Notifications</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">

    <aside class="sidebar">
      <div class="sidebar-logo">
        <div class="sidebar-logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg></div>
        <span class="sidebar-logo-text"><?= $_SESSION['role'] === 'runner' ? 'UIU Runner Portal' : 'UIU Food Portal' ?></span>
      </div>
      <nav class="sidebar-nav">
        <a href="<?= redirect_by_role($u['role']) ?>" class="sidebar-nav-item"><i class="fa-solid fa-house"></i> Home</a>

        <?php if ($u['role'] === 'student'): ?>
          <a href="order-history.php" class="sidebar-nav-item"><i class="fa-solid fa-receipt"></i> My Orders</a>
          <a href="wallet.php"        class="sidebar-nav-item"><i class="fa-solid fa-wallet"></i> Wallet</a>
          <a href="chat.php"          class="sidebar-nav-item"><i class="fa-solid fa-comment"></i> Messages</a>
        <?php elseif ($u['role'] === 'shop_owner'): ?>
          <a href="shop-orders.php"   class="sidebar-nav-item"><i class="fa-solid fa-receipt"></i> Orders</a>
          <a href="shop-menu.php"     class="sidebar-nav-item"><i class="fa-solid fa-utensils"></i> Menu</a>
          <a href="sales-history.php" class="sidebar-nav-item"><i class="fa-solid fa-dollar-sign"></i> Sales</a>
          <a href="shop-reports.php"  class="sidebar-nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>
        <?php elseif ($u['role'] === 'runner'): ?>
          <a href="runner-dashboard.php"  class="sidebar-nav-item"><i class="fa-solid fa-bicycle"></i> Available Jobs</a>
          <a href="runner-deliveries.php" class="sidebar-nav-item"><i class="fa-solid fa-clipboard-list"></i> My Deliveries</a>
          <a href="runner-earnings.php"   class="sidebar-nav-item"><i class="fa-solid fa-wallet"></i> Earnings</a>
          <a href="chat.php"              class="sidebar-nav-item"><i class="fa-solid fa-comment"></i> Messages</a>
        <?php elseif ($u['role'] === 'admin'): ?>
          <a href="admin-dashboard.php"        class="sidebar-nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
          <a href="admin-shop-approvals.php"   class="sidebar-nav-item"><i class="fa-solid fa-store"></i> Shop Approvals</a>
          <a href="admin-runner-approvals.php" class="sidebar-nav-item"><i class="fa-solid fa-bicycle"></i> Runner Approvals</a>
          <a href="admin-reports.php"          class="sidebar-nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>
          <a href="complaint-management.php"   class="sidebar-nav-item"><i class="fa-solid fa-comment"></i> Complaints</a>
        <?php endif; ?>

        <a href="notifications.php" class="sidebar-nav-item active">
          <i class="fa-solid fa-bell"></i> Notifications
          <?php if ($unread > 0): ?>
            <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:99px;font-size:0.65rem;padding:1px 6px;font-weight:700;"><?= $unread ?></span>
          <?php endif; ?>
        </a>
        <a href="profile.php" class="sidebar-nav-item"><i class="fa-solid fa-user"></i> Profile</a>
      </nav>
      <div class="sidebar-user">
        <img src="<?= e(avatar_url()) ?>" alt="<?= e($_SESSION['name']) ?>" class="sidebar-user-avatar" />
        <div class="sidebar-user-info">
          <p class="sidebar-user-name"><?= e($_SESSION['name']) ?></p>
          <p class="sidebar-user-role"><?= ucfirst(str_replace('_',' ', $u['role'])) ?></p>
          <a href="logout.php" class="sidebar-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <div class="page-header">
        <div class="page-header-left">
          <h1 class="page-title">Notifications</h1>
          <p class="page-subtitle"><?= $unread ?> unread alert<?= $unread === 1 ? '' : 's' ?></p>
        </div>
        <?php if ($unread > 0): ?>
          <form method="post" style="margin:0;">
            <input type="hidden" name="mark_all" value="1">
            <button type="submit" class="btn btn-outline btn-sm">
              <i class="fa-solid fa-check-double" style="margin-right:5px;"></i>Mark all as read
            </button>
          </form>
        <?php endif; ?>
      </div>

      <div class="page-body" style="max-width:840px;">
        <?php if (!$notifs): ?>
          <div class="section-card" style="text-align:center;padding:60px 20px;">
            <i class="fa-regular fa-bell" style="font-size:2.5rem;color:#ccc;display:block;margin-bottom:14px;"></i>
            <p style="color:#888;font-size:0.95rem;font-weight:600;margin-bottom:4px;">No notifications yet</p>
            <p style="color:#aaa;font-size:0.82rem;">You'll see order updates and system alerts here.</p>
          </div>
        <?php else: ?>
          <?php foreach ($notifs as $n):
            list($icon, $color) = notif_icon($n['type']);
            $isUnread = !$n['is_read'];
          ?>
            <a href="notifications.php?read=<?= (int)$n['id'] ?>"
               class="section-card"
               style="display:flex;gap:14px;align-items:flex-start;
                      text-decoration:none;margin-bottom:10px;padding:16px 20px;
                      <?= $isUnread ? 'border-left:4px solid var(--color-primary);' : 'opacity:0.75;' ?>">
              <div style="width:40px;height:40px;border-radius:50%;
                          background:<?= $color ?>15;color:<?= $color ?>;
                          display:flex;align-items:center;justify-content:center;
                          flex-shrink:0;font-size:0.95rem;">
                <i class="fa-solid <?= $icon ?>"></i>
              </div>
              <div style="flex:1;min-width:0;">
                <p style="font-size:0.9rem;font-weight:<?= $isUnread ? '700' : '600' ?>;
                          color:var(--color-text-primary);margin-bottom:2px;">
                  <?= e($n['title']) ?>
                  <?php if ($isUnread): ?>
                    <span style="display:inline-block;width:8px;height:8px;background:#e74c3c;border-radius:50%;margin-left:6px;vertical-align:middle;"></span>
                  <?php endif; ?>
                </p>
                <?php if (!empty($n['message'])): ?>
                  <p style="font-size:0.82rem;color:var(--color-text-secondary);margin-bottom:4px;line-height:1.5;">
                    <?= e($n['message']) ?>
                  </p>
                <?php endif; ?>
                <p style="font-size:0.72rem;color:var(--color-text-muted);">
                  <i class="fa-regular fa-clock" style="margin-right:4px;"></i>
                  <?= date('M d, Y • g:i A', strtotime($n['created_at'])) ?>
                </p>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>