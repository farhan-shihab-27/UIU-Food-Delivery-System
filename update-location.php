<?php
require 'config.php';
require 'auth.php';
require_role('runner');

header('Content-Type: application/json');

$uid = $_SESSION['user_id'];
$runner = $pdo->query("SELECT id FROM runners WHERE user_id=$uid")->fetch();
if (!$runner) { echo json_encode(['ok'=>false,'err'=>'no runner']); exit; }

$orderId = (int)($_POST['order_id'] ?? 0);
$lat = (float)($_POST['lat'] ?? 0);
$lng = (float)($_POST['lng'] ?? 0);

if (!$orderId || !$lat || !$lng) {
    echo json_encode(['ok'=>false,'err'=>'missing fields']); exit;
}
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['ok'=>false,'err'=>'invalid coords']); exit;
}

/* Order must belong to this runner and be in-transit */
$check = $pdo->prepare("SELECT id FROM orders WHERE id=? AND runner_id=? AND status IN ('picked_up','on_the_way')");
$check->execute([$orderId, $runner['id']]);
if (!$check->fetch()) {
    echo json_encode(['ok'=>false,'err'=>'not your active order']); exit;
}

/* Upsert single row per order */
$pdo->prepare("
    INSERT INTO runner_locations (runner_id, order_id, latitude, longitude, updated_at)
    VALUES (?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        latitude   = VALUES(latitude),
        longitude  = VALUES(longitude),
        updated_at = NOW()
")->execute([$runner['id'], $orderId, $lat, $lng]);

echo json_encode(['ok'=>true]);