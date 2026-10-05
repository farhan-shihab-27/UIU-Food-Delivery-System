<?php
require 'config.php';
require 'auth.php';
require_role('admin');

/* Handle POST actions  */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid = (int)($_POST['complaint_id'] ?? 0);
    $act = $_POST['action'] ?? '';

    if ($cid && $act === 'resolve') {
        $pdo->prepare("UPDATE complaints SET status='resolved', handled_by=?, resolved_at=NOW() WHERE id=?")
            ->execute([$_SESSION['user_id'], $cid]);

        /* Notify student  */
        if (function_exists('notify')) {
            $cStmt = $pdo->prepare("
                SELECT c.complaint_code, u.id AS student_user_id
                FROM complaints c
                JOIN students s ON s.id = c.student_id
                JOIN users    u ON u.id = s.user_id
                WHERE c.id = ?
            ");
            $cStmt->execute([$cid]);
            $cr = $cStmt->fetch();
            if ($cr) {
                notify($pdo, $cr['student_user_id'],
                    'Complaint Resolved',
                    'Your complaint ' . $cr['complaint_code'] . ' has been resolved.',
                    'complaint', 'order-history.php');
            }
        }
    }
    elseif ($cid && $act === 'close') {
        $pdo->prepare("UPDATE complaints SET status='closed', handled_by=? WHERE id=?")
            ->execute([$_SESSION['user_id'], $cid]);
    }
    elseif ($cid && $act === 'note') {
        $note = trim($_POST['note'] ?? '');
        if ($note !== '') {
            $pdo->prepare("UPDATE complaints SET resolution_note=? WHERE id=?")
                ->execute([$note, $cid]);
        }
    }

    /* Redirect back to the SAME complaint so the row stays selected */
    header('Location: complaint-management.php?id=' . $cid);
    exit;
}

/*  Load complaints  */
$complaints = $pdo->query("
    SELECT c.*, u.full_name AS student_name
    FROM complaints c
    JOIN students st ON st.id = c.student_id
    JOIN users    u  ON u.id = st.user_id
    ORDER BY c.created_at DESC
")->fetchAll();

$first = $complaints[0] ?? null;
$selectedId = (int)($_GET['id'] ?? ($first['id'] ?? 0));
$selected = null;
foreach ($complaints as $c) {
    if ((int)$c['id'] === $selectedId) { $selected = $c; break; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Complaint Management</title>
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
        <a href="admin-runner-approvals.php"  class="sidebar-nav-item"><i class="fa-solid fa-bicycle"></i> Runner Approvals</a>
        <a href="admin-reports.php"           class="sidebar-nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>
        <a href="complaint-management.php"    class="sidebar-nav-item active"><i class="fa-solid fa-comment"></i> Complaints</a>

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

    <main class="main-content" style="padding:0;">
      <div class="complaint-layout">

        <!-- ============ LEFT: LIST ============ -->
        <div class="complaint-left">
          <div style="display:flex;align-items:center;gap:var(--space-4);margin-bottom:var(--space-5);">
            <h1 style="font-size:1.5rem;font-weight:800;">Complaint Management</h1>
          </div>

          <div class="complaint-table-wrap">
            <table class="complaint-data-table">
              <thead>
                <tr>
                  <th>ID</th><th>Student</th><th>Category</th><th>Priority</th><th>Status</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($complaints as $c): ?>
                  <tr class="<?= (int)$c['id']===$selectedId?'row-active':'' ?>">
                    <td class="td-id"><?= e($c['complaint_code']) ?></td>
                    <td class="td-name"><?= e($c['student_name']) ?></td>
                    <td class="td-category"><?= e(ucfirst(str_replace('_',' ',$c['category']))) ?></td>
                    <td><span class="priority-<?= e($c['priority']) ?>"><?= ucfirst($c['priority']) ?></span></td>
                    <td><span class="status-badge status-<?= $c['status']==='open'?'open':'resolved' ?>"><?= ucfirst($c['status']) ?></span></td>
                    <td>
                      <?php if ((int)$c['id'] === $selectedId): ?>
                        <!-- Currently viewed complaint — show a non-clickable "Viewing" badge -->
                        <span class="tbl-btn-view-outline"
                              style="opacity:0.6;cursor:default;border-style:dashed;">
                          <i class="fa-solid fa-eye" style="margin-right:4px;font-size:0.7rem;"></i>Viewing
                        </span>
                      <?php else: ?>
                        <a href="complaint-management.php?id=<?= (int)$c['id'] ?>"
                           class="tbl-btn-view-outline">View</a>
                      <?php endif; ?>

                      <?php if ($c['status'] === 'open'): ?>
                        <form method="post"
                              action="complaint-management.php?id=<?= (int)$c['id'] ?>"
                              style="display:inline;">
                          <input type="hidden" name="complaint_id" value="<?= (int)$c['id'] ?>">
                          <input type="hidden" name="action" value="resolve">
                          <button type="submit" class="tbl-btn-approve" style="margin-left:4px;">Resolve</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$complaints): ?>
                  <tr><td colspan="6" style="text-align:center;color:#888;padding:30px;">No complaints yet.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ============ RIGHT: DETAILS ============ -->
        <div class="complaint-right">
          <?php if ($selected): ?>
            <div class="complaint-detail-header">
              <div>
                <p class="complaint-detail-title">Complaint Details</p>
                <p class="complaint-detail-id">ID: <?= e($selected['complaint_code']) ?></p>
              </div>
            </div>

            <div class="complaint-meta-pills">
              <span class="priority-<?= e($selected['priority']) ?>"><?= ucfirst($selected['priority']) ?></span>
              <span class="status-pill-open"><?= ucfirst($selected['status']) ?></span>
            </div>

            <div class="complaint-detail-section">
              <p class="complaint-detail-label">Filed By</p>
              <p class="complaint-detail-body"><?= e($selected['student_name']) ?></p>
            </div>

            <div class="complaint-detail-section">
              <p class="complaint-detail-label">Title</p>
              <p class="complaint-detail-body"><?= e($selected['title']) ?></p>
            </div>

            <div class="complaint-detail-section">
              <p class="complaint-detail-label">Description</p>
              <p class="complaint-desc-text"><?= e($selected['description']) ?></p>
            </div>

            <?php if (!empty($selected['screenshot_url'])): ?>
              <div class="complaint-detail-section">
                <p class="complaint-detail-label">Attached Screenshot</p>
                <img src="<?= e($selected['screenshot_url']) ?>" alt="Screenshot" class="complaint-screenshot" />
              </div>
            <?php endif; ?>

            <?php if (!empty($selected['resolution_note'])): ?>
              <div class="complaint-detail-section">
                <p class="complaint-detail-label">Current Note</p>
                <p class="complaint-desc-text"><?= e($selected['resolution_note']) ?></p>
              </div>
            <?php endif; ?>

            <!-- ===== Save Note + Resolve ===== -->
            <form method="post"
                  action="complaint-management.php?id=<?= (int)$selected['id'] ?>"
                  class="complaint-detail-section">
              <p class="complaint-detail-label">Resolution Notes</p>
              <input type="hidden" name="complaint_id" value="<?= (int)$selected['id'] ?>">
              <input type="hidden" name="action" value="note">
              <textarea name="note" class="resolution-textarea"
                        placeholder="Enter resolution notes..."><?= e($selected['resolution_note']) ?></textarea>

              <div class="complaint-action-btns">
                <!-- Save Note: submits the note-only form above -->
                <button type="submit" class="btn-reply-only">Save Note</button>

                <!-- Resolve: separate form below, avoids name clash -->
                <button type="button" class="btn-resolve-refund"
                        onclick="document.getElementById('resolveForm<?= (int)$selected['id'] ?>').submit();">
                  Resolve
                </button>
              </div>
            </form>

            <!-- Resolve: independent form, submitted by the button above -->
            <form id="resolveForm<?= (int)$selected['id'] ?>"
                  method="post"
                  action="complaint-management.php?id=<?= (int)$selected['id'] ?>"
                  style="display:none;">
              <input type="hidden" name="complaint_id" value="<?= (int)$selected['id'] ?>">
              <input type="hidden" name="action" value="resolve">
            </form>

            <!-- Close complaint -->
            <form method="post"
                  action="complaint-management.php?id=<?= (int)$selected['id'] ?>"
                  onsubmit="return confirm('Close this complaint?');">
              <input type="hidden" name="complaint_id" value="<?= (int)$selected['id'] ?>">
              <input type="hidden" name="action" value="close">
              <button type="submit" class="btn-close-complaint">Close Complaint</button>
            </form>

          <?php else: ?>
            <p style="text-align:center;color:#888;padding:40px;">No complaint selected.</p>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</body>
</html>