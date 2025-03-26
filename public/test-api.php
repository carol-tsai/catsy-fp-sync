<?php
require_once __DIR__ . '/../includes/api_client.php';

try {
    $apiClient = new ApiClient();
    
    echo "<h2>PIM API Test</h2>";
    $testProduct = $apiClient->getFromPIM('/products/test-sku-123'); // Use a known test SKU
    echo "<pre>" . print_r($testProduct, true) . "</pre>";
    
    echo "<h2>Website API Test</h2>";
    $testData = ['sku' => 'test-sku-123', 'name' => 'Test Product'];
    $response = $apiClient->postToWebsite('/products/update', $testData);
    echo "<pre>" . print_r($response, true) . "</pre>";
    
} catch (Exception $e) {
    die("<h2>API Test Failed</h2><p>" . $e->getMessage() . "</p>");
}