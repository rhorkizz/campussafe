<?php
require 'config/db.php';
$pdo = getDBConnection();
if ($pdo) {
    echo "Users Schema:\n";
    $stmt = $pdo->query("DESCRIBE users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} else {
    echo "DB Connection Failed";
}
