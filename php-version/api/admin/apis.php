<?php
require_once '../../config.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // API'leri listele
            $result = $db->query("SELECT * FROM apis ORDER BY created_at DESC");
            $apis = $result->fetch_all(MYSQLI_ASSOC);
            
            jsonResponse(['success' => true, 'apis' => $apis]);
            break;
            
        case 'POST':
            // Yeni API ekle
            $input = json_decode(file_get_contents('php://input'), true);
            
            $required = ['name', 'url', 'apiKey'];
            foreach ($required as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    jsonResponse(['success' => false, 'message' => ucfirst($field) . ' alanı gerekli']);
                }
            }
            
            $name = sanitize($input['name']);
            $url = validateInput($input['url'], 'url');
            $apiKey = sanitize($input['apiKey']);
            
            if (!$url) {
                jsonResponse(['success' => false, 'message' => 'Geçersiz URL']);
            }
            
            $stmt = $db->prepare("INSERT INTO apis (name, url, api_key) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $url, $apiKey);
            
            if ($stmt->execute()) {
                $apiId = $db->insert_id;
                jsonResponse(['success' => true, 'id' => $apiId, 'message' => 'API başarıyla eklendi']);
            } else {
                jsonResponse(['success' => false, 'message' => 'API eklenemedi']);
            }
            break;
            
        case 'DELETE':
            // API sil
            $apiId = validateInput($_GET['id'], 'int');
            
            if (!$apiId) {
                jsonResponse(['success' => false, 'message' => 'Geçersiz API ID']);
            }
            
            $stmt = $db->prepare("DELETE FROM apis WHERE id = ?");
            $stmt->bind_param("i", $apiId);
            
            if ($stmt->execute()) {
                jsonResponse(['success' => true, 'message' => 'API başarıyla silindi']);
            } else {
                jsonResponse(['success' => false, 'message' => 'API silinemedi']);
            }
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Geçersiz istek metodu'], 405);
    }
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()], 500);
}
?>