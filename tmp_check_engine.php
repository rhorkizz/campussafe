<?php
require 'config/db.php';
$pdo = getDBConnection();
if ($pdo) {
    $stmt = $pdo->query("SHOW TABLE STATUS WHERE Name IN ('users', 'incidents')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Table: " . $row['Name'] . ", Engine: " . $row['Engine'] . ", Collation: " . $row['Collation'] . "\n";
    }
}
