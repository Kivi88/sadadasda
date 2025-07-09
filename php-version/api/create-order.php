<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Geçersiz istek metodu'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

// Gerekli alanları kontrol et
$required = ['keyValue', 'serviceId', 'link', 'quantity'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        jsonResponse(['success' => false, 'message' => ucfirst($field) . ' alanı gerekli']);
    }
}

$keyValue = sanitize($input['keyValue']);
$serviceId = validateInput($input['serviceId'], 'int');
$link = validateInput($input['link'], 'url');
$quantity = validateInput($input['quantity'], 'int');

if (!$serviceId || !$link || !$quantity) {
    jsonResponse(['success' => false, 'message' => 'Geçersiz veri formatı']);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Anahtarı doğrula
    $stmt = $db->prepare("SELECT * FROM `keys` WHERE key_value = ? AND is_active = 1");
    $stmt->bind_param("s", $keyValue);
    $stmt->execute();
    $keyResult = $stmt->get_result();
    
    if ($keyResult->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Geçersiz anahtar']);
    }
    
    $key = $keyResult->fetch_assoc();
    
    // Servisi doğrula
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $serviceId);
    $stmt->execute();
    $serviceResult = $stmt->get_result();
    
    if ($serviceResult->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Geçersiz servis']);
    }
    
    $service = $serviceResult->fetch_assoc();
    
    // Miktar kontrolü
    if ($quantity < $service['min_quantity'] || $quantity > $service['max_quantity']) {
        jsonResponse(['success' => false, 'message' => 'Geçersiz miktar']);
    }
    
    // Anahtar limitini kontrol et
    if (($key['used_amount'] + $quantity) > $key['max_amount']) {
        jsonResponse(['success' => false, 'message' => 'Anahtar kullanım limiti aşılacak']);
    }
    
    // Sipariş ID oluştur
    $orderId = 'KW' . date('YmdHis') . rand(1000, 9999);
    
    // Siparişi oluştur
    $stmt = $db->prepare("
        INSERT INTO orders (order_id, key_id, service_id, link, quantity, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->bind_param("siisi", $orderId, $key['id'], $serviceId, $link, $quantity);
    
    if (!$stmt->execute()) {
        jsonResponse(['success' => false, 'message' => 'Sipariş oluşturulamadı']);
    }
    
    // Anahtar kullanım miktarını güncelle
    $newUsedAmount = $key['used_amount'] + $quantity;
    $stmt = $db->prepare("UPDATE `keys` SET used_amount = ? WHERE id = ?");
    $stmt->bind_param("ii", $newUsedAmount, $key['id']);
    $stmt->execute();
    
    // Siparişi al
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $orderResult = $stmt->get_result();
    $order = $orderResult->fetch_assoc();
    
    // Dış API'ye sipariş gönder (isteğe bağlı)
    // Bu kısım gerçek API entegrasyonu için kullanılabilir
    
    jsonResponse([
        'success' => true,
        'order' => $order,
        'message' => 'Sipariş başarıyla oluşturuldu'
    ]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()], 500);
}
?>