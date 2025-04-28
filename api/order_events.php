<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('X-Accel-Buffering: no');

if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}

if (ob_get_level()) ob_end_clean();

echo str_repeat(' ', 2048);
echo PHP_EOL;

flush();

if (connection_aborted()) {
    exit();
}

$lastEventId = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? 
    intval($_SERVER['HTTP_LAST_EVENT_ID']) : 
    (isset($_GET['last_event_id']) ? intval($_GET['last_event_id']) : 0);

$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

$isStaff = isset($_GET['client']) && $_GET['client'] === 'staff';

$eventsFile = __DIR__ . '/../temp/events/order_events.json';

$orderDetailsDir = __DIR__ . '/../temp/events/orders/';
if (!is_dir($orderDetailsDir)) {
    mkdir($orderDetailsDir, 0755, true);
}

function sendSSE($id, $event, $data) {
    echo "id: $id" . PHP_EOL;
    echo "event: $event" . PHP_EOL;
    echo "data: " . json_encode($data) . PHP_EOL;
    echo PHP_EOL;
    ob_flush();
    flush();
}

sendSSE(time(), 'connection', ['status' => 'connected']);

$lastCheck = time();
$reconnectTime = 30;
$keepAliveInterval = 15;
$lastEventCheckTime = 0;
$processedEventIds = [];

$lastStatusCheck = [];

while (true) {
    if (connection_aborted()) {
        break;
    }

    $currentTime = time();
    
    if ($currentTime - $lastCheck >= $keepAliveInterval) {
        sendSSE($currentTime, 'ping', ['time' => $currentTime]);
        $lastCheck = $currentTime;
    }
    
    if ($currentTime - $lastEventCheckTime >= 1) {
        $lastEventCheckTime = $currentTime;
        
        if (file_exists($eventsFile)) {
            $events = [];
            $fileContent = file_get_contents($eventsFile);
            if (!empty($fileContent)) {
                $events = json_decode($fileContent, true) ?: [];
            }
            
            if (!empty($events)) {
                $newEvents = array_filter($events, function($event) use ($lastEventId, $processedEventIds) {
                    return isset($event['event_id']) && $event['event_id'] > $lastEventId && !in_array($event['event_id'], $processedEventIds);
                });
                
                if (!empty($newEvents)) {
                    $maxId = $lastEventId;
                    
                    foreach ($newEvents as $event) {
                        if (isset($event['event_id'])) {
                            $processedEventIds[] = $event['event_id'];
                            
                            if (count($processedEventIds) > 100) {
                                $processedEventIds = array_slice($processedEventIds, -100);
                            }
                            
                            if ($event['event_id'] > $maxId) {
                                $maxId = $event['event_id'];
                            }
                        }
                        
                        if (isset($event['event_type']) && $event['event_type'] === 'status_change' && isset($event['order_id'])) {
                            $updatedOrderId = $event['order_id'];
                            
                            if ($isStaff || $orderId === 0 || $orderId === $updatedOrderId) {
                                $orderDetailFile = $orderDetailsDir . 'order_' . $updatedOrderId . '.json';
                                
                                if (file_exists($orderDetailFile)) {
                                    $orderContent = file_get_contents($orderDetailFile);
                                    if (!empty($orderContent)) {
                                        $order = json_decode($orderContent, true);
                                        
                                        sendSSE($updatedOrderId . '_' . $currentTime, 'order_update', ['order' => $order]);
                                    }
                                } else {
                                    sendSSE($updatedOrderId . '_' . $currentTime, 'order_update', [
                                        'order' => [
                                            'order_id' => $updatedOrderId,
                                            'status' => $event['status'] ?? 'unknown',
                                            'timestamp' => $event['timestamp'] ?? $currentTime
                                        ]
                                    ]);
                                }
                            }
                        }
                    }
                    
                    if ($maxId > $lastEventId) {
                        $lastEventId = $maxId;
                    }
                }
            }
        }
        
        if (!$isStaff && $orderId > 0) {
            $orderDetailFile = $orderDetailsDir . 'order_' . $orderId . '.json';
            
            if (file_exists($orderDetailFile)) {
                $orderContent = file_get_contents($orderDetailFile);
                if (!empty($orderContent)) {
                    $order = json_decode($orderContent, true);
                    
                    $shouldUpdate = false;
                    
                    if (!isset($lastStatusCheck[$orderId])) {
                        $shouldUpdate = true;
                        $lastStatusCheck[$orderId] = [
                            'status' => $order['status'] ?? 'unknown',
                            'time' => $currentTime
                        ];
                    } 
                    else if (
                        (!isset($lastStatusCheck[$orderId]['status']) || 
                        $lastStatusCheck[$orderId]['status'] !== ($order['status'] ?? 'unknown')) || 
                        ($currentTime - $lastStatusCheck[$orderId]['time']) > 5
                    ) {
                        $shouldUpdate = true;
                        $lastStatusCheck[$orderId] = [
                            'status' => $order['status'] ?? 'unknown',
                            'time' => $currentTime
                        ];
                    }
                    
                    if ($shouldUpdate) {
                        sendSSE($orderId . '_' . $currentTime, 'order_update', ['order' => $order]);
                    }
                }
            }
        }
    }
    
    usleep(200000);
}