<?php
require 'config.php';
require 'auth.php';
require_role('student');

$uid = $_SESSION['user_id'];
$me  = $pdo->query("SELECT s.id AS sid, s.wallet_balance, s.default_address FROM students s WHERE s.user_id=$uid")->fetch();
$sid = $me['sid'];

/* Remove a cart item */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    $pdo->prepare("DELETE FROM cart_items WHERE id=? AND student_id=?")->execute([(int)$_POST['remove_id'], $sid]);
    header('Location: checkout.php'); exit;
}

/* Fetch cart (menu_id included so order_items can store the FK) */
$cart = $pdo->prepare("SELECT ci.id, ci.quantity, mi.id AS menu_id, mi.name, mi.price, mi.shop_id FROM cart_items ci JOIN menu_items mi ON mi.id=ci.menu_item_id WHERE ci.student_id=?");
$cart->execute([$sid]); $cart = $cart->fetchAll();

$subtotal = 0; foreach ($cart as $c) $subtotal += $c['quantity'] * $c['price'];
$deliveryFee = $cart ? 1.50 : 0;
$vat = round($subtotal * 0.05, 2);
$total = $subtotal + $deliveryFee + $vat;

$error = '';
$success = '';

/* Place order */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $address = trim($_POST['address'] ?? '');
    $payment = $_POST['payment_method'] ?? 'wallet';
    $promo   = strtoupper(trim($_POST['promo_code'] ?? ''));
    $discount = 0; $promoId = null;

    if (!$cart) {
        $error = 'Your cart is empty.';
    } elseif ($address === '') {
        $error = 'Please enter a delivery address.';
    } else {
        if ($promo !== '') {
            $p = $pdo->prepare("SELECT * FROM promo_codes WHERE code=? AND is_active=1 LIMIT 1");
            $p->execute([$promo]); $pr = $p->fetch();
            if ($pr) {
                if ($pr['discount_type'] === 'percent') {
                    $discount = round($subtotal * $pr['discount_value'] / 100, 2);
                    if ($pr['max_discount'] && $discount > $pr['max_discount']) $discount = (float)$pr['max_discount'];
                } else { $discount = (float)$pr['discount_value']; }
                $promoId = $pr['id'];
            }
        }
        $grand = max(0, $subtotal + $deliveryFee + $vat - $discount);

        if ($payment === 'wallet' && $me['wallet_balance'] < $grand) {
            $error = 'Insufficient wallet balance. Choose Cash on Delivery or top up.';
        } else {
            try {
                $pdo->beginTransaction();
                $code = '#' . random_int(1000, 9999);
                $payStatus = $payment === 'wallet' ? 'paid' : 'unpaid';
                $pdo->prepare("INSERT INTO orders (order_code, student_id, shop_id, delivery_address, payment_method, payment_status, subtotal, delivery_fee, vat, discount, total, promo_code_id, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'pending')")
                    ->execute([$code, $sid, $cart[0]['shop_id'], $address, $payment, $payStatus, $subtotal, $deliveryFee, $vat, $discount, $grand, $promoId]);
                $oid = $pdo->lastInsertId();

                foreach ($cart as $c) {
                    $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, item_name, unit_price, quantity, subtotal) VALUES (?,?,?,?,?,?)")
                        ->execute([$oid, $c['menu_id'], $c['name'], $c['price'], $c['quantity'], $c['quantity'] * $c['price']]);
                }

                $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (?, 'pending', 'Order placed', ?)")
                    ->execute([$oid, $uid]);

                if ($payment === 'wallet') {
                    $pdo->prepare("UPDATE students SET wallet_balance = wallet_balance - ? WHERE id=?")->execute([$grand, $sid]);
                    $pdo->prepare("INSERT INTO wallet_transactions (student_id, order_id, type, amount, direction, method, description) VALUES (?,?, 'order_payment', ?, 'debit', 'wallet', ?)")
                        ->execute([$sid, $oid, $grand, 'Order ' . $code]);
                }

                $pdo->prepare("DELETE FROM cart_items WHERE student_id=?")->execute([$sid]);

                /* Notify shop owner */
                $ownerStmt = $pdo->prepare("SELECT u.id AS owner_user_id, s.name AS shop_name FROM shops s JOIN users u ON u.id = s.owner_id WHERE s.id = ?");
                $ownerStmt->execute([$cart[0]['shop_id']]);
                $ownerRow = $ownerStmt->fetch();
                if ($ownerRow) {
                    notify($pdo, $ownerRow['owner_user_id'],
                        'New Order Received',
                        'Order ' . $code . ' placed by ' . $_SESSION['name'] . ' — $' . number_format($grand, 2),
                        'order',
                        'shop-orders.php');
                }

                $pdo->commit();

                header('Location: order-success.php?order_id=' . $oid); exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Order failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body class="checkout-page">
  <header class="pub-navbar">
    <div class="pub-navbar-inner">
      <div class="pub-navbar-left">
        <a href="student-dashboard.php" class="pub-navbar-back">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12" /><polyline points="12 19 5 12 12 5" /></svg>
          Back
        </a>
        <a href="index.php" class="pub-navbar-brand">
          <div class="pub-navbar-brand-icon">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg>
          </div>
          <span class="pub-navbar-brand-text">UIU Food Delivery</span>
        </a>
      </div>
      <a href="profile.php" class="pub-navbar-user">
        <img src="https://images.unsplash.com/photo-1607746882042-944635dfe10e?w=80&q=80&auto=format&fit=crop" alt="User" />
        <?= e($_SESSION['name']) ?>
      </a>
    </div>
  </header>

  <div class="checkout-body">
    <div class="checkout-left">
      <h1 class="checkout-main-title">Checkout</h1>

      <?php if ($error): ?>
        <div style="background:#fdecea;color:#c62828;padding:10px 14px;border-radius:8px;font-weight:600;margin-bottom:14px;"><?= e($error) ?></div>
      <?php endif; ?>

      <?php if (!$cart): ?>
        <div class="checkout-section-card">
          <p style="color:#888;">Your cart is empty. <a href="index.php" style="color:var(--color-primary);font-weight:700;">Browse restaurants</a></p>
        </div>
      <?php else: ?>
        <form method="post" action="checkout.php">
          <div class="checkout-section-card">
            <p class="checkout-section-label">1. Delivery Address</p>
            <div class="input-icon-wrap">
              <span class="input-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" /><circle cx="12" cy="10" r="3" /></svg>
              </span>
              <input type="text" name="address" class="checkout-address-input form-input" placeholder="Enter your delivery address" value="<?= e($me['default_address'] ?? '') ?>" required />
            </div>
          </div>

          <div class="checkout-section-card">
            <p class="checkout-section-label">2. Payment Method</p>
            <div class="payment-option selected">
              <span class="payment-radio-dot"></span>
              <div class="payment-option-info">
                <p class="payment-option-name">UIU Student Wallet</p>
                <p class="payment-option-sub">Balance: $<?= number_format((float)$me['wallet_balance'],2) ?></p>
              </div>
            </div>
            <select name="payment_method" class="form-input" style="margin-top:10px;">
              <option value="wallet">Wallet</option>
              <option value="cash">Cash on Delivery</option>
            </select>
          </div>

          <div class="checkout-section-card">
            <p class="checkout-section-label">Promo Code</p>
            <input type="text" name="promo_code" class="form-input" placeholder="Enter code e.g. UIU10" />
          </div>

          <div class="checkout-summary-card">
            <p class="checkout-summary-title">Order Summary</p>
            <?php foreach ($cart as $c): ?>
              <div class="checkout-summary-row">
                <span><?= (int)$c['quantity'] ?>x <?= e($c['name']) ?></span>
                <span>$<?= number_format($c['quantity'] * $c['price'], 2) ?></span>
              </div>
            <?php endforeach; ?>
            <div class="checkout-summary-row" style="margin-top: var(--space-3); padding-top: var(--space-3); border-top: 1px solid var(--color-border-light);">
              <span>Subtotal</span><span>$<?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="checkout-summary-row"><span>Delivery Fee</span><span>$<?= number_format($deliveryFee, 2) ?></span></div>
            <div class="checkout-summary-row"><span>VAT (5%)</span><span>$<?= number_format($vat, 2) ?></span></div>
            <div class="checkout-summary-row total">
              <span>Total</span><span class="cht-val">$<?= number_format($total, 2) ?></span>
            </div>
          </div>

          <button type="submit" name="place_order" value="1" class="btn btn-primary btn-full btn-lg">Place Order</button>
        </form>

        <div style="margin-top:20px;">
          <?php foreach ($cart as $c): ?>
            <form method="post" style="display:inline-block;margin:4px;">
              <input type="hidden" name="remove_id" value="<?= (int)$c['id'] ?>" />
              <button type="submit" class="btn btn-ghost btn-sm">Remove <?= e($c['name']) ?></button>
            </form>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="checkout-right">
      <h1 class="track-main-title">Track Your Order</h1>
      <div class="track-progress-card">
        <p class="track-progress-label">Active Progress</p>
        <div class="track-steps-row">
          <div class="track-step done"><div class="track-step-circle done"></div><span class="track-step-label">Placed</span></div>
          <div class="track-step-connector done"></div>
          <div class="track-step"><div class="track-step-circle"></div><span class="track-step-label">Accepted</span></div>
          <div class="track-step-connector"></div>
          <div class="track-step"><div class="track-step-circle"></div><span class="track-step-label">Preparing</span></div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>