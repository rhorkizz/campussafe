<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("Database connection failed.\n");
    }

    echo "--- Recent Incidents ---\n";
    $stmt = $pdo->query("SELECT i.incident_id, i.title, i.category_id, i.assigned_role_id, r.role_name 
                         FROM incidents i
                         LEFT JOIN roles r ON i.assigned_role_id = r.role_id
                         ORDER BY i.created_at DESC
                         LIMIT 5");
    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($incidents as $inc) {
        $roleName = $inc['role_name'] ?? 'NULL';
        echo "ID: {$inc['incident_id']} | Title: {$inc['title']} | CatID: {$inc['category_id']} | AssignedRoleID: {$inc['assigned_role_id']} ($roleName)\n";
    }

    if (empty($incidents)) {
        echo "No incidents found.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
