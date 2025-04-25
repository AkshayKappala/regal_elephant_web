<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
$mysqli = Database::getConnection();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['items']) || !is_array($data['items'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$items = $data['items'];
$resultItems = [];

foreach ($items as $item) {
    $name = $item['name'] ?? '';
    $price = $item['price'] ?? 0;
    $quantity = $item['quantity'] ?? 1;
    $stmt = $mysqli->prepare('SELECT item_id FROM food_items WHERE name = ? AND price = ? LIMIT 1');
    $stmt->bind_param('sd', $name, $price);
    $stmt->execute();
    $stmt->bind_result($item_id);
    if ($stmt->fetch()) {
        $resultItems[] = [
            'item_id' => $item_id,
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity
        ];
    } else {
        echo json_encode(['success' => false, 'error' => "Item not found: $name"]);
        exit;
    }
    $stmt->close();
}

echo json_encode(['success' => true, 'items' => $resultItems]);
