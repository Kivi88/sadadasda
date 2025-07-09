<?php
require_once '../../config.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Servisleri listele
            $result = $db->query("
                SELECT s.*, a.name as api_name 
                FROM services s
                LEFT JOIN apis a ON s.api_id = a.id
                ORDER BY s.created_at DESC
                LIMIT 100
            ");
            $services = $result->fetch_all(MYSQLI_ASSOC);
            
            jsonResponse(['success' => true, 'services' => $services]);
            break;
            
        case 'POST':
            // Yeni servis ekle
            $input = json_decode(file_get_contents('php://input'), true);
            
            $required = ['apiId', 'externalId', 'name', 'platform', 'category'];
            foreach ($required as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    jsonResponse(['success' => false, 'message' => ucfirst($field) . ' alanı gerekli']);
                }
            }
            
            $apiId = validateInput($input['apiId'], 'int');
            $externalId = sanitize($input['externalId']);
            $name = sanitize($input['name']);
            $platform = sanitize($input['platform']);
            $category = sanitize($input['category']);
            $minQuantity = validateInput($input['minQuantity'] ?? 1, 'int');
            $maxQuantity = validateInput($input['maxQuantity'] ?? 10000, 'int');
            
            if (!$apiId) {
                jsonResponse(['success' => false, 'message' => 'Geçersiz API ID']);
            }
            
            $stmt = $db->prepare("
                INSERT INTO services (api_id, external_id, name, platform, category, min_quantity, max_quantity)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issssii", $apiId, $externalId, $name, $platform, $category, $minQuantity, $maxQuantity);
            
            if ($stmt->execute()) {
                $serviceId = $db->insert_id;
                jsonResponse(['success' => true, 'id' => $serviceId, 'message' => 'Servis başarıyla eklendi']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Servis eklenemedi']);
            }
            break;
            
        case 'DELETE':
            // Servis sil
            $serviceId = validateInput($_GET['id'], 'int');
            
            if (!$serviceId) {
                jsonResponse(['success' => false, 'message' => 'Geçersiz servis ID']);
            }
            
            $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
            $stmt->bind_param("i", $serviceId);
            
            if ($stmt->execute()) {
                jsonResponse(['success' => true, 'message' => 'Servis başarıyla silindi']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Servis silinemedi']);
            }
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Geçersiz istek metodu'], 405);
    }
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()], 500);
}
?>