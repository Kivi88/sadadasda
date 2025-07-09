<?php
require_once '../../config.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Siparişleri listele
            $result = $db->query("
                SELECT o.*, s.name as service_name, s.platform, s.category, k.name as key_name
                FROM orders o
                LEFT JOIN services s ON o.service_id = s.id
                LEFT JOIN `keys` k ON o.key_id = k.id
                ORDER BY o.created_at DESC
                LIMIT 100
            ");
            $orders = $result->fetch_all(MYSQLI_ASSOC);
            
            jsonResponse(['success' => true, 'orders' => $orders]);
            break;
            
        case 'PUT':
            // Sipariş durumunu güncelle
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id']) || !isset($input['status'])) {
                jsonResponse(['success' => false, 'message' => 'ID ve durum gerekli']);
            }
            
            $orderId = validateInput($input['id'], 'int');
            $status = sanitize($input['status']);
            
            if (!$orderId) {
                jsonResponse(['success' => false, 'message' => 'Geçersiz sipariş ID']);
            }
            
            $allowedStatuses = ['pending', 'processing', 'completed', 'cancelled'];
            if (!in_array($status, $allowedStatuses)) {
                jsonResponse(['success' => false, 'message' => 'Geçersiz durum']);
            }
            
            $stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $status, $orderId);
            
            if ($stmt->execute()) {
                jsonResponse(['success' => true, 'message' => 'Sipariş durumu güncellendi']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Sipariş güncellenemedi']);
            }
            break;
            
        case 'DELETE':
            // Sipariş sil
            $orderId = validateInput($_GET['id'], 'int');
            
            if (!$orderId) {
                jsonResponse(['success' => false, 'message' => 'Geçersiz sipariş ID']);
            }
            
            $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->bind_param("i", $orderId);
            
            if ($stmt->execute()) {
                jsonResponse(['success' => true, 'message' => 'Sipariş başarıyla silindi']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Sipariş silinemedi']);
            }
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Geçersiz istek metodu'], 405);
    }
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()], 500);
}
?>