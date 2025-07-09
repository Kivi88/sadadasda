<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Geçersiz istek metodu'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['keyValue']) || empty($input['keyValue'])) {
    jsonResponse(['success' => false, 'message' => 'Anahtar değeri gerekli']);
}

$keyValue = sanitize($input['keyValue']);

try {
    $db = Database::getInstance()->getConnection();
    
    // Anahtarı ve ilgili servisi bul
    $stmt = $db->prepare("
        SELECT k.*, s.name as service_name, s.platform, s.category, s.min_quantity, s.max_quantity
        FROM `keys` k
        LEFT JOIN services s ON k.service_id = s.id
        WHERE k.key_value = ? AND k.is_active = 1
    ");
    $stmt->bind_param("s", $keyValue);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Geçersiz anahtar']);
    }
    
    $keyData = $result->fetch_assoc();
    
    // Anahtarın kullanım limitini kontrol et
    if ($keyData['used_amount'] >= $keyData['max_amount']) {
        jsonResponse(['success' => false, 'message' => 'Anahtar kullanım limiti aşıldı']);
    }
    
    // Servis bilgilerini ayrı olarak döndür
    $service = [
        'id' => $keyData['service_id'],
        'name' => $keyData['service_name'],
        'platform' => $keyData['platform'],
        'category' => $keyData['category'],
        'min_quantity' => $keyData['min_quantity'],
        'max_quantity' => $keyData['max_quantity']
    ];
    
    $key = [
        'id' => $keyData['id'],
        'key_value' => $keyData['key_value'],
        'name' => $keyData['name'],
        'max_amount' => $keyData['max_amount'],
        'used_amount' => $keyData['used_amount'],
        'service_id' => $keyData['service_id']
    ];
    
    jsonResponse([
        'success' => true,
        'key' => $key,
        'service' => $service
    ]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()], 500);
}
?>