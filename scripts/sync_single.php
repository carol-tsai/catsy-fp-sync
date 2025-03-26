<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/api_client.php';
require_once __DIR__ . '/../includes/logger.php';

$logger = new Logger();
$db = new Database();
$apiClient = new ApiClient();

// Get SKU from command line argument
$sku = $argv[1] ?? null;

if (empty($sku)) {
    $logger->log("No SKU provided for single product sync", 'ERROR');
    exit(1);
}

try {
    $logger->log("Starting sync for product: $sku");
    
    // 1. Fetch product from PIM
    $product = $apiClient->getFromPIM('/products/' . urlencode($sku));
    
    if (empty($product)) {
        throw new Exception("Product not found in PIM");
    }
    
    // 2. Update website via API
    $response = $apiClient->postToWebsite('/products/update', $product);
    
    // 3. Update database record
    $conn = $db->getConnection();
    $stmt = $conn->prepare("UPDATE products SET last_updated = NOW(), sync_status = ? WHERE sku = ?");
    $status = ($response['status'] === 'success') ? 'success' : 'failed';
    $stmt->bind_param("ss", $status, $sku);
    $stmt->execute();
    $stmt->close();
    
    if ($response['status'] === 'success') {
        $logger->log("Successfully synced product: $sku");
    } else {
        throw new Exception("Failed to sync product: $sku - " . ($response['message'] ?? 'Unknown error'));
    }
} catch (Exception $e) {
    $logger->log($e->getMessage(), 'ERROR');
    exit(1);
}