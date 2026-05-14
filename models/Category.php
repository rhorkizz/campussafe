<?php
/**
 * Category Model
 * Handles all database operations related to incident categories
 */

class Category {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get all categories
     * @return array Array of categories
     */
    public function getAllCategories() {
        if ($this->db === null) {
            error_log("Database connection is null in Category::getAllCategories");
            return [];
        }
        try {
            $stmt = $this->db->prepare("SELECT * FROM incident_categories ORDER BY category_name ASC");
            $stmt->execute();
            $categories = $stmt->fetchAll();
            // Map field names for backward compatibility
            foreach ($categories as &$cat) {
                $cat['id'] = $cat['category_id'];
                $cat['name'] = $cat['category_name'];
            }
            return $categories;
        } catch (PDOException $e) {
            error_log("Get all categories error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get category by ID
     * @param int $category_id Category ID
     * @return array|null Category data or null if not found
     */
    public function getCategoryById($category_id) {
        if ($this->db === null) {
            return null;
        }
        try {
            $stmt = $this->db->prepare("SELECT * FROM incident_categories WHERE category_id = :category_id");
            $stmt->execute(['category_id' => $category_id]);
            $cat = $stmt->fetch();
            if ($cat) {
                $cat['id'] = $cat['category_id'];
                $cat['name'] = $cat['category_name'];
            }
            return $cat;
        } catch (PDOException $e) {
            error_log("Get category by ID error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create new category
     * @param array $categoryData Category data
     * @return bool|int Category ID on success, false on failure
     */
    public function createCategory($categoryData) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO incident_categories (name, description, created_at) 
                VALUES (:name, :description, :created_at)
            ");
            
            $result = $stmt->execute([
                'name' => $categoryData['name'],
                'description' => $categoryData['description'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Create category error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update category
     * @param int $category_id Category ID
     * @param array $categoryData Updated category data
     * @return bool True on success, false on failure
     */
    public function updateCategory($category_id, $categoryData) {
        try {
            $fields = [];
            $params = ['category_id' => $category_id];

            foreach ($categoryData as $key => $value) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }

            $sql = "UPDATE incident_categories SET " . implode(', ', $fields) . " WHERE id = :category_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update category error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete category
     * @param int $category_id Category ID
     * @return bool True on success, false on failure
     */
    public function deleteCategory($category_id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM incident_categories WHERE id = :category_id");
            return $stmt->execute(['category_id' => $category_id]);
        } catch (PDOException $e) {
            error_log("Delete category error: " . $e->getMessage());
            return false;
        }
    }
}
