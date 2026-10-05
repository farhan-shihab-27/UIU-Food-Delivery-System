<?php
require 'config.php';
require 'auth.php';
require_role('student');

$orderId = (int)($_GET['order_id'] ?? 0);
$uid = $_SESSION['user_id'];
$me = $pdo->query("SELECT id FROM students WHERE user_id=$uid")->fetch();

$o = $pdo->prepare("SELECT * FROM orders WHERE id=? AND student_id=?");
$o->execute([$orderId, $me['id']]);
$order = $o->fetch();
if (!$order) die('Order not found.');

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
$items->execute([$orderId]); $items = $items->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order Success</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="success-page">
    <a href="order-history.php" class="success-back-link">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12" /><polyline points="12 19 5 12 12 5" /></svg>
      Back
    </a>

    <div class="success-card">
      <div class="success-icon-wrap">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8"><polyline points="20 6 9 17 4 12" /></svg>
      </div>

      <h1 class="success-title">Order Placed Successfully!</h1>
      <p class="success-desc">Your order is being prepared. Track it live from your dashboard.</p>

      <div class="success-order-box">
        <div class="success-order-id-row">
          <span class="success-order-id-label">Order ID</span>
          <span class="success-order-id-val"><?= e($order['order_code']) ?></span>
        </div>
        <div class="success-order-items">
          <?php foreach ($items as $it): ?>
            <div class="success-order-item-row">
              <span><?= (int)$it['quantity'] ?>x <?= e($it['item_name']) ?></span>
              <span>$<?= number_format((float)$it['subtotal'],2) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="success-order-total-row">
          <span class="success-order-total-label">Total Paid</span>
          <span class="success-order-total-val">$<?= number_format((float)$order['total'],2) ?></span>
        </div>
      </div>

      <div class="success-btns">
        <a href="rate-order.php?order_id=<?= (int)$order['id'] ?>" class="btn btn-primary">Rate Your Delivery</a>
        <a href="student-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
      </div>
    </div>
  </div>
</body>
</html>