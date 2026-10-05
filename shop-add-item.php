<?php
require 'config.php';
require 'auth.php';
require_role('shop_owner');

$uid = $_SESSION['user_id'];
$shop = $pdo->query("SELECT * FROM shops WHERE owner_id=$uid")->fetch();
$shopId = $shop['id'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $cat   = $_POST['category'] ?? 'others';
    $prep  = (int)($_POST['prep_time'] ?? 0);
    $cal   = (int)($_POST['calories'] ?? 0);
    $stock = max(0, (int)($_POST['stock_quantity'] ?? 0));
    $avail = isset($_POST['available']) ? 1 : 0;

    if ($name === '') $errors[] = 'Item name is required.';
    if ($price <= 0)  $errors[] = 'Price must be greater than zero.';

    $imageUrl = null;
    if (!empty($_FILES['image']['name'])) {
        $allowedImg = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedImg)) {
            $errors[] = 'Image must be JPG, PNG or WEBP.';
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image too large (max 5 MB).';
        } else {
            $dir = __DIR__ . '/uploads/menu';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $fname = 'item_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $fname)) {
                $imageUrl = 'uploads/menu/' . $fname;
            } else {
                $errors[] = 'Failed to save image.';
            }
        }
    }

    if (!$errors) {
        $pdo->prepare("INSERT INTO menu_items (shop_id, name, description, category, price, prep_time_min, calories, is_available, stock_quantity, image_url) VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([$shopId, $name, $desc, $cat, $price, $prep ?: null, $cal ?: null, $avail, $stock, $imageUrl]);
        header('Location: shop-menu.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add Menu Item</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-logo"><div class="sidebar-logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg></div><span class="sidebar-logo-text">UIU Food Portal</span></div>
      <nav class="sidebar-nav">
        <a href="shop-dashboard.php" class="sidebar-nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="shop-orders.php"    class="sidebar-nav-item"><i class="fa-solid fa-receipt"></i> Orders</a>
        <a href="shop-menu.php"      class="sidebar-nav-item active"><i class="fa-solid fa-utensils"></i> Menu</a>
        <a href="sales-history.php"  class="sidebar-nav-item"><i class="fa-solid fa-dollar-sign"></i> Sales</a>
        <a href="shop-reports.php"   class="sidebar-nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</a>

        <a href="notifications.php" class="sidebar-nav-item">
          <i class="fa-solid fa-bell"></i> Notifications
          <?php $__uc = unread_notification_count($pdo, $_SESSION['user_id']); if ($__uc > 0): ?>
            <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:99px;font-size:0.65rem;padding:1px 6px;font-weight:700;"><?= $__uc ?></span>
          <?php endif; ?>
        </a>

        <a href="profile.php"        class="sidebar-nav-item"><i class="fa-solid fa-user"></i> Profile</a>
      </nav>
      <div class="sidebar-user">
        <img src="<?= e(avatar_url()) ?>" alt="<?= e($_SESSION['name']) ?>" class="sidebar-user-avatar" />
        <div class="sidebar-user-info">
          <p class="sidebar-user-name"><?= e($_SESSION['name']) ?></p>
          <p class="sidebar-user-role">Shop Owner</p>
          <a href="logout.php" class="sidebar-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <div class="dash-topbar">
        <div class="dash-topbar-left">
          <a href="shop-menu.php" class="dash-topbar-back"><i class="fa-solid fa-arrow-left"></i> Back to Menu</a>
          <h1 class="dash-topbar-title">Add New Menu Item</h1>
        </div>
      </div>

      <div style="max-width:640px;margin:24px auto;padding:0 32px;">
        <?php if ($errors): ?>
          <div style="background:#fdecea;color:#c62828;padding:10px 14px;border-radius:8px;font-weight:600;margin-bottom:14px;">
            <?php foreach ($errors as $e): ?><div>• <?= e($e) ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="section-card" enctype="multipart/form-data">
          <div class="form-group">
            <label class="form-label">Item Image</label>
            <input type="file" name="image" class="form-input" accept="image/*" />
            <p style="font-size:0.75rem;color:#888;margin-top:4px;">JPG / PNG / WEBP, max 5 MB. Optional — a default image will be shown if none uploaded.</p>
          </div>

          <div class="form-group"><label class="form-label">Item Name *</label><input type="text" name="name" class="form-input" required /></div>
          <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-input" rows="3"></textarea></div>
          <div class="form-group"><label class="form-label">Price (USD) *</label><input type="number" step="0.01" min="0" name="price" class="form-input" required /></div>

          <div class="form-group">
            <label class="form-label">Stock Quantity *</label>
            <input type="number" name="stock_quantity" class="form-input" min="0" value="10" required />
            <p style="font-size:0.75rem;color:#888;margin-top:4px;">How many units are available today. Set 0 to mark as out of stock.</p>
          </div>

          <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category" class="form-input">
              <option value="burgers">Burgers</option><option value="pizza">Pizza</option>
              <option value="rice">Rice</option><option value="snacks">Snacks</option>
              <option value="drinks">Drinks</option><option value="desserts">Desserts</option>
              <option value="others">Others</option>
            </select>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group"><label class="form-label">Prep Time (min)</label><input type="number" name="prep_time" class="form-input" min="0" /></div>
            <div class="form-group"><label class="form-label">Calories</label><input type="number" name="calories" class="form-input" min="0" /></div>
          </div>

          <div class="form-group"><label><input type="checkbox" name="available" checked /> Available for order</label></div>
          <button type="submit" class="btn btn-primary btn-full">Add Item to Menu</button>
        </form>
      </div>
    </main>
  </div>
</body>
</html>