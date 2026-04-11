<?php
require_once 'config.php';

// Create database tables
try {
    // Create orders table
    $sql = "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(50) UNIQUE NOT NULL,
        chair_number VARCHAR(10) NOT NULL,
        customer_name VARCHAR(100),
        customer_email VARCHAR(100),
        items JSON NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
        stripe_payment_intent_id VARCHAR(100),
        notes TEXT,
        delivery_option VARCHAR(100) DEFAULT 'Deliver to chair',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✅ Orders table created successfully\n";
    
    // Create products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        type VARCHAR(50),
        description TEXT,
        regular_price DECIMAL(10,2),
        large_price DECIMAL(10,2),
        is_available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✅ Products table created successfully\n";
    
    // Create admin_users table
    $sql = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✅ Admin users table created successfully\n";
    
    // Create categories table
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(50) UNIQUE NOT NULL,
        description TEXT,
        icon VARCHAR(10),
        display_order INT DEFAULT 1,
        active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✅ Categories table created successfully\n";
    
    // Insert default categories
    $defaultCategories = [
        ['Hot Drinks', 'hot-drinks', 'Traditional teas and hot coffees', '☕', 1, true],
        ['Cold Drinks', 'cold-drinks', 'Iced and cold beverages', '🧊', 2, true],
        ['Loose Leaf Tea', 'everyday-teas', 'Premium tea selections', '🍵', 3, true],
        ['Matcha', 'matcha-teas', 'Matcha-based drinks', '🫖', 4, true],
        ['Coffee', 'coffees', 'Espresso and coffee drinks', '☕', 5, true],
        ['Specials', 'specialties', 'Specialty and unique beverages', '✨', 6, true]
    ];
    
    $sql = "INSERT IGNORE INTO categories (name, slug, description, icon, display_order, active) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    foreach ($defaultCategories as $category) {
        $stmt->execute($category);
    }
    
    echo "✅ Default categories inserted successfully\n";
    
    // Insert default admin user (password: teas2024)
    $defaultPassword = password_hash('teas2024', PASSWORD_DEFAULT);
    $sql = "INSERT IGNORE INTO admin_users (username, password_hash, email) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['admin', $defaultPassword, 'admin@teasandcs.com']);
    echo "✅ Default admin user created (username: admin, password: teas2024)\n";
    
    // Insert sample products
    $products = [
        ['Americano', 'hot-drinks', 'coffee', 'Rich and bold espresso with hot water', 2.50, 3.50],
        ['Cappuccino', 'hot-drinks', 'coffee', 'Espresso with steamed milk and foam', 3.00, 4.00],
        ['Latte', 'hot-drinks', 'coffee', 'Espresso with steamed milk', 3.50, 4.50],
        ['Green Tea', 'tea', 'tea', 'Premium loose leaf green tea', 2.00, 2.75],
        ['Earl Grey', 'tea', 'tea', 'Classic bergamot black tea', 2.25, 3.00],
        ['Matcha Latte', 'matcha', 'matcha', 'Traditional matcha with steamed milk', 4.00, 5.00],
        ['Iced Coffee', 'cold-drinks', 'coffee', 'Cold brew coffee over ice', 2.75, 3.75],
        ['Iced Tea', 'cold-drinks', 'tea', 'Refreshing iced tea', 2.25, 3.00],
        ['Special Blend', 'special', 'special', 'Our signature house blend', 3.75, 4.75]
    ];
    
    $sql = "INSERT IGNORE INTO products (name, category, type, description, regular_price, large_price) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    foreach ($products as $product) {
        $stmt->execute($product);
    }
    
    echo "✅ Sample products inserted successfully\n";
    echo "\n🎉 Database setup completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Update your config.js with the correct API URL\n";
    echo "2. Test the database connection\n";
    echo "3. Deploy your files to Hostinger\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up database: " . $e->getMessage() . "\n";
    echo "Please check your database credentials in config.php\n";
}
?>
