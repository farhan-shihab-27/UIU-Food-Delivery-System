<?php
require 'config.php';
require 'auth.php';
require_role('admin');

$stats = [
    'students' => (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'shops'    => (int)$pdo->query("SELECT COUNT(*) FROM shops WHERE status='approved'")->fetchColumn(),
    'runners'  => (int)$pdo->query("SELECT COUNT(*) FROM runners WHERE status='approved'")->fetchColumn(),
    'orders'   => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
];

$pendingShops   = $pdo->query("SELECT s.*, u.full_name FROM shops s JOIN users u ON u.id=s.owner_id WHERE s.status='pending' LIMIT 5")->fetchAll();
$pendingRunners = $pdo->query("SELECT r.*, u.full_name FROM runners r JOIN users u ON u.id=r.user_id WHERE r.status='pending' LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Control Center</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-logo"><div class="sidebar-logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg></div><span class="sidebar-logo-text">UIU Food Portal</span></div>
      <nav class="sidebar-nav">
        <a href="admin-dashboard.php"         class="sidebar-nav-item active"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="admin-shop-approvals.php"    class="sidebar-nav-item"><i class="fa-solid fa-store"></i> Shop Approvals</a>
        <a href="admin-runner-approvals.php"  class="sidebar-nav-item"><i class="fa-solid fa-bicycle"></i> Runner Approvals</a>
        <a href="admin-reports.php"           class="sidebar-nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>
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
      <div class="dash-topbar">
        <div class="dash-topbar-left"><h1 class="dash-topbar-title">Admin Control Center</h1></div>
      </div>

      <div class="admin-body">
        <div class="dash-stats-row">
          <div class="dash-stat-card"><p class="dash-stat-label">Total Students</p><p class="dash-stat-value"><?= $stats['students'] ?></p></div>
          <div class="dash-stat-card"><p class="dash-stat-label">Approved Shops</p><p class="dash-stat-value"><?= $stats['shops'] ?></p></div>
          <div class="dash-stat-card"><p class="dash-stat-label">Active Runners</p><p class="dash-stat-value"><?= $stats['runners'] ?></p></div>
          <div class="dash-stat-card"><p class="dash-stat-label">Total Orders</p><p class="dash-stat-value"><?= $stats['orders'] ?></p></div>
        </div>

        <div class="approval-tables-row">
          <div class="section-card" style="margin-bottom:0;">
            <h2 class="section-card-title">Pending Shop Approvals</h2>
            <table class="data-table">
              <thead><tr><th>Thumbnail</th><th>Owner</th><th>Shop</th><th>Actions</th></tr></thead>
              <tbody>
                <?php foreach ($pendingShops as $s): ?>
                  <tr>
                    <td>
                      <img src="<?= e($s['image_url'] ?: 'https://images.unsplash.com/photo-1552566626-52f8b828add9?w=120&q=80') ?>"
                           alt="Thumbnail"
                           style="width:44px;height:44px;object-fit:cover;border-radius:6px;border:1px solid #eee;" />
                    </td>
                    <td><?= e($s['full_name']) ?></td>
                    <td class="col-muted">
                      <?= e($s['name']) ?>
                      <?php if (!empty($s['document_url'])): ?>
                        <div style="margin-top:4px;">
                          <a href="<?= e($s['document_url']) ?>" target="_blank" style="font-size:0.75rem;color:var(--color-primary);font-weight:600;text-decoration:none;">
                            <i class="fa-solid fa-file-lines"></i> View Document
                          </a>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <form method="post" action="admin-shop-approvals.php" style="display:inline;">
                        <input type="hidden" name="shop_id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="action" value="approve">
                        <button class="tbl-btn-approve">Approve</button>
                      </form>
                      <form method="post" action="admin-shop-approvals.php" style="display:inline;">
                        <input type="hidden" name="shop_id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="action" value="reject">
                        <button class="tbl-btn-reject">Reject</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$pendingShops): ?><tr><td colspan="4" style="text-align:center;color:#888;">No pending approvals.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="section-card" style="margin-bottom:0;">
            <h2 class="section-card-title">Pending Runner Approvals</h2>
            <table class="data-table">
              <thead><tr><th>Name</th><th>Student ID</th><th>Actions</th></tr></thead>
              <tbody>
                <?php foreach ($pendingRunners as $r): ?>
                  <tr>
                    <td><?= e($r['full_name']) ?></td>
                    <td class="col-muted">
                      <?= e($r['student_id']) ?>
                      <?php if (!empty($r['document_url'])): ?>
                        <div style="margin-top:4px;">
                          <a href="<?= e($r['document_url']) ?>" target="_blank" style="font-size:0.75rem;color:var(--color-primary);font-weight:600;text-decoration:none;">
                            <i class="fa-solid fa-file-lines"></i> View Document
                          </a>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <form method="post" action="admin-runner-approvals.php" style="display:inline;">
                        <input type="hidden" name="runner_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="approve">
                        <button class="tbl-btn-approve">Approve</button>
                      </form>
                      <form method="post" action="admin-runner-approvals.php" style="display:inline;">
                        <input type="hidden" name="runner_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="reject">
                        <button class="tbl-btn-reject">Reject</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$pendingRunners): ?><tr><td colspan="3" style="text-align:center;color:#888;">No pending approvals.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>