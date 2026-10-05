<?php
require 'config.php';
require 'auth.php';
require 'chat-helpers.php';
require_login();

$uid     = (int)$_SESSION['user_id'];
$orderId = (int)($_GET['order_id'] ?? 0);

if (!$orderId) { http_response_code(400); exit; }

/* Verify user is a participant of this order and fetch status + both avatars */
$stmt = $pdo->prepare("
    SELECT o.id, o.status,
           st.user_id AS student_user_id,
           su.profile_image AS student_avatar,
           r.user_id  AS runner_user_id,
           ru.profile_image AS runner_avatar
    FROM orders o
    LEFT JOIN students st ON st.id = o.student_id
    LEFT JOIN users    su ON su.id = st.user_id
    LEFT JOIN runners  r  ON r.id  = o.runner_id
    LEFT JOIN users    ru ON ru.id = r.user_id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$row = $stmt->fetch();

if (!$row) { http_response_code(404); exit; }

$isParticipant = ((int)$row['student_user_id'] === $uid || (int)$row['runner_user_id'] === $uid);
if (!$isParticipant) { http_response_code(403); exit; }

/* If the delivery is over, close the chat */
if (!in_array($row['status'], ['picked_up','on_the_way'], true)) {
    echo "<div class='chat-date-divider' style='color:#888;'>Delivery completed — this conversation is now closed.</div>";
    exit;
}

/* Load messages */
$stmt = $pdo->prepare("SELECT * FROM messages WHERE order_id = ? ORDER BY created_at ASC LIMIT 200");
$stmt->execute([$orderId]);
$messages = $stmt->fetchAll();

/* Mark messages received by me as read */
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE order_id = ? AND receiver_id = ?")
    ->execute([$orderId, $uid]);

/* Figure out who is who and build both avatar URLs */
$myAvatar    = avatar_url($_SESSION['role']);
$otherAvatar = '';

if ((int)$row['student_user_id'] === $uid) {
    /* I'm the student → other is the runner */
    $otherAvatar = !empty($row['runner_avatar'])
        ? $row['runner_avatar']
        : 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&q=80&fit=crop';
} else {
    /* I'm the runner → other is the student */
    $otherAvatar = !empty($row['student_avatar'])
        ? $row['student_avatar']
        : 'https://images.unsplash.com/photo-1607746882042-944635dfe10e?w=80&q=80&auto=format&fit=crop';
}

render_chat_messages($messages, $uid, $myAvatar, $otherAvatar);