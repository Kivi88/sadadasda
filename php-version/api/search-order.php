<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Geçersiz istek metodu'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['orderId']) || empty($input['orderId'])) {
    jsonResponse(['success' => false, 'message' => 'Sipariş ID gerekli']);
}

$orderId = sanitize($input['orderId']);

// # işaretini kaldır
$orderId = str_replace('#', '', $orderId);

try {
    $db = Database::getInstance()->getConnection();
    
    // Siparişi ve servis bilgilerini al
    $stmt = $db->prepare("
        SELECT o.*, s.name as service_name, s.platform, s.category
        FROM orders o
        LEFT JOIN services s ON o.service_id = s.id
        WHERE o.order_id = ?
    ");
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Sipariş bulunamadı']);
    }
    
    $orderData = $result->fetch_assoc();
    
    // Sipariş bilgilerini ayrı olarak döndür
    $order = [
        'id' => $orderData['id'],
        'order_id' => $orderData['order_id'],
        'link' => $orderData['link'],
        'quantity' => $orderData['quantity'],
        'status' => $orderData['status'],
        'created_at' => $orderData['created_at'],
        'updated_at' => $orderData['updated_at']
    ];
    
    $service = [
        'id' => $orderData['service_id'],
        'name' => $orderData['service_name'],
        'platform' => $orderData['platform'],
        'category' => $orderData['category']
    ];
    
    jsonResponse([
        'success' => true,
        'order' => $order,
        'service' => $service
    ]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()], 500);
}
?>