<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("Database connection failed.\n");
    }

    echo "--- Incidents Table Columns ---\n";
    $stmt = $pdo->query("DESCRIBE incidents");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        // Handle different PDO fetch modes or case sensitivity
        $field = $col['Field'] ?? $col['field'];
        echo $field . "\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
