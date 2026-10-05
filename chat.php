<?php
require 'config.php';
require 'auth.php';
require 'chat-helpers.php';
require_login();

$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

/*  Build contact list */
$contacts = [];

if ($role === 'student') {
    $me = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $me->execute([$uid]);
    $meRow = $me->fetch();
    if ($meRow) {
        $stmt = $pdo->prepare("
            SELECT o.id AS order_id, o.order_code, o.status, o.picked_up_at,
                   u.id AS other_user_id, u.full_name AS other_name,
                   u.profile_image AS other_avatar
            FROM orders o
            JOIN runners r ON r.id = o.runner_id
            JOIN users   u ON u.id = r.user_id
            WHERE o.student_id = ?
              AND o.runner_id IS NOT NULL
              AND o.status IN ('picked_up','on_the_way')
            ORDER BY o.picked_up_at DESC
        ");
        $stmt->execute([$meRow['id']]);
        $contacts = $stmt->fetchAll();
    }
} elseif ($role === 'runner') {
    $me = $pdo->prepare("SELECT id FROM runners WHERE user_id = ?");
    $me->execute([$uid]);
    $meRow = $me->fetch();
    if ($meRow) {
        $stmt = $pdo->prepare("
            SELECT o.id AS order_id, o.order_code, o.status, o.picked_up_at,
                   u.id AS other_user_id, u.full_name AS other_name,
                   u.profile_image AS other_avatar
            FROM orders o
            JOIN students s ON s.id = o.student_id
            JOIN users    u ON u.id = s.user_id
            WHERE o.runner_id = ?
              AND o.status IN ('picked_up','on_the_way')
            ORDER BY o.picked_up_at DESC
        ");
        $stmt->execute([$meRow['id']]);
        $contacts = $stmt->fetchAll();
    }
}

/*  Pick selected conversation  */
$selectedOrderId = (int)($_GET['order_id'] ?? 0);
$selected = null;
foreach ($contacts as $c) {
    if ((int)$c['order_id'] === $selectedOrderId) { $selected = $c; break; }
}
if (!$selected && count($contacts) > 0) {
    $selected = $contacts[0];
    $selectedOrderId = (int)$selected['order_id'];
}

/* Handle send */
$sendError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selected) {
    $content = trim($_POST['msg'] ?? '');
    if ($content === '') {
        $sendError = 'Message cannot be empty.';
    } elseif (mb_strlen($content) > 500) {
        $sendError = 'Message is too long (max 500 chars).';
    } else {
        $pdo->prepare("INSERT INTO messages (order_id, sender_id, receiver_id, content) VALUES (?,?,?,?)")
            ->execute([$selected['order_id'], $uid, $selected['other_user_id'], $content]);
        header('Location: chat.php?order_id=' . (int)$selected['order_id']);
        exit;
    }
}

/* Load messages for selected */
$messages = [];
if ($selected) {
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE order_id = ? ORDER BY created_at ASC LIMIT 200");
    $stmt->execute([$selected['order_id']]);
    $messages = $stmt->fetchAll();

    /* Mark incoming as read */
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE order_id = ? AND receiver_id = ?")
        ->execute([$selected['order_id'], $uid]);
}

/* Small helper to preview the last message */
function last_message_preview($pdo, $orderId, $fallback) {
    $s = $pdo->prepare("SELECT content FROM messages WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
    $s->execute([$orderId]);
    $last = $s->fetchColumn();
    if ($last === false || $last === null) return $fallback;
    return (mb_strlen($last) > 34) ? mb_substr($last, 0, 34) . '…' : $last;
}

/* Helper: fallback avatar if a user has no uploaded picture */
function other_avatar_or_default($path, $role) {
    if (!empty($path)) return $path;
    return $role === 'student'
        ? 'https://images.unsplash.com/photo-1607746882042-944635dfe10e?w=80&q=80&auto=format&fit=crop'
        : 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&q=80&fit=crop';
}

/* My avatar (used for sent bubbles) */
$myAvatar = avatar_url($role);

/* Selected contact's avatar (used for received bubbles + header) */
$selectedAvatar = '';
if ($selected) {
    $contactRoleForFallback = ($role === 'student') ? 'runner' : 'student';
    $selectedAvatar = other_avatar_or_default($selected['other_avatar'] ?? '', $contactRoleForFallback);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Messages</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body style="margin:0;padding:0;overflow:hidden;">
  <div class="chat-layout">
    
    <div class="chat-sidebar">
      <div class="chat-sidebar-header">
        <h2 class="chat-sidebar-title">Messages</h2>
        <div class="chat-search-wrap">
          <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
          <input type="text" class="chat-search-input" placeholder="Search conversations..." />
        </div>
      </div>

      <div class="chat-contacts">
        <?php if (count($contacts) === 0): ?>
          <div style="padding:30px 20px; text-align:center; color:#888; font-size:0.85rem;">
            <i class="fa-solid fa-comments" style="font-size:1.8rem; opacity:0.3; display:block; margin-bottom:10px;"></i>
            <?php if ($role === 'student'): ?>
              No active deliveries to chat about yet.<br>
              <span style="font-size:0.75rem;">You can chat with a runner once they pick up your order.</span>
            <?php elseif ($role === 'runner'): ?>
              No active deliveries right now.
            <?php else: ?>
              No conversations available.
            <?php endif; ?>
          </div>
        <?php else: foreach ($contacts as $c):
          $isActive = ((int)$c['order_id'] === $selectedOrderId);
          $preview  = last_message_preview($pdo, $c['order_id'],
                        'Order ' . $c['order_code'] . ' — ' . ucfirst(str_replace('_',' ', $c['status'])));

          $contactRoleForFallback = ($role === 'student') ? 'runner' : 'student';
          $contactAvatar = other_avatar_or_default($c['other_avatar'] ?? '', $contactRoleForFallback);
        ?>
          <a href="chat.php?order_id=<?= (int)$c['order_id'] ?>"
             class="chat-contact-item <?= $isActive ? 'active' : '' ?>">
            <div class="chat-contact-avatar-wrap">
              <img src="<?= e($contactAvatar) ?>"
                   alt="<?= e($c['other_name']) ?>" class="chat-contact-avatar" />
              <span class="chat-online-dot"></span>
            </div>
            <div class="chat-contact-info">
              <p class="chat-contact-name"><?= e($c['other_name']) ?></p>
              <p class="chat-contact-preview"><?= e($preview) ?></p>
            </div>
            <span class="chat-contact-time"><?= e($c['order_code']) ?></span>
          </a>
        <?php endforeach; endif; ?>
      </div>
    </div>

    
    <div class="chat-main">
      <?php if ($selected): ?>
        <div class="chat-main-header">
          <a href="<?= redirect_by_role($role) ?>" class="chat-header-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12" /><polyline points="12 19 5 12 12 5" /></svg>
          </a>
          <img src="<?= e($selectedAvatar) ?>"
               alt="<?= e($selected['other_name']) ?>" class="chat-header-avatar" />
          <div style="flex:1;">
            <p class="chat-header-name"><?= e($selected['other_name']) ?></p>
            <p class="chat-header-status">Online — Order <?= e($selected['order_code']) ?></p>
          </div>
          <span style="font-size:var(--font-size-xs); color:var(--color-text-muted); background:var(--color-bg); padding:4px 10px; border-radius:99px;">
            <?= $role === 'student' ? 'Delivery Runner' : 'Customer' ?>
          </span>
        </div>

        <div id="chatMessages" class="chat-messages">
          <?php render_chat_messages($messages, $uid, $myAvatar, $selectedAvatar); ?>
        </div>

        <form method="post" class="chat-input-area">
          <input type="text" name="msg" class="chat-input" placeholder="Type a message..."
                 autocomplete="off" maxlength="500" required autofocus />
          <button type="submit" class="chat-send-btn" title="Send">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <line x1="22" y1="2" x2="11" y2="13" /><polygon points="22 2 15 22 11 13 2 9 22 2" /></svg>
          </button>
        </form>

        <script>
          (function () {
            var orderId = <?= (int)$selected['order_id'] ?>;
            var box     = document.getElementById('chatMessages');

            function scrollBottom() { box.scrollTop = box.scrollHeight; }
            scrollBottom();

            setInterval(function () {
              fetch('chat-messages.php?order_id=' + orderId, { cache: 'no-store' })
                .then(function (r) { return r.text(); })
                .then(function (html) {
                  if (html !== box.innerHTML) {
                    box.innerHTML = html;
                    scrollBottom();
                  }
                })
                .catch(function () { /* silent */ });
            }, 4000);
          })();
        </script>
      <?php else: ?>
        <div class="chat-main-header">
          <a href="<?= redirect_by_role($role) ?>" class="chat-header-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12" /><polyline points="12 19 5 12 12 5" /></svg>
          </a>
          <div style="flex:1;">
            <p class="chat-header-name">Messages</p>
            <p class="chat-header-status">No conversation selected</p>
          </div>
        </div>

        <div class="chat-messages" style="display:flex; align-items:center; justify-content:center; text-align:center;">
          <div style="max-width:320px; color:#888;">
            <i class="fa-solid fa-comments" style="font-size:3rem; opacity:0.25; display:block; margin-bottom:16px;"></i>
            <?php if ($role === 'student'): ?>
              <p style="font-size:0.95rem; font-weight:600; color:#666; margin-bottom:6px;">No active chats</p>
              <p style="font-size:0.82rem; line-height:1.5;">Once a runner picks up one of your orders, you'll be able to chat with them here.</p>
            <?php elseif ($role === 'runner'): ?>
              <p style="font-size:0.95rem; font-weight:600; color:#666; margin-bottom:6px;">No active chats</p>
              <p style="font-size:0.82rem; line-height:1.5;">Once you accept a delivery job, your customer will appear here.</p>
            <?php else: ?>
              <p style="font-size:0.95rem; font-weight:600; color:#666;">No conversations available.</p>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>