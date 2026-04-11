<?php
require_once 'config.php';

try {
    // Check for products that reference non-existent categories
    $stmt = $pdo->query("
        SELECT p.* 
        FROM products p 
        LEFT JOIN categories c ON p.category = c.slug 
        WHERE c.slug IS NULL
    ");
    $orphanedProducts = $stmt->fetchAll();
    
    echo "Found " . count($orphanedProducts) . " orphaned products:\n";
    
    foreach ($orphanedProducts as $product) {
        echo "- Product ID {$product['id']}: {$product['name']} (category: {$product['category']})\n";
        
        $newCategory = '';
        
        // Map orphaned categories to existing categories
        switch ($product['category']) {
            case 'hot-drinks':
                $newCategory = 'coffees';
                break;
            case 'tea':
                $newCategory = 'everyday-teas';
                break;
            case 'matcha':
                $newCategory = 'matcha-teas';
                break;
            case 'special':
                $newCategory = 'specialties';
                break;
            default:
                // Default to coffees if no mapping found
                $newCategory = 'coffees';
                break;
        }
        
        if ($newCategory) {
            $updateStmt = $pdo->prepare('UPDATE products SET category = ? WHERE id = ?');
            $updateStmt->execute([$newCategory, $product['id']]);
            echo "  → Moved to '$newCategory' category\n";
        }
    }
    
    echo "\nOrphaned products fixed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
