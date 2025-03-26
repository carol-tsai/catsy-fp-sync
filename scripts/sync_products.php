<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/api_client.php';
require_once __DIR__ . '/../includes/logger.php';

$logger = new Logger();
$db = new Database();
$apiClient = new ApiClient();

try {
    $logger->log("Starting product sync");
    
    // 1. Fetch products from PIM
    $products = $apiClient->getFromPIM('/products', ['updatedSince' => '2023-11-01']);
    
    // 2. Process each product
    foreach ($products as $product) {
        // Transform data if needed
        $websiteProduct = [
            'id' => $product['sku'],
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => $product['price'],
            // ... other fields
        ];
        
        // 3. Update website via API
        $response = $apiClient->postToWebsite('/products/update', $websiteProduct);
        
        if ($response['status'] === 'success') {
            $logger->log("Updated product: " . $product['sku']);
        } else {
            $logger->log("Failed to update product: " . $product['sku'] . " - " . $response['message'], 'ERROR');
        }
    }
    
    $logger->log("Product sync completed");
} catch (Exception $e) {
    $logger->log("Sync failed: " . $e->getMessage(), 'ERROR');
}