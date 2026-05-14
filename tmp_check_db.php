<?php
require 'config/db.php';
$pdo = getDBConnection();
if ($pdo) {
    echo "Tables:\n";
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "- " . $row[0] . "\n";
    }
    
    echo "\nIncidents Schema:\n";
    $stmt = $pdo->query("DESCRIBE incidents");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} else {
    echo "DB Connection Failed";
}
