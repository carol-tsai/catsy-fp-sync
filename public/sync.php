<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/api_client.php';
require_once __DIR__ . '/../includes/logger.php';

header('Content-Type: application/json');

$db = new Database();
$logger = new Logger();
$apiClient = new ApiClient();

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'sync_single':
            $sku = $_POST['sku'] ?? '';
            if (empty($sku)) {
                throw new Exception('SKU is required');
            }
            
            // Get product from PIM
            $product = $apiClient->getFromPIM('/products/' . urlencode($sku));
            
            if (empty($product)) {
                throw new Exception('Product not found in PIM');
            }
            
            // Update website
            $response = $apiClient->postToWebsite('/products/update', $product);
            
            if ($response['status'] !== 'success') {
                throw new Exception($response['message'] ?? 'Update failed');
            }
            
            // Update database
            $conn = $db->getConnection();
            $stmt = $conn->prepare("UPDATE products SET last_updated = NOW(), sync_status = 'success' WHERE sku = ?");
            $stmt->bind_param("s", $sku);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'last_updated' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'sync_all':
            // Trigger full sync (could run as background process)
            $output = shell_exec('php ' . __DIR__ . '/../scripts/sync_products.php');
            
            echo json_encode([
                'success' => true,
                'output' => $output
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $logger->log("Sync error: " . $e->getMessage(), 'ERROR');
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}