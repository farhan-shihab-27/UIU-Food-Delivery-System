<?php
require 'config.php';
require 'auth.php';
require_role('student');

$uid = $_SESSION['user_id'];
$me  = $pdo->query("SELECT s.id AS sid FROM students s WHERE s.user_id = $uid")->fetch();
$sid = $me['sid'];

$shopId = (int)($_GET['shop_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM shops WHERE id=? AND status='approved'");
$stmt->execute([$shopId]); $shop = $stmt->fetch();
if (!$shop) { die('Shop not found.'); }

/* Handle "Add to Cart" POST */
$cartMsg = '';
$cartErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu_item_id'])) {
    $mid = (int)$_POST['menu_item_id'];
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    /* Verify item exists, belongs to this shop, is available and has stock */
    $chk = $pdo->prepare("SELECT stock_quantity, is_available FROM menu_items WHERE id=? AND shop_id=?");
    $chk->execute([$mid, $shopId]);
    $mi = $chk->fetch();

    if (!$mi || !$mi['is_available']) {
        $cartErr = 'That item is not available.';
    } elseif ((int)$mi['stock_quantity'] <= 0) {
        $cartErr = 'That item is out of stock.';
    } else {
        /* How many are already in the cart? */
        $inCart = 0;
        $cq = $pdo->prepare("SELECT quantity FROM cart_items WHERE student_id=? AND menu_item_id=?");
        $cq->execute([$sid, $mid]);
        $crow = $cq->fetch();
        if ($crow) $inCart = (int)$crow['quantity'];

        $remaining = (int)$mi['stock_quantity'] - $inCart;
        if ($remaining <= 0) {
            $cartErr = 'You already have all available stock in your cart.';
        } else {
            if ($qty > $remaining) $qty = $remaining;

            if ($crow) {
                $pdo->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id=?")->execute([$qty, $crow['id']]);
                /* need id — re-fetch not necessary; do it via student+item */
                $pdo->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE student_id=? AND menu_item_id=?")->execute([0, $sid, $mid]);
            } else {
                $pdo->prepare("INSERT INTO cart_items (student_id, menu_item_id, quantity) VALUES (?,?,?)")->execute([$sid, $mid, $qty]);
            }
            $cartMsg = 'Item added to cart!';
        }
    }
}

/* Cart total */
$cart = $pdo->prepare("SELECT ci.quantity, mi.name, mi.price FROM cart_items ci JOIN menu_items mi ON mi.id=ci.menu_item_id WHERE ci.student_id=?");
$cart->execute([$sid]); $cart = $cart->fetchAll();
$cartCount = 0; $subtotal = 0;
foreach ($cart as $c) { $cartCount += $c['quantity']; $subtotal += $c['quantity'] * $c['price']; }

/* Menu items of this shop */
$items = $pdo->prepare("SELECT * FROM menu_items WHERE shop_id=? ORDER BY category, name");
$items->execute([$shopId]); $items = $items->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($shop['name']) ?> — Menu</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body style="background-color: var(--color-white);">
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
      <a href="checkout.php" class="pub-navbar-user" style="text-decoration:none;">
        <i class="fa-solid fa-cart-shopping" style="color:var(--color-primary);margin-right:6px;"></i>
        Cart (<?= $cartCount ?>)
      </a>
    </div>
  </header>

  <div class="menu-hero">
    <img src="<?= e($shop['image_url'] ?: 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1400&q=80') ?>" alt="<?= e($shop['name']) ?>" class="menu-hero-img" />
    <div class="menu-hero-overlay"></div>
    <div class="menu-hero-content">
      <div class="menu-hero-badges">
        <span class="menu-hero-open">OPEN</span>
        <span class="menu-hero-rating">★ <?= number_format((float)$shop['rating_avg'],1) ?> (<?= (int)$shop['rating_count'] ?> ratings)</span>
      </div>
      <h1 class="menu-hero-title"><?= e($shop['name']) ?></h1>
      <div class="menu-hero-meta">
        <span>Cuisine: <?= e($shop['cuisine'] ?: 'Fast Food') ?></span>
      </div>
    </div>
  </div>

  <div class="menu-body">
    <div>
      <h2 class="menu-section-title">Our Full Menu</h2>

      <?php if ($cartMsg): ?>
        <div style="background:#e8f5e9;color:#2e7d32;padding:10px 14px;border-radius:8px;font-weight:600;margin-bottom:16px;"><?= e($cartMsg) ?></div>
      <?php endif; ?>
      <?php if ($cartErr): ?>
        <div style="background:#fdecea;color:#c62828;padding:10px 14px;border-radius:8px;font-weight:600;margin-bottom:16px;"><?= e($cartErr) ?></div>
      <?php endif; ?>

      <div class="menu-grid">
        <?php foreach ($items as $it):
          $stock = (int)$it['stock_quantity'];
          $canOrder = $it['is_available'] && $stock > 0;
        ?>
          <div class="menu-item-card">
            <div class="menu-item-img">
              <img src="<?= e($it['image_url'] ?: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&q=80') ?>" alt="<?= e($it['name']) ?>" />
            </div>
            <div class="menu-item-body">
              <div class="menu-item-name-row">
                <span class="menu-item-name"><?= e($it['name']) ?></span>
                <?php if ($canOrder): ?>
                  <span class="menu-item-avail avail-yes">Available</span>
                <?php else: ?>
                  <span class="menu-item-avail avail-no"><?= $it['is_available'] ? 'Out of Stock' : 'Unavailable' ?></span>
                <?php endif; ?>
              </div>
              <p class="menu-item-desc"><?= e($it['description'] ?: '') ?></p>
              <p class="menu-item-price">$<?= number_format((float)$it['price'],2) ?></p>

              <?php if ($canOrder): ?>
                <form method="post" action="restaurant-menu.php?shop_id=<?= (int)$shop['id'] ?>" style="display:flex;gap:8px;align-items:center;margin-top:8px;">
                  <input type="hidden" name="menu_item_id" value="<?= (int)$it['id'] ?>" />
                  <input type="number" name="quantity" value="1" min="1" max="<?= min(10, $stock) ?>" style="width:55px;padding:6px 8px;border:1.5px solid #ddd;border-radius:8px;font-size:0.85rem;text-align:center;" />
                  <button type="submit" class="menu-add-btn">Add to Cart</button>
                </form>
                <p style="font-size:0.7rem;color:#888;margin-top:6px;">Only <?= $stock ?> left in stock</p>
              <?php else: ?>
                <div class="menu-add-btn-disabled">Out of Stock</div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$items): ?>
          <p style="color:#888;">No menu items yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <aside class="cart-panel">
      <div class="cart-panel-header">
        <span class="cart-panel-title">Your Cart</span>
        <span class="cart-item-count"><?= $cartCount ?> item(s)</span>
      </div>

      <?php if ($cart): foreach ($cart as $c): ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:0.85rem;">
          <span><?= (int)$c['quantity'] ?>× <?= e($c['name']) ?></span>
          <span style="font-weight:600;color:var(--color-primary);">$<?= number_format($c['quantity'] * $c['price'], 2) ?></span>
        </div>
      <?php endforeach; else: ?>
        <p style="color:#888;font-size:0.85rem;">Cart is empty.</p>
      <?php endif; ?>

      <div class="cart-totals">
        <div class="cart-total-row grand">
          <span>Subtotal</span>
          <span class="cart-total-val">$<?= number_format($subtotal, 2) ?></span>
        </div>
      </div>

      <a href="checkout.php" class="btn btn-primary btn-full" style="margin-top: var(--space-4);">Proceed to Checkout</a>
    </aside>
  </div>

  <footer class="home-footer">
    <div class="home-footer-inner">
      <div class="home-footer-bottom">
        <p class="footer-copyright">&copy; 2026 UIU Food Delivery. All Rights Reserved.</p>
      </div>
    </div>
  </footer>
</body>
</html>