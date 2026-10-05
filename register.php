<?php
require 'config.php';
require 'auth.php';

if (is_logged_in()) { header('Location: ' . redirect_by_role($_SESSION['role'])); exit; }

$errors = [];
$old = ['name'=>'','email'=>'','phone'=>'','student_id'=>'','role'=>'student','shop_name'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name']       = trim($_POST['name'] ?? '');
    $old['email']      = trim($_POST['email'] ?? '');
    $old['phone']      = trim($_POST['phone'] ?? '');
    $old['student_id'] = trim($_POST['student_id'] ?? '');
    $old['role']       = $_POST['role'] ?? 'student';
    $old['shop_name']  = trim($_POST['shop_name'] ?? '');
    $password          = $_POST['password'] ?? '';
    $confirm           = $_POST['confirm'] ?? '';
    $vehicle           = $_POST['vehicle_type'] ?? 'bicycle';

    if ($old['name'] === '')                                          $errors[] = 'Full name required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))            $errors[] = 'Valid email required.';
    if (strlen($password) < 8)                                        $errors[] = 'Password must be 8+ characters.';
    if ($password !== $confirm)                                       $errors[] = 'Passwords do not match.';
    if (!in_array($old['role'], ['student','runner','shop_owner']))   $errors[] = 'Invalid role.';
    if (in_array($old['role'], ['student','runner']) && $old['student_id'] === '') $errors[] = 'Student ID required.';
    if ($old['role'] === 'shop_owner' && $old['shop_name'] === '')    $errors[] = 'Shop name required.';

    if ($old['role'] === 'shop_owner') {
        if (empty($_FILES['shop_thumbnail']['name'])) $errors[] = 'Shop thumbnail image required.';
        if (empty($_FILES['shop_document']['name']))  $errors[] = 'Shop verification document required.';
    }
    if ($old['role'] === 'runner') {
        if (empty($_FILES['runner_document']['name'])) $errors[] = 'Runner verification document required.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) $errors[] = 'That email is already registered.';
    }

    /* Shared upload settings */
    $allowedImg = ['jpg','jpeg','png','webp'];
    $allowedDoc = ['jpg','jpeg','png','pdf'];

    /*  Profile picture (optional, all roles) */
    $profileImagePath = null;
    if (!$errors && !empty($_FILES['profile_image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedImg)) {
            $errors[] = 'Profile picture must be JPG, PNG or WEBP.';
        } elseif ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Profile picture too large (max 5 MB).';
        } else {
            $dir = __DIR__ . '/uploads/profiles';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $fname = 'u_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dir . '/' . $fname)) {
                $profileImagePath = 'uploads/profiles/' . $fname;
            } else {
                $errors[] = 'Failed to save profile picture.';
            }
        }
    }

    /* Shop files */
    $thumbnailUrl = null;
    $documentUrl  = null;
    if (!$errors && $old['role'] === 'shop_owner') {
        $ext = strtolower(pathinfo($_FILES['shop_thumbnail']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedImg)) {
            $errors[] = 'Thumbnail must be JPG, PNG or WEBP.';
        } elseif ($_FILES['shop_thumbnail']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Thumbnail too large (max 5 MB).';
        } else {
            $dir = __DIR__ . '/uploads/shops';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $fname = 'thumb_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['shop_thumbnail']['tmp_name'], $dir . '/' . $fname)) {
                $thumbnailUrl = 'uploads/shops/' . $fname;
            } else {
                $errors[] = 'Failed to save thumbnail.';
            }
        }

        $ext2 = strtolower(pathinfo($_FILES['shop_document']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext2, $allowedDoc)) {
            $errors[] = 'Shop document must be JPG, PNG or PDF.';
        } elseif ($_FILES['shop_document']['size'] > 8 * 1024 * 1024) {
            $errors[] = 'Shop document too large (max 8 MB).';
        } else {
            $dir2 = __DIR__ . '/uploads/shops';
            if (!is_dir($dir2)) mkdir($dir2, 0777, true);
            $fname2 = 'doc_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext2;
            if (move_uploaded_file($_FILES['shop_document']['tmp_name'], $dir2 . '/' . $fname2)) {
                $documentUrl = 'uploads/shops/' . $fname2;
            } else {
                $errors[] = 'Failed to save shop document.';
            }
        }
    }

    /* Runner document */
    $runnerDocUrl = null;
    if (!$errors && $old['role'] === 'runner') {
        $ext3 = strtolower(pathinfo($_FILES['runner_document']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext3, $allowedDoc)) {
            $errors[] = 'Runner document must be JPG, PNG or PDF.';
        } elseif ($_FILES['runner_document']['size'] > 8 * 1024 * 1024) {
            $errors[] = 'Runner document too large (max 8 MB).';
        } else {
            $dir3 = __DIR__ . '/uploads/runners';
            if (!is_dir($dir3)) mkdir($dir3, 0777, true);
            $fname3 = 'rdoc_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext3;
            if (move_uploaded_file($_FILES['runner_document']['tmp_name'], $dir3 . '/' . $fname3)) {
                $runnerDocUrl = 'uploads/runners/' . $fname3;
            } else {
                $errors[] = 'Failed to save runner document.';
            }
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (full_name,email,password_hash,phone,role,profile_image,is_verified,is_active) VALUES (?,?,?,?,?,?,1,1)")
                ->execute([$old['name'], $old['email'], $hash, $old['phone'], $old['role'], $profileImagePath]);
            $uid = $pdo->lastInsertId();

            if ($old['role'] === 'student') {
                $pdo->prepare("INSERT INTO students (user_id,student_id) VALUES (?,?)")->execute([$uid, $old['student_id']]);
            } elseif ($old['role'] === 'runner') {
                $pdo->prepare("INSERT INTO runners (user_id,student_id,vehicle_type,document_url,status) VALUES (?,?,?,?,'pending')")
                    ->execute([$uid, $old['student_id'], $vehicle, $runnerDocUrl]);
            } else {
                $pdo->prepare("INSERT INTO shops (owner_id,name,status,image_url,document_url) VALUES (?,?,'pending',?,?)")
                    ->execute([$uid, $old['shop_name'], $thumbnailUrl, $documentUrl]);
            }
            $pdo->commit();
            $_SESSION['reg_success'] = 'Account created! Please sign in.';
            header('Location: login.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UIU Food Delivery — Register</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="register-page">
    <div class="register-card">
      <h1 class="register-title">Create Your Account</h1>
      <p class="register-subtitle">Join the UIU Food Delivery Portal</p>

      <?php if ($errors): ?>
        <div style="background:#fdecea;color:#c62828;padding:10px 14px;border-radius:8px;font-size:0.85rem;font-weight:600;margin-bottom:14px;">
          <?php foreach ($errors as $er): ?><div>• <?= e($er) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="register.php" enctype="multipart/form-data">
        <div class="form-group">
          <label class="form-label" for="reg-role">I am registering as</label>
          <select id="reg-role" name="role" class="form-input" onchange="toggleRoleFields()" required>
            <option value="student"    <?= $old['role']==='student'?'selected':'' ?>>Ordering Student</option>
            <option value="runner"     <?= $old['role']==='runner'?'selected':'' ?>>Delivery Runner</option>
            <option value="shop_owner" <?= $old['role']==='shop_owner'?'selected':'' ?>>Shop Owner</option>
          </select>
        </div>

        <!-- Profile picture (optional, all roles) -->
        <div class="form-group">
          <label class="form-label">Profile Picture <span style="color:#888;font-weight:400;">(optional)</span></label>
          <input type="file" name="profile_image" class="form-input" accept="image/*" />
          <p style="font-size:0.75rem;color:#888;margin-top:4px;">JPG / PNG / WEBP, max 5 MB. If skipped, a default avatar is used.</p>
        </div>

        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-input" placeholder="e.g. John Doe" value="<?= e($old['name']) ?>" required />
        </div>

        <div class="form-group" id="studentIdGroup">
          <label class="form-label">Student ID</label>
          <input type="text" name="student_id" class="form-input" placeholder="e.g. UIU-2023-094" value="<?= e($old['student_id']) ?>" />
        </div>

        <div class="form-group" id="vehicleGroup" style="display:none;">
          <label class="form-label">Vehicle Type</label>
          <select name="vehicle_type" class="form-input">
            <option value="bicycle">Bicycle</option>
            <option value="motorcycle">Motorcycle</option>
            <option value="walking">Walking</option>
          </select>
        </div>

        <div class="form-group" id="runnerDocGroup" style="display:none;">
          <label class="form-label">Verification Document <span style="color:#c62828;">*</span></label>
          <input type="file" name="runner_document" class="form-input" accept=".jpg,.jpeg,.png,.pdf" />
          <p style="font-size:0.75rem;color:#888;margin-top:4px;">Upload your Student ID card / NID for verification. JPG/PNG/PDF, max 8 MB.</p>
        </div>

        <div class="form-group" id="shopNameGroup" style="display:none;">
          <label class="form-label">Shop Name</label>
          <input type="text" name="shop_name" class="form-input" placeholder="e.g. Khan's Biryani Palace" value="<?= e($old['shop_name']) ?>" />
        </div>

        <div class="form-group" id="shopThumbGroup" style="display:none;">
          <label class="form-label">Shop Thumbnail Image <span style="color:#c62828;">*</span></label>
          <input type="file" name="shop_thumbnail" class="form-input" accept="image/*" />
          <p style="font-size:0.75rem;color:#888;margin-top:4px;">Students will see this when browsing shops. JPG/PNG/WEBP, max 5 MB.</p>
        </div>

        <div class="form-group" id="shopDocGroup" style="display:none;">
          <label class="form-label">Verification Document <span style="color:#c62828;">*</span></label>
          <input type="file" name="shop_document" class="form-input" accept=".jpg,.jpeg,.png,.pdf" />
          <p style="font-size:0.75rem;color:#888;margin-top:4px;">Upload trade license / NID / food permit. JPG/PNG/PDF, max 8 MB.</p>
        </div>

        <div class="form-group">
          <label class="form-label">University Email Address</label>
          <input type="email" name="email" class="form-input" placeholder="student@uiu.edu" value="<?= e($old['email']) ?>" required />
        </div>

        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="tel" name="phone" class="form-input" placeholder="+8801XXXXXXXXX" value="<?= e($old['phone']) ?>" />
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-input" placeholder="Minimum 8 characters" required />
        </div>

        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm" class="form-input" placeholder="Repeat password" required />
        </div>

        <div class="terms-row">
          <input type="checkbox" id="terms" class="terms-checkbox" checked />
          <label for="terms" class="terms-text">I agree to the <a href="#" class="terms-link">Terms &amp; Conditions</a></label>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">Register</button>
      </form>

      <a href="login.php" class="register-back-link">Back to Login</a>
    </div>
  </div>
  <script>
    function toggleRoleFields() {
      var r = document.getElementById('reg-role').value;
      document.getElementById('studentIdGroup').style.display  = (r==='student'||r==='runner') ? '' : 'none';
      document.getElementById('vehicleGroup').style.display    = (r==='runner') ? '' : 'none';
      document.getElementById('runnerDocGroup').style.display  = (r==='runner') ? '' : 'none';
      document.getElementById('shopNameGroup').style.display   = (r==='shop_owner') ? '' : 'none';
      document.getElementById('shopThumbGroup').style.display  = (r==='shop_owner') ? '' : 'none';
      document.getElementById('shopDocGroup').style.display    = (r==='shop_owner') ? '' : 'none';
    }
    toggleRoleFields();
  </script>
</body>
</html>