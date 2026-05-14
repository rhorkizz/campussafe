<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/Incident.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("Database connection failed.\n");
    }

    $model = new Incident($pdo);

    // Test for STAFFF001 (Role 2 - Campus Officer)
    echo "--- Incidents for STAFF001 (Role 2) ---\n";
    $incidents = $model->getIncidentsByOfficer('STAFF001');
    if (empty($incidents)) {
        echo "No incidents found.\n";
    } else {
        foreach ($incidents as $inc) {
            echo "ID: {$inc['id']} | Title: {$inc['title']} | RoleID: {$inc['assigned_role_id']}\n";
        }
    }

    // Test for HOST001 (Role 3 - Hostel Officer)
    echo "\n--- Incidents for HOST001 (Role 3) ---\n";
    $incidents = $model->getIncidentsByOfficer('HOST001');
    if (empty($incidents)) {
        echo "No incidents found.\n";
    } else {
        foreach ($incidents as $inc) {
            echo "ID: {$inc['id']} | Title: {$inc['title']} | RoleID: {$inc['assigned_role_id']}\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
