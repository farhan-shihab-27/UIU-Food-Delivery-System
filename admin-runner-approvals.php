<?php
require 'config.php';
require 'auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid = (int)($_POST['runner_id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($rid && in_array($act, ['approve','reject'])) {
        $status = $act === 'approve' ? 'approved' : 'rejected';
        $pdo->prepare("UPDATE runners SET status=?, approved_by=?, approved_at=NOW() WHERE id=?")
            ->execute([$status, $_SESSION['user_id'], $rid]);
        $pdo->prepare("INSERT INTO admin_action_logs (admin_id, action_type, target_type, target_id, description) VALUES (?,?,?,?,?)")
            ->execute([$_SESSION['user_id'], $act . '_runner', 'runner', $rid, "Runner #$rid $status"]);

        /* Notify runner */
        $rStmt = $pdo->prepare("SELECT user_id FROM runners WHERE id = ?");
        $rStmt->execute([$rid]);
        $rUserId = $rStmt->fetchColumn();
        if ($rUserId) {
            if ($act === 'approve') {
                notify($pdo, $rUserId,
                    'Runner Account Approved',
                    'Your runner account has been approved. You can now accept deliveries!',
                    'system', 'runner-dashboard.php');
            } else {
                notify($pdo, $rUserId,
                    'Runner Application Rejected',
                    'Unfortunately your runner application was not approved.',
                    'system', 'login.php');
            }
        }
    }
    header('Location: admin-runner-approvals.php'); exit;
}

$pending = $pdo->query("SELECT r.*, u.full_name, u.email FROM runners r JOIN users u ON u.id=r.user_id WHERE r.status='pending' ORDER BY r.created_at ASC")->fetchAll();
$active = $pdo->query("SELECT r.*, u.full_name FROM runners r JOIN users u ON u.id=r.user_id WHERE r.status='approved' ORDER BY r.total_deliveries DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Runner Approvals</title>
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
        <a href="admin-runner-approvals.php"  class="sidebar-nav-item active"><i class="fa-solid fa-bicycle"></i> Runner Approvals</a>
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
      <div class="dash-topbar"><div class="dash-topbar-left"><h1 class="dash-topbar-title">Runner Approvals</h1></div></div>

      <div class="approval-page-body">
        <div class="approval-card-full">
          <h2 class="section-card-title">Pending Runner Applications</h2>
          <table class="data-table">
            <thead><tr><th>Name</th><th>Email</th><th>Student ID</th><th>Vehicle</th><th>Document</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($pending as $r): ?>
                <tr>
                  <td><?= e($r['full_name']) ?></td>
                  <td style="font-size:0.85rem;color:#666;"><?= e($r['email']) ?></td>
                  <td><?= e($r['student_id']) ?></td>
                  <td><?= ucfirst($r['vehicle_type']) ?></td>
                  <td>
                    <?php if (!empty($r['document_url'])): ?>
                      <a href="<?= e($r['document_url']) ?>" target="_blank"
                         style="color:var(--color-primary);font-weight:600;font-size:0.85rem;text-decoration:none;">
                        <i class="fa-solid fa-file-lines"></i> View Document
                      </a>
                    <?php else: ?>
                      <span style="color:#c62828;font-size:0.85rem;">No document</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <form method="post" style="display:inline;"><input type="hidden" name="runner_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="approve"><button class="tbl-btn-approve">Approve</button></form>
                    <form method="post" style="display:inline;"><input type="hidden" name="runner_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="reject"><button class="tbl-btn-reject">Reject</button></form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$pending): ?><tr><td colspan="6" style="text-align:center;color:#888;padding:20px;">No pending applications.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="approval-card-full">
          <h2 class="section-card-title">Active Runners</h2>
          <table class="data-table">
            <thead><tr><th>Name</th><th>Student ID</th><th>Deliveries</th><th>Rating</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($active as $r): ?>
                <tr>
                  <td><?= e($r['full_name']) ?></td>
                  <td><?= e($r['student_id']) ?></td>
                  <td><?= (int)$r['total_deliveries'] ?></td>
                  <td>★ <?= number_format((float)$r['rating_avg'],1) ?></td>
                  <td><span class="status-badge badge-success">Active</span></td>
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