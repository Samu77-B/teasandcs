<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

// Handle different endpoints
if ($method === 'GET' && strpos($path, '/products') !== false) {
    // Get all products
    try {
        $sql = "SELECT * FROM products ORDER BY category, name";
        $stmt = $pdo->query($sql);
        $products = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'products' => $products]);
    } catch (Exception $e) {
        http_response_code(500);
        // Don't expose detailed error messages in production
        error_log('Products fetch error: ' . $e->getMessage());
        echo json_encode(['error' => 'Failed to fetch products']);
    }
    
} elseif ($method === 'POST' && strpos($path, '/products') !== false) {
    // Create new product (admin only)
    require_admin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }
    
    try {
        $sql = "INSERT INTO products (name, category, subcategory, type, description, regular_price, large_price) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input['name'] ?? '',
            $input['category'] ?? '',
            $input['subcategory'] ?? null,
            $input['type'] ?? '',
            $input['description'] ?? '',
            $input['regular_price'] ?? 0,
            $input['large_price'] ?? 0
        ]);
        
        $productId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'product_id' => $productId]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Product creation error: ' . $e->getMessage());
        echo json_encode(['error' => 'Failed to create product']);
    }
    
} elseif ($method === 'PUT' && strpos($path, '/products') !== false) {
    // Update product (admin only)
    require_admin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = $input['id'] ?? '';
    
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID is required']);
        exit;
    }
    
    try {
        $sql = "UPDATE products SET 
                name = ?, 
                category = ?, 
                subcategory = ?,
                type = ?, 
                description = ?, 
                regular_price = ?, 
                large_price = ?,
                updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input['name'] ?? '',
            $input['category'] ?? '',
            $input['subcategory'] ?? null,
            $input['type'] ?? '',
            $input['description'] ?? '',
            $input['regular_price'] ?? 0,
            $input['large_price'] ?? 0,
            $productId
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Product update error: ' . $e->getMessage());
        echo json_encode(['error' => 'Failed to update product']);
    }
    
} elseif ($method === 'DELETE' && strpos($path, '/products') !== false) {
    // Delete product (admin only)
    require_admin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = $input['id'] ?? '';
    
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID is required']);
        exit;
    }
    
    try {
        $sql = "DELETE FROM products WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Product deletion error: ' . $e->getMessage());
        echo json_encode(['error' => 'Failed to delete product']);
    }
    
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
}
?>
