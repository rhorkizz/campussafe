<?php
/**
 * Fix Duplicate Categories Script
 * duplicates entries in incident_categories table
 */

require_once __DIR__ . '/config/db.php';

try {
    $db = getDBConnection();
    if ($db === null) {
        die("Database connection failed.");
    }

    echo "Cleaning up duplicate categories...\n";

    // 1. Identify duplicates
    // We want to keep the one with the lowest ID (or highest, doesn't matter much unless used)
    // But wait, if IDs are used in incidents, we might break relationships if we delete the wrong one.
    // Let's assume we keep the FIRST one (min ID).
    
    // Find duplicates and their IDs
    $sql = "
        SELECT category_name, COUNT(*) as count, GROUP_CONCAT(category_id) as ids 
        FROM incident_categories 
        GROUP BY category_name 
        HAVING count > 1
    ";
    $stmt = $db->query($sql);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($duplicates)) {
        echo "No duplicates found.\n";
    } else {
        $deletedCount = 0;
        foreach ($duplicates as $row) {
            $ids = explode(',', $row['ids']);
            sort($ids); 
            $keepId = $ids[0]; // Keep the first ID
            $removeIds = array_slice($ids, 1);
            
            echo "Processing '{$row['category_name']}': Keeping ID $keepId, removing IDs: " . implode(', ', $removeIds) . "\n";
            
            foreach ($removeIds as $removeId) {
                // Update incidents to point to the kept ID before deleting
                // Check if 'incidents' table uses category_id
                // Schema uses 'category_id' in incidents table.
                
                // Update incidents
                $updateStmt = $db->prepare("UPDATE incidents SET category_id = :keepId WHERE category_id = :removeId");
                $updateStmt->execute(['keepId' => $keepId, 'removeId' => $removeId]);
                
                // Update incident_routing
                $updateRouting = $db->prepare("UPDATE incident_routing SET category_id = :keepId WHERE category_id = :removeId");
                $updateRouting->execute(['keepId' => $keepId, 'removeId' => $removeId]);
                
                // Delete the duplicate category
                $delStmt = $db->prepare("DELETE FROM incident_categories WHERE category_id = :removeId");
                $delStmt->execute(['removeId' => $removeId]);
                $deletedCount++;
            }
        }
        echo "Removed $deletedCount duplicate categories.\n";
    }

    // 2. Add Unique Constraint to prevent future duplicates
    // We use IGNORE in case it already exists (though vanilla MySQL doesn't support ADD CONSTRAINT IF NOT EXISTS well for UKs)
    // We'll wrap in try-catch
    echo "Adding UNIQUE constraint to category_name...\n";
    try {
        $db->exec("ALTER TABLE incident_categories ADD UNIQUE (category_name)");
        echo "Unique constraint added successfully.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
             echo "Constraint failed due to remaining duplicates? " . $e->getMessage() . "\n";
        } elseif (strpos($e->getMessage(), 'already exists') !== false) {
            echo "Constraint already exists.\n";
        } else {
             // In some versions, adding index requires name
             // Let's try adding index if alter failed
             $db->exec("CREATE UNIQUE INDEX idx_category_name ON incident_categories(category_name)");
        }
    }

    echo "Done.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
