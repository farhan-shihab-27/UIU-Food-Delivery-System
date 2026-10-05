<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function is_logged_in() { return isset($_SESSION['user_id']); }

function require_login() {
    if (!is_logged_in()) { header('Location: login.php'); exit; }
}

function require_role($roles) {
    require_login();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['role'], $roles)) { header('Location: login.php'); exit; }
}

function redirect_by_role($role) {
    switch ($role) {
        case 'admin':      return 'admin-dashboard.php';
        case 'shop_owner': return 'shop-dashboard.php';
        case 'runner':     return 'runner-dashboard.php';
        default:           return 'student-dashboard.php';
    }
}

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }



function notify($pdo, $userId, $title, $message = null, $type = 'system', $link = null) {
    if (!$userId) return;
    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link_url)
                   VALUES (?, ?, ?, ?, ?)")
        ->execute([$userId, $title, $message, $type, $link]);
}

function unread_notification_count($pdo, $userId) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $s->execute([$userId]);
    return (int)$s->fetchColumn();
}




function avatar_url($role = null) {
    if (!empty($_SESSION['profile_image'])) {
        return $_SESSION['profile_image'];
    }
    if ($role === null) $role = $_SESSION['role'] ?? 'student';
    switch ($role) {
        case 'admin':
            return 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&q=80&fit=crop';
        case 'runner':
            return 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&q=80&fit=crop';
        default:
            return 'https://images.unsplash.com/photo-1607746882042-944635dfe10e?w=80&q=80&auto=format&fit=crop';
    }
}