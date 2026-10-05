<?php
require 'config.php';
require 'auth.php';
require_role('student');

header('Content-Type: application/json');

$uid = $_SESSION['user_id'];
$me  = $pdo->query("SELECT id FROM students WHERE user_id=$uid")->fetch();

$orderId = (int)($_GET['order_id'] ?? 0);

/* Student can only fetch locations for their own orders */
$stmt = $pdo->prepare("
    SELECT rl.latitude, rl.longitude, rl.updated_at,
           u.full_name AS runner_name,
           o.status    AS order_status
    FROM orders o
    LEFT JOIN runner_locations rl ON rl.order_id = o.id
    LEFT JOIN runners r ON r.id = o.runner_id
    LEFT JOIN users u ON u.id = r.user_id
    WHERE o.id = ? AND o.student_id = ?
");
$stmt->execute([$orderId, $me['id']]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['ok'=>false, 'err'=>'order not found']); exit;
}

$hasLoc = ($row['latitude'] !== null && $row['longitude'] !== null);

echo json_encode([
    'ok'            => true,
    'has_location'  => $hasLoc,
    'latitude'      => $hasLoc ? (float)$row['latitude']  : null,
    'longitude'     => $hasLoc ? (float)$row['longitude'] : null,
    'seconds_ago'   => $hasLoc ? (time() - strtotime($row['updated_at'])) : null,
    'runner_name'   => $row['runner_name'],
    'order_status'  => $row['order_status']
]);