<?php
require_once __DIR__ . '/config/db.php';
try {
    $pdo = getDBConnection();
    if (!$pdo) die("Failed to connect\n");
    $stmt = $pdo->query('DESCRIBE incidents');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("%-15s | %-20s\n", $row['Field'], $row['Type']);
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
