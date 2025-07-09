<?php
require_once '../../config.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Anahtarları listele
            $result = $db->query("
                SELECT k.*, s.name as service_name, s.platform, s.category
                FROM `keys` k
                LEFT JOIN services s ON k.service_id = s.id
                ORDER BY k.created_at DESC
                LIMIT 100
            ");
            $keys = $result->fetch_all(MYSQLI_ASSOC);
            
            jsonResponse(['success' => true, 'keys' => $keys]);
            break;
            
        case 'POST':
            // Yeni anahtar ekle
            $input = json_decode(file_get_contents('php://input'), true);
            
            $required = ['serviceId', 'name'];
            foreach ($required as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    jsonResponse(['success' => false, 'message' => ucfirst($field) . ' alanı gerekli']);
                }
            }
            
            $serviceId = validateInput($input['serviceId'], 'int');
            $name = sanitize($input['name']);
            $prefix = sanitize($input['prefix'] ?? DEFAULT_KEY_PREFIX);
            $maxAmount = validateInput($input['maxAmount'] ?? 1000, 'int');
            
            if (!$serviceId) {
                jsonResponse(['success' => false, 'message' => 'Geçersiz servis ID']);
            }
            
            // Anahtar değeri oluştur
            $keyValue = $prefix . '_' . strtoupper(bin2hex(random_bytes(8)));
            
            $stmt = $db->prepare("
                INSERT INTO `keys` (key_value, service_id, name, prefix, max_amount)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sissi", $keyValue, $serviceId, $name, $prefix, $maxAmount);
            
            if ($stmt->execute()) {
                $keyId = $db->insert_id;
                jsonResponse(['success' => true, 'id' => $keyId, 'keyValue' => $keyValue, 'message' => 'Anahtar başarıyla oluşturuldu']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Anahtar oluşturulamadı']);
            }
            break;
            
        case 'DELETE':
            // Anahtar sil
            $keyId = validateInput($_GET['id'], 'int');
            
            if (!$keyId) {
                jsonResponse(['success' => false, 'message' => 'Geçersiz anahtar ID']);
            }
            
            $stmt = $db->prepare("DELETE FROM `keys` WHERE id = ?");
            $stmt->bind_param("i", $keyId);
            
            if ($stmt->execute()) {
                jsonResponse(['success' => true, 'message' => 'Anahtar başarıyla silindi']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Anahtar silinemedi']);
            }
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Geçersiz istek metodu'], 405);
    }
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()], 500);
}
?>