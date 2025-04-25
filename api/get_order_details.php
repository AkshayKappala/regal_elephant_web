<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
$mysqli = Database::getConnection();

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Missing order_id']);
    exit;
}

$stmt = $mysqli->prepare('SELECT * FROM orders WHERE order_id = ?');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

$stmt = $mysqli->prepare('SELECT oi.*, fi.name FROM order_items oi JOIN food_items fi ON oi.item_id = fi.item_id WHERE oi.order_id = ?');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

$order['items'] = $items;
echo json_encode(['success' => true, 'order' => $order]);
