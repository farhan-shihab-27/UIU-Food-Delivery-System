<?php
require 'config.php';
require 'auth.php';
require_role('admin');

/* Handle approve/reject */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shopId = (int)($_POST['shop_id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($shopId && in_array($act, ['approve','reject'])) {
        $status = $act === 'approve' ? 'approved' : 'rejected';
        $note = $act === 'reject' ? 'Rejected by admin' : null;
        $pdo->prepare("UPDATE shops SET status=?, approved_by=?, approved_at=NOW(), rejection_note=? WHERE id=?")
            ->execute([$status, $_SESSION['user_id'], $note, $shopId]);
        $pdo->prepare("INSERT INTO admin_action_logs (admin_id, action_type, target_type, target_id, description) VALUES (?,?,?,?,?)")
            ->execute([$_SESSION['user_id'], $act . '_shop', 'shop', $shopId, "Shop #$shopId $status"]);

        /* Notify owner */
        $ownerStmt = $pdo->prepare("SELECT owner_id, name FROM shops WHERE id = ?");
        $ownerStmt->execute([$shopId]);
        $ownerRow = $ownerStmt->fetch();
        if ($ownerRow) {
            if ($act === 'approve') {
                notify($pdo, $ownerRow['owner_id'],
                    'Shop Approved',
                    '"' . $ownerRow['name'] . '" has been approved. You can now start accepting orders!',
                    'system', 'shop-dashboard.php');
            } else {
                notify($pdo, $ownerRow['owner_id'],
                    'Shop Application Rejected',
                    'Unfortunately your shop "' . $ownerRow['name'] . '" was not approved.',
                    'system', 'login.php');
            }
        }
    }
    header('Location: admin-shop-approvals.php'); exit;
}

$pending = $pdo->query("SELECT s.*, u.full_name, u.email FROM shops s JOIN users u ON u.id=s.owner_id WHERE s.status='pending' ORDER BY s.created_at ASC")->fetchAll();
$approved = $pdo->query("SELECT s.*, u.full_name FROM shops s JOIN users u ON u.id=s.owner_id WHERE s.status='approved' ORDER BY s.approved_at DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Shop Approvals</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-logo"><div class="sidebar-logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg></div><span class="sidebar-logo-text">UIU Food Portal</span></div>
      <nav class="sidebar-nav">
        <a href="admin-dashboard.php"         class="sidebar-nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="admin-shop-approvals.php"    class="sidebar-nav-item active"><i class="fa-solid fa-store"></i> Shop Approvals</a>
        <a href="admin-runner-approvals.php"  class="sidebar-nav-item"><i class="fa-solid fa-bicycle"></i> Runner Approvals</a>
        <a href="admin-reports.php"           class="sidebar-nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>
        <a href="complaint-management.php"    class="sidebar-nav-item"><i class="fa-solid fa-comment"></i> Complaints</a>

        <!-- ========== NOTIFICATIONS BELL ========== -->
        <a href="notifications.php" class="sidebar-nav-item">
          <i class="fa-solid fa-bell"></i> Notifications
          <?php $__uc = unread_notification_count($pdo, $_SESSION['user_id']); if ($__uc > 0): ?>
            <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:99px;font-size:0.65rem;padding:1px 6px;font-weight:700;"><?= $__uc ?></span>
          <?php endif; ?>
        </a>
        <!-- ========================================= -->

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
      <div class="dash-topbar"><div class="dash-topbar-left"><h1 class="dash-topbar-title">Shop Approvals</h1></div></div>

      <div class="approval-page-body">
        <div class="approval-stats-row">
          <div class="dash-stat-card"><span class="stat-label">Pending</span><span class="stat-value"><?= count($pending) ?></span></div>
          <div class="dash-stat-card"><span class="stat-label">Approved</span><span class="stat-value"><?= count($approved) ?></span></div>
        </div>

        <div class="approval-card-full">
          <h2 class="section-card-title">Pending Shop Applications</h2>
          <table class="data-table">
            <thead><tr><th>Thumbnail</th><th>Owner</th><th>Email</th><th>Shop Name</th><th>Submitted</th><th>Document</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($pending as $s): ?>
                <tr>
                  <td>
                    <img src="<?= e($s['image_url'] ?: 'https://images.unsplash.com/photo-1552566626-52f8b828add9?w=120&q=80') ?>"
                         alt="Thumbnail"
                         style="width:60px;height:60px;object-fit:cover;border-radius:8px;border:1px solid #eee;" />
                  </td>
                  <td><?= e($s['full_name']) ?></td>
                  <td style="color:#666;font-size:0.85rem;"><?= e($s['email']) ?></td>
                  <td><?= e($s['name']) ?></td>
                  <td style="font-size:0.85rem;color:#666;"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                  <td>
                    <?php if ($s['document_url']): ?>
                      <a href="<?= e($s['document_url']) ?>" target="_blank"
                         style="color:var(--color-primary);font-weight:600;font-size:0.85rem;text-decoration:none;">
                        <i class="fa-solid fa-file-lines"></i> View Document
                      </a>
                    <?php else: ?>
                      <span style="color:#c62828;font-size:0.85rem;">No document</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <form method="post" style="display:inline;"><input type="hidden" name="shop_id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="action" value="approve"><button class="tbl-btn-approve">Approve</button></form>
                    <form method="post" style="display:inline;"><input type="hidden" name="shop_id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="action" value="reject"><button class="tbl-btn-reject">Reject</button></form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$pending): ?><tr><td colspan="7" style="text-align:center;color:#888;padding:20px;">No pending applications.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="approval-card-full">
          <h2 class="section-card-title">Approved Shops</h2>
          <table class="data-table">
            <thead><tr><th>Thumbnail</th><th>Shop</th><th>Owner</th><th>Approved</th></tr></thead>
            <tbody>
              <?php foreach ($approved as $s): ?>
                <tr>
                  <td>
                    <img src="<?= e($s['image_url'] ?: 'https://images.unsplash.com/photo-1552566626-52f8b828add9?w=120&q=80') ?>"
                         alt="Thumbnail"
                         style="width:50px;height:50px;object-fit:cover;border-radius:8px;border:1px solid #eee;" />
                  </td>
                  <td><strong><?= e($s['name']) ?></strong></td>
                  <td><?= e($s['full_name']) ?></td>
                  <td style="font-size:0.85rem;color:#666;"><?= $s['approved_at'] ? date('M d, Y', strtotime($s['approved_at'])) : '—' ?></td>
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