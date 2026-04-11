<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if this is an admin operation
$isAdminOperation = in_array($method, ['POST', 'PUT', 'DELETE']);
if ($isAdminOperation) {
    require_admin();
}

try {
    if ($method === 'GET') {
        // Get all categories from categories table
        $stmt = $pdo->query('SELECT * FROM categories ORDER BY display_order ASC, id ASC');
        $categories = $stmt->fetchAll();
        
        // Add product counts for each category
        foreach ($categories as &$category) {
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category = ?');
            $countStmt->execute([$category['slug']]);
            $category['productCount'] = $countStmt->fetchColumn();
        }
        
        echo json_encode(['success' => true, 'categories' => $categories]);
        
    } elseif ($method === 'POST') {
        // Create new category
        $name = $body['name'] ?? '';
        $slug = $body['slug'] ?? '';
        $description = $body['description'] ?? '';
        $subtitle = $body['subtitle'] ?? '';
        $icon = $body['icon'] ?? '';
        $order = (int)($body['order'] ?? 1);
        $active = (bool)($body['active'] ?? true);
        
        // Validate required fields
        if (empty($name) || empty($slug)) {
            http_response_code(400);
            echo json_encode(['error' => 'Name and slug are required']);
            exit;
        }
        
        // Check if slug already exists
        $checkStmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
        $checkStmt->execute([$slug]);
        if ($checkStmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Category slug already exists']);
            exit;
        }
        
        // Insert new category
        $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description, subtitle, icon, display_order, active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$name, $slug, $description, $subtitle, $icon, $order, $active]);
        
        $newId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Category created successfully']);
        
    } elseif ($method === 'PUT') {
        // Update existing category
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Category ID is required']);
            exit;
        }
        
        $name = $body['name'] ?? '';
        $slug = $body['slug'] ?? '';
        $description = $body['description'] ?? '';
        $subtitle = $body['subtitle'] ?? '';
        $icon = $body['icon'] ?? '';
        $order = (int)($body['order'] ?? 1);
        $active = (bool)($body['active'] ?? true);
        
        // Validate required fields
        if (empty($name) || empty($slug)) {
            http_response_code(400);
            echo json_encode(['error' => 'Name and slug are required']);
            exit;
        }
        
        // Check if slug already exists for a different category
        $checkStmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ? AND id != ?');
        $checkStmt->execute([$slug, $id]);
        if ($checkStmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Category slug already exists']);
            exit;
        }
        
        // Get the old slug before updating
        $oldStmt = $pdo->prepare('SELECT slug FROM categories WHERE id = ?');
        $oldStmt->execute([$id]);
        $oldSlug = $oldStmt->fetchColumn();
        
        // Update category
        $stmt = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, description = ?, subtitle = ?, icon = ?, display_order = ?, active = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$name, $slug, $description, $subtitle, $icon, $order, $active, $id]);
        
        if ($stmt->rowCount() > 0) {
            // If the slug changed, update all products that reference the old slug
            if ($oldSlug && $oldSlug !== $slug) {
                $updateProductsStmt = $pdo->prepare('UPDATE products SET category = ? WHERE category = ?');
                $updateProductsStmt->execute([$slug, $oldSlug]);
                $updatedProductsCount = $updateProductsStmt->rowCount();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Category updated successfully',
                    'updated_products' => $updatedProductsCount
                ]);
            } else {
                echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
        }
        
    } elseif ($method === 'DELETE') {
        // Delete category
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Category ID is required']);
            exit;
        }
        
        // Check if category has products
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category = (SELECT slug FROM categories WHERE id = ?)');
        $countStmt->execute([$id]);
        $productCount = $countStmt->fetchColumn();
        
        if ($productCount > 0) {
            http_response_code(400);
            echo json_encode(['error' => "Cannot delete category because it contains $productCount products"]);
            exit;
        }
        
        // Delete category
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log('Categories error: ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
}
?>
