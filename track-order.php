<?php
require 'config.php';
require 'auth.php';
require_role('student');

$uid = $_SESSION['user_id'];
$me = $pdo->query("SELECT id FROM students WHERE user_id=$uid")->fetch();

$orderId = (int)($_GET['order_id'] ?? 0);
$stmt = $pdo->prepare("SELECT o.*, s.name AS shop_name FROM orders o JOIN shops s ON s.id=o.shop_id WHERE o.id=? AND o.student_id=?");
$stmt->execute([$orderId, $me['id']]); $order = $stmt->fetch();
if (!$order) die('Order not found.');

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
$items->execute([$orderId]); $items = $items->fetchAll();

/* Show the live map only when the order is actually in transit */
$showMap = in_array($order['status'], ['picked_up','on_the_way']);

/* Auto-refresh page until runner picks up (so the map appears automatically) */
$autoRefresh = in_array($order['status'], ['pending','accepted','preparing','ready']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Track Order <?= e($order['order_code']) ?></title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <?php if ($showMap): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <?php endif; ?>
</head>
<body style="background:var(--color-bg);">
  <div style="max-width:720px;margin:0 auto;padding:24px;">
    <a href="order-history.php" style="color:var(--color-primary);text-decoration:none;font-weight:600;">
      <i class="fa-solid fa-arrow-left"></i> Back to Orders
    </a>

    <h1 style="font-size:1.5rem;font-weight:800;margin:16px 0;">Order <?= e($order['order_code']) ?></h1>

    <?php if ($showMap): ?>
    <div class="section-card" style="padding:0; overflow:hidden; margin-bottom:16px;">
      <div style="padding:14px 18px; border-bottom:1px solid var(--color-border); display:flex; justify-content:space-between; align-items:center;">
        <span style="font-weight:700; display:flex; align-items:center; gap:8px;">
          <i class="fa-solid fa-location-dot" style="color:var(--color-primary);"></i>
          Live Runner Location
        </span>
        <span id="lastUpdate" style="font-size:0.75rem; color:#888;">Waiting…</span>
      </div>
      <div id="map" style="height:320px; background:#eef2ee;"></div>
      <div id="mapFooter" style="padding:10px 18px; font-size:0.8rem; color:#666; border-top:1px solid var(--color-border-light);">
        Waiting for runner to share location…
      </div>
    </div>
    <?php endif; ?>

    <div class="section-card">
      <p style="color:#888;font-size:0.85rem;">Shop</p>
      <p style="font-weight:700;margin-bottom:12px;"><?= e($order['shop_name']) ?></p>

      <p style="color:#888;font-size:0.85rem;">Status</p>
      <p><span class="badge badge-primary"><?= ucfirst(str_replace('_',' ',$order['status'])) ?></span></p>

      <p style="color:#888;font-size:0.85rem;margin-top:12px;">Delivery Address</p>
      <p><?= e($order['delivery_address']) ?></p>

      <p style="color:#888;font-size:0.85rem;margin-top:12px;">Items</p>
      <?php foreach ($items as $i): ?>
        <p style="display:flex;justify-content:space-between;font-size:0.9rem;">
          <span><?= (int)$i['quantity'] ?>× <?= e($i['item_name']) ?></span>
          <span>$<?= number_format((float)$i['subtotal'],2) ?></span>
        </p>
      <?php endforeach; ?>

      <p style="display:flex;justify-content:space-between;font-weight:700;font-size:1.05rem;border-top:1px solid #eee;padding-top:12px;margin-top:12px;">
        <span>Total</span><span style="color:var(--color-primary);">$<?= number_format((float)$order['total'],2) ?></span>
      </p>
    </div>
  </div>

  <?php if ($autoRefresh): ?>
  <!-- Auto-refresh every 30s until the runner picks up so the map appears on its own -->
  <script>setTimeout(function(){ location.reload(); }, 30000);</script>
  <?php endif; ?>

  <?php if ($showMap): ?>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
  (function() {
    var orderId = <?= (int)$order['id'] ?>;

    /* Fallback center — UIU campus (Bashundhara, Dhaka) */
    var DEFAULT_LAT = 23.8150, DEFAULT_LNG = 90.4255;

    var map = L.map('map').setView([DEFAULT_LAT, DEFAULT_LNG], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var runnerIcon = L.divIcon({
      className: '',
      html:
        '<div style="background:#1f7a3e;width:34px;height:34px;border-radius:50%;' +
        'display:flex;align-items:center;justify-content:center;border:3px solid #fff;' +
        'box-shadow:0 3px 12px rgba(0,0,0,.4);color:#fff;font-size:14px;">' +
        '<i class="fa-solid fa-person-biking"></i></div>',
      iconSize:   [34, 34],
      iconAnchor: [17, 17]
    });

    var marker   = null;
    var firstFix = true;

    function updateLastText(sec) {
      var t = document.getElementById('lastUpdate');
      if (sec === null || sec === undefined) { t.textContent = 'Waiting…'; return; }
      if (sec < 10)      t.textContent = '● LIVE';
      else if (sec < 60) t.textContent = sec + 's ago';
      else               t.textContent = Math.floor(sec/60) + 'm ago';
      t.style.color = sec < 15 ? '#1f7a3e' : '#888';
      t.style.fontWeight = sec < 15 ? '700' : '400';
    }

    function poll() {
      fetch('get-location.php?order_id=' + orderId, { cache: 'no-store' })
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (!d.ok) return;

          if (!d.has_location) {
            document.getElementById('mapFooter').textContent =
              'Waiting for runner to start sharing location…';
            updateLastText(null);
            return;
          }

          var latlng = [d.latitude, d.longitude];

          if (marker === null) {
            marker = L.marker(latlng, { icon: runnerIcon }).addTo(map);
          } else {
            marker.setLatLng(latlng);
          }

          if (firstFix) {
            map.setView(latlng, 16);
            firstFix = false;
          }

          document.getElementById('mapFooter').innerHTML =
            'Runner: <strong>' + (d.runner_name || '—') + '</strong> — live tracking active';
          updateLastText(d.seconds_ago);
        })
        .catch(function() { /* silent */ });
    }

    poll();
    setInterval(poll, 5000);
  })();
  </script>
  <?php endif; ?>
</body>
</html>