// Load products from MySQL database for the main website
async function loadProductsFromDatabase() {
    try {
        console.log('Loading products from database...');
        
        const response = await fetch(`${CONFIG.API_BASE_URL}/products.php`);
        const data = await response.json();
        
        if (data.success && data.products) {
            console.log('Products loaded from database:', data.products);
            return data.products;
        } else {
            console.error('Failed to load products from database:', data.error);
            return [];
        }
    } catch (error) {
        console.error('Error loading products from database:', error);
        return [];
    }
}

// Convert database products to website format
function convertDatabaseProducts(dbProducts) {
    return dbProducts.map(product => ({
        id: product.id,
        name: product.name,
        category: product.category,
        regularPrice: parseFloat(product.regular_price),
        largePrice: parseFloat(product.large_price),
        type: product.type,
        description: product.description
    }));
}

// Initialize products for the main website
async function initializeProducts() {
    // Try to load from database first
    const dbProducts = await loadProductsFromDatabase();
    
    if (dbProducts.length > 0) {
        // Use products from database
        window.products = convertDatabaseProducts(dbProducts);
        console.log('Using products from database:', window.products);
    } else {
        // Fallback to hardcoded products
        console.log('Using fallback hardcoded products');
        window.products = [
            // Coffee Drinks
            { id: 1, name: 'Breakfast Tea', category: 'coffees', regularPrice: 2.72, largePrice: 3.84, type: 'tea', description: 'Classic English breakfast tea' },
            { id: 2, name: 'Earl Grey', category: 'coffees', regularPrice: 2.88, largePrice: 4.0, type: 'tea', description: 'Bergamot-scented black tea' },
            { id: 3, name: 'Flat White', category: 'coffees', regularPrice: 2.72, largePrice: 3.84, type: 'coffee', description: 'Smooth coffee with microfoam' },
            
            // Cold Drinks
            { id: 4, name: 'Pistachio Latte', category: 'cold-drinks', regularPrice: 3.36, largePrice: 4.48, type: 'coffee', description: 'Creamy pistachio-flavored latte' },
            { id: 5, name: 'Iced Matcha Latte', category: 'cold-drinks', regularPrice: 3.36, largePrice: 4.48, type: 'matcha', description: 'Refreshing iced matcha with milk' },
            
            // Everyday Teas
            { id: 6, name: 'Milk Oolong', category: 'everyday-teas', regularPrice: 3.2, largePrice: 4.32, type: 'tea', description: 'Creamy and smooth oolong tea' },
            { id: 7, name: 'Jasmine Green', category: 'everyday-teas', regularPrice: 3.2, largePrice: 4.32, type: 'tea', description: 'Fragrant jasmine-scented green tea' },
            
            // Matcha Teas
            { id: 8, name: 'Classic Matcha Latte', category: 'matcha-teas', regularPrice: 3.36, largePrice: 4.48, type: 'matcha', description: 'Traditional matcha with steamed milk' },
            { id: 9, name: 'Matcha Frappe', category: 'matcha-teas', regularPrice: 3.84, largePrice: 4.96, type: 'matcha', description: 'Blended matcha with ice' },
            
            // Coffee
            { id: 10, name: 'Espresso', category: 'coffees', regularPrice: 2.24, largePrice: 3.36, type: 'coffee', description: 'Rich and intense espresso shot' },
            { id: 11, name: 'Cappuccino', category: 'coffees', regularPrice: 2.72, largePrice: 3.84, type: 'coffee', description: 'Classic cappuccino with foam' },
            
            // Specialties
            { id: 12, name: 'Mork 70% Chocolate', category: 'specialties', regularPrice: 3.04, largePrice: 4.16, type: 'specialty', description: 'Rich 70% dark chocolate drink' },
            { id: 13, name: 'Golden Turmeric Latte', category: 'specialties', regularPrice: 3.36, largePrice: 4.48, type: 'specialty', description: 'Anti-inflammatory turmeric latte' }
        ];
    }
    
    // Refresh the product display
    if (typeof refreshProductDisplay === 'function') {
        refreshProductDisplay();
    }
}

// Call this when the page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeProducts();
});
