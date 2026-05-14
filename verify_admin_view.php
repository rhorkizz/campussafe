<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/Incident.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("Database connection failed.\n");
    }

    $model = new Incident($pdo);
    echo "--- Admin View Incidents ---\n";
    $incidents = $model->getAllIncidents();
    
    if (empty($incidents)) {
        echo "No incidents found.\n";
    } else {
        foreach ($incidents as $inc) {
            $officerDisplay = $inc['officer_name'] ?? ($inc['assigned_role_name'] ?? 'Unassigned');
            echo "ID: {$inc['id']} | Title: {$inc['title']} | RoleID: {$inc['assigned_role_id']} | UserID: {$inc['assigned_user_id']} | Display: $officerDisplay\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
