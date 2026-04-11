<?php
require_once 'config.php';

try {
    $changes = [];
    
    // Add subcategory column to products table
    try {
        $sql = "ALTER TABLE products ADD COLUMN subcategory VARCHAR(255) NULL AFTER category";
        $pdo->exec($sql);
        $changes[] = 'Added subcategory column to products table';
    } catch (PDOException $e) {
        if ($e->getCode() != '42S21') {
            throw $e;
        }
    }
    
    // Add delivery_option column to orders table
    try {
        $sql = "ALTER TABLE orders ADD COLUMN delivery_option VARCHAR(100) DEFAULT 'Deliver to chair' AFTER notes";
        $pdo->exec($sql);
        $changes[] = 'Added delivery_option column to orders table';
    } catch (PDOException $e) {
        if ($e->getCode() != '42S21') {
            throw $e;
        }
    }
    
    if (empty($changes)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Database already up to date.',
            'changes' => []
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => 'Database updated successfully.',
            'changes' => $changes
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database update failed: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database update failed: ' . $e->getMessage()
    ]);
}
?>
