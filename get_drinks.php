<?php
/**
 * API endpoint to fetch drinks from database
 * Returns JSON data of all drinks organized by category
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    // Fetch all products from your existing table, ordered by category and name
    $stmt = $pdo->query("
        SELECT 
            id,
            name,
            category,
            subcategory,
            regular_price,
            large_price,
            type,
            description
        FROM products
        ORDER BY category, name
    ");
    
    $products = $stmt->fetchAll();
    
    // Organize products by category
    $organized = [];
    foreach ($products as $product) {
        // Use category as the main grouping, or 'Other' if empty
        $category = !empty($product['category']) ? ucfirst($product['category']) : 'Other';
        
        if (!isset($organized[$category])) {
            $organized[$category] = [];
        }
        
        // Build product item with both regular and large prices if available
        $item = [
            'id' => $product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'type' => $product['type'],
            'subcategory' => $product['subcategory'],
            'regular_price' => $product['regular_price'],
            'large_price' => $product['large_price']
        ];
        
        $organized[$category][] = $item;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $organized
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database Error: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

