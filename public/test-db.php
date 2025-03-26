<?php
require_once __DIR__ . '/../includes/db_connect.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>Database Connection Successful</h2>";
    echo "<p>MySQL Server Version: " . $conn->server_version . "</p>";
    
    // Test query
    $result = $conn->query("SHOW TABLES");
    echo "<h3>Tables in Database:</h3>";
    while ($row = $result->fetch_array()) {
        echo "<p>" . $row[0] . "</p>";
    }
    
    $db->closeConnection();
} catch (Exception $e) {
    die("<h2>Database Connection Failed</h2><p>" . $e->getMessage() . "</p>");
}