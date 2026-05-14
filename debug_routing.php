<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("Database connection failed.\n");
    }

    echo "--- Categories ---\n";
    $stmt = $pdo->query("SELECT * FROM incident_categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($categories as $cat) {
        echo "ID: {$cat['category_id']} | Name: {$cat['category_name']}\n";
    }

    echo "\n--- Roles ---\n";
    $stmt = $pdo->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($roles as $role) {
        echo "ID: {$role['role_id']} | Name: {$role['role_name']}\n";
    }

    echo "\n--- Routing ---\n";
    $stmt = $pdo->query("SELECT r.category_id, c.category_name, r.assigned_role_id, ro.role_name 
                         FROM incident_routing r
                         JOIN incident_categories c ON r.category_id = c.category_id
                         JOIN roles ro ON r.assigned_role_id = ro.role_id");
    $routing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($routing as $route) {
        echo "Category: {$route['category_name']} ({$route['category_id']}) -> Role: {$route['role_name']} ({$route['assigned_role_id']})\n";
    }

    if (empty($routing)) {
        echo "NO ROUTING RULES FOUND!\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
