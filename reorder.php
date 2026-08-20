<?php
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
$orderedIds = $payload['order'] ?? null;

if (!is_array($orderedIds) || empty($orderedIds)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid order payload']);
    exit;
}

$orderedIds = array_map('intval', $orderedIds);
save_new_order($orderedIds);

echo json_encode(['ok' => true]);
