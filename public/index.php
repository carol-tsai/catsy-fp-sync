<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

// Check login
checkLogin();

// Get products from database
$db = new Database();
$conn = $db->getConnection();

$query = "SELECT id, sku, name, price, last_updated, sync_status FROM products ORDER BY last_updated DESC";
$result = $conn->query($query);

$products = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
$db->closeConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Sync Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Product Sync Dashboard</h1>
        
        <div class="actions">
            <button id="syncAll" class="btn">Sync All Products</button>
            <span id="syncStatus"></span>
        </div>
        
        <table id="productsTable">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Last Updated</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr data-sku="<?= htmlspecialchars($product['sku']) ?>">
                    <td><?= htmlspecialchars($product['sku']) ?></td>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td>$<?= number_format($product['price'], 2) ?></td>
                    <td><?= $product['last_updated'] ? date('M j, Y H:i', strtotime($product['last_updated'])) : 'Never' ?></td>
                    <td class="status-<?= strtolower($product['sync_status']) ?>">
                        <?= ucfirst($product['sync_status']) ?>
                    </td>
                    <td>
                        <button class="btn sync-btn" data-sku="<?= htmlspecialchars($product['sku']) ?>">
                            Sync Now
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <script src="scripts.js"></script>
</body>
</html>