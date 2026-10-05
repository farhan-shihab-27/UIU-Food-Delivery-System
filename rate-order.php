<?php
require 'config.php';
require 'auth.php';
require_role('student');

$uid = $_SESSION['user_id'];
$me  = $pdo->query("SELECT id FROM students WHERE user_id=$uid")->fetch();
$sid = $me['id'];

$orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);

/* Fetch order + shop + runner details in one go */
$stmt = $pdo->prepare("
    SELECT o.*,
           s.name AS shop_name,
           u.full_name AS runner_name
    FROM orders o
    JOIN shops s ON s.id = o.shop_id
    LEFT JOIN runners r ON r.id = o.runner_id
    LEFT JOIN users   u ON u.id = r.user_id
    WHERE o.id = ? AND o.student_id = ?
");
$stmt->execute([$orderId, $sid]);
$order = $stmt->fetch();
if (!$order) die('Order not found.');

$hasRunner = !empty($order['runner_id']) && !empty($order['runner_name']);

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shopRating   = (int)($_POST['shop_rating']   ?? 0);
    $runnerRating = (int)($_POST['runner_rating'] ?? 0);
    $comment      = trim($_POST['comment'] ?? '');

    if ($shopRating < 1 || $shopRating > 5) {
        $err = 'Please rate the shop with 1–5 stars.';
    } elseif ($hasRunner && ($runnerRating < 1 || $runnerRating > 5)) {
        $err = 'Please rate the delivery runner with 1–5 stars.';
    } else {
        /* One review per order */
        $chk = $pdo->prepare("SELECT id FROM reviews WHERE order_id=?");
        $chk->execute([$orderId]);
        if ($chk->fetch()) {
            $err = 'You already rated this order.';
        } else {
            /* overall_rating is the legacy NOT NULL column — mirror shop rating into it */
            $pdo->prepare("INSERT INTO reviews
                            (order_id, student_id, shop_id, runner_id,
                             overall_rating, shop_rating, runner_rating, comment)
                           VALUES (?,?,?,?,?,?,?,?)")
                ->execute([
                    $orderId, $sid, $order['shop_id'], $order['runner_id'],
                    $shopRating, $shopRating,
                    $hasRunner ? $runnerRating : null,
                    $comment
                ]);

            /* Recompute shop average from shop_rating column */
            $pdo->prepare("UPDATE shops
                           SET rating_avg   = (SELECT AVG(shop_rating) FROM reviews WHERE shop_id = ? AND shop_rating IS NOT NULL),
                               rating_count = (SELECT COUNT(*)       FROM reviews WHERE shop_id = ? AND shop_rating IS NOT NULL)
                           WHERE id = ?")
                ->execute([$order['shop_id'], $order['shop_id'], $order['shop_id']]);

            /* Recompute runner average from runner_rating column */
            if ($hasRunner) {
                $pdo->prepare("UPDATE runners
                               SET rating_avg   = (SELECT AVG(runner_rating) FROM reviews WHERE runner_id = ? AND runner_rating IS NOT NULL),
                                   rating_count = (SELECT COUNT(*)         FROM reviews WHERE runner_id = ? AND runner_rating IS NOT NULL)
                               WHERE id = ?")
                    ->execute([$order['runner_id'], $order['runner_id'], $order['runner_id']]);
            }

            $msg = 'Thank you for your feedback!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Rate Order</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="rate-page">
    <div class="rate-card">
      <div class="rate-icon-wrap">
        <svg width="38" height="38" viewBox="0 0 24 24" fill="#f5a623"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg>
      </div>
      <h1 class="rate-title">Rate Your Order</h1>
      <p class="rate-subtitle">Order <?= e($order['order_code']) ?></p>

      <?php if ($msg): ?>
        <div style="background:#e8f5e9;color:#2e7d32;padding:10px;border-radius:8px;font-weight:600;margin-bottom:14px;"><?= e($msg) ?></div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div style="background:#fdecea;color:#c62828;padding:10px;border-radius:8px;font-weight:600;margin-bottom:14px;"><?= e($err) ?></div>
      <?php endif; ?>

      <?php if (!$msg): ?>
      <form method="post">
        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>" />

        <!-- ============= SHOP RATING ============= -->
        <div style="border:1px solid var(--color-border-light);border-radius:10px;padding:16px;margin-bottom:14px;">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <i class="fa-solid fa-store" style="color:var(--color-primary);"></i>
            <span style="font-size:0.85rem;font-weight:700;color:var(--color-text-primary);">
              <?= e($order['shop_name']) ?>
            </span>
          </div>
          <span class="star-rating-label-text">Rate the Shop</span>
          <div class="star-rating" style="margin-bottom:0;">
            <input type="radio" id="shopstar5" name="shop_rating" value="5" /><label for="shopstar5">&#9733;</label>
            <input type="radio" id="shopstar4" name="shop_rating" value="4" /><label for="shopstar4">&#9733;</label>
            <input type="radio" id="shopstar3" name="shop_rating" value="3" /><label for="shopstar3">&#9733;</label>
            <input type="radio" id="shopstar2" name="shop_rating" value="2" /><label for="shopstar2">&#9733;</label>
            <input type="radio" id="shopstar1" name="shop_rating" value="1" /><label for="shopstar1">&#9733;</label>
          </div>
        </div>

        <!-- ============= RUNNER RATING ============= -->
        <?php if ($hasRunner): ?>
        <div style="border:1px solid var(--color-border-light);border-radius:10px;padding:16px;margin-bottom:14px;">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <i class="fa-solid fa-person-biking" style="color:var(--color-primary);"></i>
            <span style="font-size:0.85rem;font-weight:700;color:var(--color-text-primary);">
              <?= e($order['runner_name']) ?>
            </span>
          </div>
          <span class="star-rating-label-text">Rate the Delivery Runner</span>
          <div class="star-rating" style="margin-bottom:0;">
            <input type="radio" id="runnerstar5" name="runner_rating" value="5" /><label for="runnerstar5">&#9733;</label>
            <input type="radio" id="runnerstar4" name="runner_rating" value="4" /><label for="runnerstar4">&#9733;</label>
            <input type="radio" id="runnerstar3" name="runner_rating" value="3" /><label for="runnerstar3">&#9733;</label>
            <input type="radio" id="runnerstar2" name="runner_rating" value="2" /><label for="runnerstar2">&#9733;</label>
            <input type="radio" id="runnerstar1" name="runner_rating" value="1" /><label for="runnerstar1">&#9733;</label>
          </div>
        </div>
        <?php endif; ?>

        <!-- ============= COMMENT ============= -->
        <textarea name="comment" class="rate-comment" placeholder="Share your experience..."></textarea>

        <button type="submit" class="btn btn-primary" style="width:100%;">
          <i class="fa-solid fa-paper-plane"></i> Submit Rating
        </button>
      </form>
      <?php endif; ?>

      <a href="student-dashboard.php" class="rate-back-link">
        <i class="fa-solid fa-house"></i> Back to Dashboard
      </a>
    </div>
  </div>
</body>
</html>