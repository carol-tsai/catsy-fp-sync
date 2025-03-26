<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/api_client.php';
require_once __DIR__ . '/../includes/logger.php';

// Initialize components
$logger = new Logger('batch_sync.log');
$db = new Database();
$apiClient = new ApiClient();

try {
    $logger->log("Starting batch product update process");
    
    // 1. Get products needing update (e.g., those marked as 'pending' or outdated)
    $products = getProductsNeedingUpdate($db->getConnection());
    
    if (empty($products)) {
        $logger->log("No products require updating at this time");
        exit(0);
    }
    
    $logger->log("Found " . count($products) . " products needing update");
    
    // 2. Process each product
    $successCount = 0;
    $failureCount = 0;
    
    foreach ($products as $product) {
        try {
            $logger->log("Processing product SKU: " . $product['sku']);
            
            // Update product in database before attempting sync
            markProductAsProcessing($db->getConnection(), $product['id']);
            
            // 3. Fetch latest data from PIM
            $pimData = $apiClient->getFromPIM('/products/' . urlencode($product['sku']));
            
            if (empty($pimData)) {
                throw new Exception("No data returned from PIM for SKU: " . $product['sku']);
            }
            
            // 4. Transform data if needed
            $transformedData = transformProductData($pimData);
            
            // 5. Update website via API
            $response = $apiClient->postToWebsite('/products/update', $transformedData);
            
            if ($response['status'] !== 'success') {
                throw new Exception("API update failed: " . ($response['message'] ?? 'Unknown error'));
            }
            
            // 6. Mark as successful in database
            markProductAsSynced($db->getConnection(), $product['id'], $transformedData);
            $successCount++;
            
            $logger->log("Successfully updated product: " . $product['sku']);
            
            // Optional: Add delay between requests to avoid rate limiting
            usleep(200000); // 200ms delay
            
        } catch (Exception $e) {
            $failureCount++;
            markProductAsFailed($db->getConnection(), $product['id'], $e->getMessage());
            $logger->log("Failed to update product {$product['sku']}: " . $e->getMessage(), 'ERROR');
        }
    }
    
    // 7. Log final results
    $logger->log("Batch update completed. Success: $successCount, Failures: $failureCount");
    
    // 8. Optionally send notification
    if ($failureCount > 0) {
        sendFailureNotification($failureCount);
    }
    
} catch (Exception $e) {
    $logger->log("Fatal error in batch processing: " . $e->getMessage(), 'CRITICAL');
    exit(1);
}

/**
 * Helper Functions
 */

function getProductsNeedingUpdate(mysqli $conn, int $limit = 100): array {
    $query = "SELECT id, sku, name FROM products 
              WHERE sync_status = 'pending' 
              OR last_updated IS NULL 
              OR last_updated < DATE_SUB(NOW(), INTERVAL 1 DAY)
              ORDER BY last_updated ASC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function transformProductData(array $pimData): array {
    return [
        'sku' => $pimData['productCode'] ?? $pimData['sku'],
        'name' => $pimData['name']['en'] ?? $pimData['name'],
        'description' => $pimData['description']['en'] ?? $pimData['description'],
        'price' => $pimData['price']['amount'] ?? $pimData['price'],
        'stock' => $pimData['stock']['quantity'] ?? $pimData['stock'],
        // Add other transformations as needed
    ];
}

function markProductAsProcessing(mysqli $conn, int $productId): void {
    $stmt = $conn->prepare("UPDATE products 
                           SET sync_status = 'pending', 
                               last_sync_attempt = NOW() 
                           WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
}

function markProductAsSynced(mysqli $conn, int $productId, array $data): void {
    $stmt = $conn->prepare("UPDATE products 
                           SET sync_status = 'success',
                               last_updated = NOW(),
                               name = ?,
                               description = ?,
                               price = ?,
                               website_status = 'active'
                           WHERE id = ?");
    $stmt->bind_param("ssdi", 
        $data['name'],
        $data['description'],
        $data['price'],
        $productId
    );
    $stmt->execute();
}

function markProductAsFailed(mysqli $conn, int $productId, string $error): void {
    $stmt = $conn->prepare("UPDATE products 
                           SET sync_status = 'failed',
                               last_sync_attempt = NOW(),
                               website_status = 'sync_error'
                           WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    
    // Optionally log detailed error to a separate table
}

function sendFailureNotification(int $failureCount): void {
    // Implement your notification logic (email, Slack, etc.)
    // Example:
    // mail('admin@example.com', 'Product Sync Failures', "$failureCount products failed to sync");
}