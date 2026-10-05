<?php
require 'config.php';
require 'auth.php';

$shops = $pdo->query("SELECT * FROM shops WHERE status='approved' ORDER BY rating_avg DESC LIMIT 3")->fetchAll();
$foods = $pdo->query("SELECT * FROM menu_items WHERE is_available=1 ORDER BY RAND() LIMIT 4")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UIU Food Delivery — Order Food Around Campus</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body class="home-page">

  <header class="home-navbar">
    <div class="home-navbar-inner">
      <a href="index.php" class="navbar-brand">
        <div class="navbar-brand-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
            stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg>
        </div>
        <span class="navbar-brand-text">UIU Food Delivery</span>
      </a>

      <nav class="navbar-links">
        <a href="index.php" class="navbar-link active">Home</a>
        <a href="#shops" class="navbar-link">Shops</a>
        <a href="#about" class="navbar-link">About</a>
        <a href="#contact" class="navbar-link">Contact</a>
      </nav>

      <?php if (is_logged_in()): ?>
        <a href="<?= redirect_by_role($_SESSION['role']) ?>" class="btn btn-primary btn-sm">My Dashboard</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-primary btn-sm">Login</a>
      <?php endif; ?>
    </div>
  </header>

  <section class="hero-section">
    <div class="hero-content">
      <h1 class="hero-title">Order Food Around Campus Easily</h1>
      <p class="hero-desc">Fresh meals from your favorite campus restaurants, delivered by fellow students right to your dorm or department.</p>
      <div class="hero-search-row">
        <div class="hero-search-wrap">
          <span class="search-icon"><i class="fa-solid fa-magnifying-glass" style="font-size:0.9rem;"></i></span>
          <input type="text" class="hero-search-input" placeholder="Search for food, snacks, drinks or restaurants..." />
        </div>
        <a href="<?= is_logged_in() ? redirect_by_role($_SESSION['role']) : 'login.php' ?>" class="btn btn-primary">Order Now</a>
      </div>
    </div>
    <div class="hero-image-wrap">
      <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=700&q=80&auto=format&fit=crop" alt="Delicious burger with fries" />
    </div>
  </section>

  <section class="home-section" id="shops" style="padding-top: var(--space-6);">
    <div class="section-header">
      <h2 class="section-title" style="margin-bottom:0;">Featured Campus Restaurants</h2>
    </div>
    <div class="restaurants-grid">
      <?php foreach ($shops as $s): ?>
        <a href="<?= is_logged_in() && $_SESSION['role']==='student' ? 'restaurant-menu.php?shop_id='.$s['id'] : 'login.php' ?>" class="restaurant-card">
          <div class="restaurant-card-img">
            <img src="<?= e($s['image_url'] ?: 'https://images.unsplash.com/photo-1552566626-52f8b828add9?w=600&q=80') ?>" alt="<?= e($s['name']) ?>" />
            <span class="restaurant-open-badge">OPEN</span>
          </div>
          <div class="restaurant-card-body">
            <h3 class="restaurant-card-name"><?= e($s['name']) ?></h3>
            <p class="restaurant-card-desc"><?= e($s['description'] ?: 'Delicious campus meals') ?></p>
            <div class="restaurant-meta">
              <span class="rating">★ <?= number_format((float)$s['rating_avg'], 1) ?></span>
            </div>
            <div class="view-menu-btn">View Menu</div>
          </div>
        </a>
      <?php endforeach; ?>
      <?php if (!$shops): ?><p style="color:#888;">No approved shops yet.</p><?php endif; ?>
    </div>
  </section>

  <section style="background-color: var(--color-white); padding: var(--space-10) 0;">
    <div style="max-width: 1180px; margin: 0 auto; padding: 0 var(--space-8);">
      <h2 class="section-title">Popular Foods Near You</h2>
      <div class="foods-grid">
        <?php foreach ($foods as $f): ?>
          <div class="food-card">
            <div class="food-card-img">
              <img src="<?= e($f['image_url'] ?: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&q=80') ?>" alt="<?= e($f['name']) ?>" />
            </div>
            <div class="food-card-body">
              <p class="food-card-name"><?= e($f['name']) ?></p>
              <p class="food-card-price">$<?= number_format((float)$f['price'], 2) ?></p>
              <a href="<?= is_logged_in() ? 'restaurant-menu.php?shop_id='.$f['shop_id'] : 'login.php' ?>" class="add-to-cart-btn">Add to Cart</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <footer class="home-footer" id="about">
    <div class="home-footer-inner">
      <div class="home-footer-bottom">
        <p class="footer-copyright">&copy; 2026 UIU Food Delivery. All Rights Reserved.</p>
      </div>
    </div>
  </footer>
</body>
</html>