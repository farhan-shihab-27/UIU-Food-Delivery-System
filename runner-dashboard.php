<?php
require 'config.php';
require 'auth.php';
require_role('runner');

$uid = $_SESSION['user_id'];
$runner = $pdo->query("SELECT * FROM runners WHERE user_id=$uid")->fetch();
if (!$runner) die('Runner profile not found.');
$rid = $runner['id'];

$flashMsg = '';

/* Handle accept / decline / deliver */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = (int)($_POST['order_id'] ?? 0);
    $act = $_POST['action'] ?? '';

    if ($oid && $act === 'accept') {
        $stmt = $pdo->prepare("UPDATE orders SET runner_id=?, status='picked_up', picked_up_at=NOW() WHERE id=? AND runner_id IS NULL AND status='ready'");
        $stmt->execute([$rid, $oid]);
        if ($stmt->rowCount() > 0) {
            $pdo->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?, 'picked_up', ?)")
                ->execute([$oid, $uid]);
            $flashMsg = 'Job accepted! Head to pickup.';

            /* Notify student */
            $infoStmt = $pdo->prepare("
                SELECT o.order_code, u.id AS student_user_id
                FROM orders o
                JOIN students st ON st.id = o.student_id
                JOIN users    u  ON u.id = st.user_id
                WHERE o.id = ?
            ");
            $infoStmt->execute([$oid]);
            $info = $infoStmt->fetch();
            if ($info) {
                notify($pdo, $info['student_user_id'],
                    'Runner On The Way',
                    $_SESSION['name'] . ' picked up your order ' . $info['order_code'] . ' and is heading to you.',
                    'order', 'track-order.php?order_id=' . $oid);
            }
        } else {
            $flashMsg = 'Sorry, that job was already taken.';
        }
    } elseif ($oid && $act === 'deliver') {
        $stmt = $pdo->prepare("UPDATE orders SET status='delivered', delivered_at=NOW() WHERE id=? AND runner_id=? AND status IN ('picked_up','on_the_way')");
        $stmt->execute([$oid, $rid]);
        if ($stmt->rowCount() > 0) {
            $pdo->prepare("UPDATE runners SET total_deliveries=total_deliveries+1, total_earnings=total_earnings+3.50, available_balance=available_balance+3.50 WHERE id=?")
                ->execute([$rid]);
            $flashMsg = 'Delivery marked as delivered! Earnings updated.';

            /* Notify student + prompt for rating */
            $infoStmt = $pdo->prepare("
                SELECT o.order_code, u.id AS student_user_id
                FROM orders o
                JOIN students st ON st.id = o.student_id
                JOIN users    u  ON u.id = st.user_id
                WHERE o.id = ?
            ");
            $infoStmt->execute([$oid]);
            $info = $infoStmt->fetch();
            if ($info) {
                notify($pdo, $info['student_user_id'],
                    'Order Delivered',
                    'Your order ' . $info['order_code'] . ' has been delivered. Please rate the shop and runner!',
                    'order', 'rate-order.php?order_id=' . $oid);
            }
        }
    }
    header('Location: runner-dashboard.php'); exit;
}

$runner = $pdo->query("SELECT * FROM runners WHERE id=$rid")->fetch();

$jobs = $pdo->query("SELECT o.*, s.name AS shop_name, u.full_name AS student_name, st.default_address FROM orders o JOIN shops s ON s.id=o.shop_id JOIN students st ON st.id=o.student_id JOIN users u ON u.id=st.user_id WHERE o.status='ready' AND o.runner_id IS NULL ORDER BY o.ready_at ASC LIMIT 10")->fetchAll();

$active = $pdo->prepare("SELECT o.*, s.name AS shop_name, u.full_name AS student_name, st.default_address FROM orders o JOIN shops s ON s.id=o.shop_id JOIN students st ON st.id=o.student_id JOIN users u ON u.id=st.user_id WHERE o.runner_id=? AND o.status IN ('picked_up','on_the_way') LIMIT 1");
$active->execute([$rid]); $active = $active->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Runner Dashboard</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-logo"><div class="sidebar-logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg></div><span class="sidebar-logo-text">UIU Runner Portal</span></div>
      <nav class="sidebar-nav">
        <a href="runner-dashboard.php"   class="sidebar-nav-item active"><i class="fa-solid fa-bicycle"></i> Available Jobs</a>
        <a href="runner-deliveries.php"  class="sidebar-nav-item"><i class="fa-solid fa-clipboard-list"></i> My Deliveries</a>
        <a href="runner-earnings.php"    class="sidebar-nav-item"><i class="fa-solid fa-wallet"></i> Earnings</a>
        <a href="chat.php"               class="sidebar-nav-item"><i class="fa-solid fa-comment"></i> Messages</a>

        <!-- ========== NOTIFICATIONS BELL ========== -->
        <a href="notifications.php" class="sidebar-nav-item">
          <i class="fa-solid fa-bell"></i> Notifications
          <?php $__uc = unread_notification_count($pdo, $_SESSION['user_id']); if ($__uc > 0): ?>
            <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:99px;font-size:0.65rem;padding:1px 6px;font-weight:700;"><?= $__uc ?></span>
          <?php endif; ?>
        </a>
        <!-- ========================================= -->

        <a href="profile.php"            class="sidebar-nav-item"><i class="fa-solid fa-user"></i> Profile</a>
      </nav>
      <div class="sidebar-user">
        <img src="<?= e(avatar_url()) ?>" alt="<?= e($_SESSION['name']) ?>" class="sidebar-user-avatar" />
        <div class="sidebar-user-info">
          <p class="sidebar-user-name"><?= e($_SESSION['name']) ?></p>
          <p class="sidebar-user-role">Runner</p>
          <a href="logout.php" class="sidebar-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <div class="runner-topbar">
        <h1 class="runner-topbar-title">Runner Dashboard</h1>
        <div class="runner-status-pill"><span class="runner-status-pill-dot"></span> ONLINE</div>
      </div>

      <div style="padding:24px 32px;">
        <div class="runner-stats-row">
          <div class="runner-stat-card"><p class="runner-stat-label">Total Earnings</p><p class="runner-stat-value earnings">$<?= number_format((float)$runner['total_earnings'],2) ?></p></div>
          <div class="runner-stat-card"><p class="runner-stat-label">Deliveries</p><p class="runner-stat-value"><?= (int)$runner['total_deliveries'] ?></p></div>
          <div class="runner-stat-card"><p class="runner-stat-label">Rating</p><p class="runner-stat-value">★ <?= number_format((float)$runner['rating_avg'],1) ?></p></div>
        </div>

        <h2 class="runner-section-title">Available Jobs</h2>
        <?php if ($jobs): foreach ($jobs as $j): ?>
          <div class="delivery-job-card">
            <div class="delivery-job-header">
              <span class="delivery-job-name"><?= e($j['shop_name']) ?></span>
              <span class="delivery-job-earn">+$3.50</span>
            </div>
            <div class="delivery-job-route">
              <div class="delivery-route-row"><span class="delivery-dot-pickup"></span>Pickup: <?= e($j['shop_name']) ?></div>
              <div class="delivery-route-row"><span class="delivery-dot-drop"></span>Drop: <?= e($j['default_address']) ?> (<?= e($j['student_name']) ?>)</div>
            </div>
            <div class="delivery-job-footer">
              <span class="delivery-distance">Order <?= e($j['order_code']) ?></span>
              <div class="delivery-job-actions">
                <form method="post" style="display:inline;"><input type="hidden" name="order_id" value="<?= (int)$j['id'] ?>"><input type="hidden" name="action" value="decline"><button class="job-btn-decline">Decline</button></form>
                <form method="post" style="display:inline;"><input type="hidden" name="order_id" value="<?= (int)$j['id'] ?>"><input type="hidden" name="action" value="accept"><button class="job-btn-accept">Accept</button></form>
              </div>
            </div>
          </div>
        <?php endforeach; else: ?>
          <p style="color:#888;">No jobs available right now.</p>
        <?php endif; ?>

        <?php if ($active): ?>
          <h2 class="runner-section-title" style="margin-top:32px;">Active Delivery</h2>
          <div class="active-delivery-card">
            <div class="active-delivery-top">
              <span class="active-delivery-badge">CURRENT JOB</span>
              <span class="badge badge-primary"><?= ucfirst(str_replace('_',' ',$active['status'])) ?></span>
            </div>
            <p class="active-delivery-order-num">Order <?= e($active['order_code']) ?></p>
            <div class="active-delivery-route">
              <div class="active-route-section"><p class="active-route-label">PICKUP FROM</p><p class="active-route-name"><?= e($active['shop_name']) ?></p></div>
              <div class="active-route-section"><p class="active-route-label">DELIVER TO</p><p class="active-route-name"><?= e($active['student_name']) ?></p><p class="active-route-addr"><?= e($active['default_address']) ?></p></div>
            </div>

            <div style="padding:16px 20px; border-top:1px solid var(--color-border-light);">
              <button type="button" id="btnShareLoc" class="active-delivery-primary-btn"
                      style="margin-bottom:8px;"
                      onclick="toggleLocationSharing(<?= (int)$active['id'] ?>)">
                <i class="fa-solid fa-location-dot"></i> Start Sharing Location
              </button>
              <p id="shareLocStatus" style="font-size:0.8rem; color:#888; text-align:center; margin:0;">
                Your live position will be sent to the student.
              </p>
            </div>

            <div class="active-delivery-actions">
              <form method="post">
                <input type="hidden" name="order_id" value="<?= (int)$active['id'] ?>">
                <input type="hidden" name="action" value="deliver">
                <button type="submit" class="active-delivery-primary-btn">Mark Delivered</button>
              </form>
              <a href="chat.php?order_id=<?= (int)$active['id'] ?>"
                 class="active-delivery-primary-btn"
                 style="background:#fff;color:var(--color-primary);border:1.5px solid var(--color-primary);text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;">
                <i class="fa-solid fa-comment-dots"></i> Chat with Customer
              </a>
            </div>
          </div>

          <script>
          var _watchId  = null;
          var _activeOrderId = <?= (int)$active['id'] ?>;
          var _sharing  = false;

          function setBtnState(sharing) {
            _sharing = sharing;
            var btn = document.getElementById('btnShareLoc');
            if (!btn) return;
            if (sharing) {
              btn.innerHTML = '<i class="fa-solid fa-stop"></i> Stop Sharing Location';
              btn.style.background = '#c62828';
            } else {
              btn.innerHTML = '<i class="fa-solid fa-location-dot"></i> Start Sharing Location';
              btn.style.background = '';
            }
          }

          function toggleLocationSharing(orderId) {
            if (_sharing) { stopSharing(); return; }
            startSharing(orderId);
          }

          function startSharing(orderId) {
            if (!navigator.geolocation) {
              document.getElementById('shareLocStatus').textContent =
                'Your browser does not support GPS location.';
              return;
            }
            document.getElementById('shareLocStatus').textContent = '📡 Requesting GPS permission…';

            _watchId = navigator.geolocation.watchPosition(
              function(pos) {
                var lat = pos.coords.latitude;
                var lng = pos.coords.longitude;
                var acc = Math.round(pos.coords.accuracy || 0);

                var fd = new FormData();
                fd.append('order_id', orderId);
                fd.append('lat', lat);
                fd.append('lng', lng);

                fetch('update-location.php', { method: 'POST', body: fd })
                  .then(function(r) { return r.json(); })
                  .then(function(d) {
                    if (d.ok) {
                      setBtnState(true);
                      document.getElementById('shareLocStatus').innerHTML =
                        '📡 Sharing live — <strong>' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '</strong> (±' + acc + 'm)';
                    } else {
                      document.getElementById('shareLocStatus').textContent =
                        'Error: ' + (d.err || 'unknown');
                    }
                  })
                  .catch(function() {
                    document.getElementById('shareLocStatus').textContent =
                      'Network error updating location.';
                  });
              },
              function(err) {
                document.getElementById('shareLocStatus').textContent =
                  'GPS error: ' + err.message;
              },
              { enableHighAccuracy: true, maximumAge: 3000, timeout: 15000 }
            );

            setBtnState(true);
          }

          function stopSharing() {
            if (_watchId !== null) {
              navigator.geolocation.clearWatch(_watchId);
              _watchId = null;
            }
            setBtnState(false);
            document.getElementById('shareLocStatus').textContent = 'Location sharing stopped.';
          }

          window.addEventListener('beforeunload', function() {
            if (_watchId !== null) navigator.geolocation.clearWatch(_watchId);
          });
          </script>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>