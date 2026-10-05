<?php
require 'config.php';
require 'auth.php';
require_role('student');

$uid = $_SESSION['user_id'];
$me  = $pdo->query("SELECT id FROM students WHERE user_id=$uid")->fetch();
$sid = $me['id'];

$preselectedOrder = (int)($_GET['order_id'] ?? 0);
$msg = ''; $err = '';

/* ------------------ Handle submission ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId     = (int)($_POST['order_id'] ?? 0);
    $category    = trim($_POST['category'] ?? '');
    $priority    = $_POST['priority'] ?? 'medium';
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $screenshot  = trim($_POST['screenshot_url'] ?? '');

    $validCategories = ['food_quality','late_delivery','wrong_order','missing_items','runner_behaviour','payment','other'];
    $validPriorities = ['low','medium','high'];

    if ($orderId <= 0)                                 $err = 'Please select an order.';
    elseif (!in_array($category, $validCategories))    $err = 'Please select a valid category.';
    elseif (!in_array($priority, $validPriorities))    $err = 'Please select a valid priority.';
    elseif ($title === '')                             $err = 'Please give a short title.';
    elseif (mb_strlen($title) > 150)                   $err = 'Title is too long (max 150 chars).';
    elseif (mb_strlen($description) > 2000)            $err = 'Description is too long (max 2000 chars).';
    else {
        /* Verify order belongs to this student */
        $oStmt = $pdo->prepare("SELECT id, shop_id, runner_id FROM orders WHERE id = ? AND student_id = ?");
        $oStmt->execute([$orderId, $sid]);
        $order = $oStmt->fetch();

        if (!$order) {
            $err = 'That order was not found in your history.';
        } else {
            /* Generate next complaint code: #C-001, #C-002 ... */
            $max  = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(complaint_code, 4) AS UNSIGNED)), 0) FROM complaints")->fetchColumn();
            $code = '#C-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);

            $pdo->prepare("INSERT INTO complaints
                (complaint_code, student_id, order_id, shop_id, runner_id, category, priority, status, title, description, screenshot_url)
                VALUES (?,?,?,?,?,?,?, 'open', ?,?,?)")
                ->execute([
                    $code, $sid, $orderId, $order['shop_id'], $order['runner_id'],
                    $category, $priority, $title, $description,
                    $screenshot !== '' ? $screenshot : null
                ]);
            $newId = (int)$pdo->lastInsertId();

            /* Notify all admins */
            $admins = $pdo->query("SELECT id FROM users WHERE role='admin'")->fetchAll();
            foreach ($admins as $a) {
                notify($pdo, $a['id'],
                    'New Complaint Filed',
                    'Complaint ' . $code . ' filed by ' . $_SESSION['name'] . '.',
                    'complaint',
                    'complaint-management.php?id=' . $newId);
            }

            $msg = 'Complaint submitted successfully. Reference: ' . $code;
            $preselectedOrder = 0;
        }
    }
}

/* ------------------ Load student's orders ------------------ */
$orders = $pdo->prepare("
    SELECT o.id, o.order_code, o.status, o.placed_at, s.name AS shop_name
    FROM orders o
    JOIN shops s ON s.id = o.shop_id
    WHERE o.student_id = ?
      AND o.status NOT IN ('cancelled','rejected')
    ORDER BY o.placed_at DESC
    LIMIT 30
");
$orders->execute([$sid]);
$orders = $orders->fetchAll();

/* ------------------ Load student's complaints ------------------ */
$myComplaints = $pdo->prepare("
    SELECT c.*, s.name AS shop_name
    FROM complaints c
    LEFT JOIN shops s ON s.id = c.shop_id
    WHERE c.student_id = ?
    ORDER BY c.created_at DESC
");
$myComplaints->execute([$sid]);
$myComplaints = $myComplaints->fetchAll();

/* Small helper for the category label */
function pretty_category($c) {
    return ucwords(str_replace('_', ' ', $c));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Complaints</title>
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
        <a href="order-history.php"       class="sidebar-nav-item"><i class="fa-solid fa-receipt"></i> My Orders</a>
        <a href="wallet.php"              class="sidebar-nav-item"><i class="fa-solid fa-wallet"></i> Wallet</a>
        <a href="chat.php"                class="sidebar-nav-item"><i class="fa-solid fa-comment"></i> Messages</a>

        <a href="submit-complaint.php" class="sidebar-nav-item active">
          <i class="fa-solid fa-triangle-exclamation"></i> Complaints
        </a>

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
      <div class="page-header">
        <div class="page-header-left">
          <h1 class="page-title">Complaints</h1>
          <p class="page-subtitle">Report an issue with a shop or a delivery runner</p>
        </div>
      </div>

      <div style="padding:24px 32px; max-width:900px;">

        <?php if ($msg): ?>
          <div style="background:#e8f5e9;color:#2e7d32;padding:12px 16px;border-radius:8px;font-weight:600;margin-bottom:16px;">
            <i class="fa-solid fa-circle-check" style="margin-right:6px;"></i><?= e($msg) ?>
          </div>
        <?php endif; ?>
        <?php if ($err): ?>
          <div style="background:#fdecea;color:#c62828;padding:12px 16px;border-radius:8px;font-weight:600;margin-bottom:16px;">
            <i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i><?= e($err) ?>
          </div>
        <?php endif; ?>

        <!-- ============== FILE A COMPLAINT ============== -->
        <form method="post" class="section-card">
          <h2 class="section-card-title">File a New Complaint</h2>

          <div class="form-group">
            <label class="form-label">Which order is this about? *</label>
            <select name="order_id" class="form-input" required>
              <option value="">— Select an order —</option>
              <?php foreach ($orders as $o): ?>
                <option value="<?= (int)$o['id'] ?>"
                        <?= $preselectedOrder === (int)$o['id'] ? 'selected' : '' ?>>
                  <?= e($o['order_code']) ?> — <?= e($o['shop_name']) ?> — <?= date('M d, Y', strtotime($o['placed_at'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!$orders): ?>
              <p style="font-size:0.75rem;color:#888;margin-top:4px;">You have no orders yet. Place an order first.</p>
            <?php endif; ?>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
              <label class="form-label">Category *</label>
              <select name="category" class="form-input" required>
                <option value="">— Select —</option>
                <option value="food_quality">Food Quality</option>
                <option value="late_delivery">Late Delivery</option>
                <option value="wrong_order">Wrong Order</option>
                <option value="missing_items">Missing Items</option>
                <option value="runner_behaviour">Runner Behaviour</option>
                <option value="payment">Payment Issue</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Priority *</label>
              <select name="priority" class="form-input" required>
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-input"
                   placeholder="e.g. Food arrived cold and undercooked"
                   maxlength="150" required />
          </div>

          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-input" rows="4"
                      placeholder="Describe what went wrong. Include as much detail as possible."
                      maxlength="2000"></textarea>
            <p style="font-size:0.72rem;color:#888;margin-top:4px;">Optional but recommended.</p>
          </div>

          <div class="form-group">
            <label class="form-label">Screenshot / Photo URL (optional)</label>
            <input type="url" name="screenshot_url" class="form-input"
                   placeholder="https://..." />
            <p style="font-size:0.72rem;color:#888;margin-top:4px;">Paste a link to an image showing the issue.</p>
          </div>

          <button type="submit" class="btn btn-primary" <?= !$orders ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
            <i class="fa-solid fa-paper-plane" style="margin-right:6px;"></i>Submit Complaint
          </button>
        </form>

        <!-- ============== MY COMPLAINTS LIST ============== -->
        <div class="section-card" style="margin-top:16px;">
          <h2 class="section-card-title">My Complaints (<?= count($myComplaints) ?>)</h2>

          <?php if (!$myComplaints): ?>
            <p style="color:#888;text-align:center;padding:24px 0;">
              You haven't filed any complaints yet.
            </p>
          <?php else: ?>
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Order / Shop</th>
                  <th>Category</th>
                  <th>Priority</th>
                  <th>Status</th>
                  <th>Filed</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($myComplaints as $c): ?>
                  <tr>
                    <td class="col-id"><?= e($c['complaint_code']) ?></td>
                    <td>
                      <strong><?= e($c['shop_name'] ?: '—') ?></strong>
                      <div style="font-size:0.72rem;color:#888;margin-top:2px;">
                        <?= e($c['title']) ?>
                      </div>
                    </td>
                    <td style="font-size:0.85rem;"><?= e(pretty_category($c['category'])) ?></td>
                    <td>
                      <span class="priority-<?= e($c['priority']) ?>">
                        <?= ucfirst($c['priority']) ?>
                      </span>
                    </td>
                    <td>
                      <span class="status-badge status-<?= $c['status']==='open' ? 'open' : 'resolved' ?>">
                        <?= ucfirst($c['status']) ?>
                      </span>
                    </td>
                    <td style="font-size:0.85rem;color:#666;">
                      <?= date('M d, Y', strtotime($c['created_at'])) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

      </div>
    </main>
  </div>
</body>
</html>