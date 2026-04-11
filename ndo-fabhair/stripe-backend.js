// Stripe Backend Server for Teas & C's PWA
// This is a production-ready Node.js server to handle Stripe payments and admin operations
// Run with: node stripe-backend.js

const express = require('express');
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY);
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const fs = require('fs');
const path = require('path');

// Load environment variables
require('dotenv').config();

if (!process.env.STRIPE_SECRET_KEY) {
    console.error('ERROR: STRIPE_SECRET_KEY is not set. Add it in Railway Variables.');
}

const app = express();
const PORT = process.env.PORT || 3001;

// Railway / reverse proxy — needed for rate limiting and secure cookies behind proxy
app.set('trust proxy', 1);

// Safe HTTP headers (no default CSP — would break Stripe + inline scripts in static HTML)
app.use(helmet({
    contentSecurityPolicy: false,
    crossOriginEmbedderPolicy: false
}));

/** Slow down abuse without hurting a normal salon day */
const orderCreateLimiter = rateLimit({
    windowMs: 15 * 60 * 1000,
    max: 80,
    standardHeaders: true,
    legacyHeaders: false,
    message: { error: 'Too many orders from this network. Please try again in a few minutes.' }
});
const paymentIntentLimiter = rateLimit({
    windowMs: 15 * 60 * 1000,
    max: 60,
    standardHeaders: true,
    legacyHeaders: false,
    message: { error: 'Too many payment attempts. Please wait and try again.' }
});

// Data storage files
const DATA_DIR = path.join(__dirname, 'data');
const PRODUCTS_FILE = path.join(DATA_DIR, 'products.json');
const ORDERS_FILE = path.join(DATA_DIR, 'orders.json');
const DISCOUNTS_FILE = path.join(DATA_DIR, 'discounts.json');
const CATEGORIES_FILE = path.join(DATA_DIR, 'categories.json');

// Ensure data directory exists
if (!fs.existsSync(DATA_DIR)) {
    fs.mkdirSync(DATA_DIR, { recursive: true });
}

// Initialize data files if they don't exist
function initializeDataFiles() {
    if (!fs.existsSync(PRODUCTS_FILE)) {
        const defaultProducts = [
            {
                id: 1,
                name: 'Breakfast Tea',
                category: 'hot-drinks',
                regularPrice: 2.72,
                largePrice: 3.84,
                type: 'tea',
                description: 'Classic English breakfast tea'
            },
            {
                id: 2,
                name: 'Earl Grey',
                category: 'hot-drinks',
                regularPrice: 2.88,
                largePrice: 4.0,
                type: 'tea',
                description: 'Bergamot-scented black tea'
            },
            {
                id: 3,
                name: 'Flat White',
                category: 'hot-drinks',
                regularPrice: 2.72,
                largePrice: 3.84,
                type: 'coffee',
                description: 'Smooth coffee with microfoam'
            },
            {
                id: 4,
                name: 'Pistachio Latte',
                category: 'cold-drinks',
                regularPrice: 3.36,
                largePrice: 4.48,
                type: 'coffee',
                description: 'Creamy pistachio-flavored latte'
            },
            {
                id: 5,
                name: 'Milk Oolong',
                category: 'everyday-teas',
                regularPrice: 3.2,
                largePrice: 4.32,
                type: 'tea',
                description: 'Creamy and smooth oolong tea'
            },
            {
                id: 6,
                name: 'Classic Matcha Latte',
                category: 'matcha-teas',
                regularPrice: 3.36,
                largePrice: 4.48,
                type: 'matcha',
                description: 'Traditional matcha with steamed milk'
            },
            {
                id: 7,
                name: 'Espresso',
                category: 'coffees',
                regularPrice: 2.24,
                largePrice: 3.36,
                type: 'coffee',
                description: 'Rich and intense espresso shot'
            },
            {
                id: 8,
                name: 'Mork 70% Chocolate',
                category: 'specialties',
                regularPrice: 3.04,
                largePrice: 4.16,
                type: 'specialty',
                description: 'Premium dark chocolate drink'
            }
        ];
        fs.writeFileSync(PRODUCTS_FILE, JSON.stringify(defaultProducts, null, 2));
    }

    if (!fs.existsSync(ORDERS_FILE)) {
        fs.writeFileSync(ORDERS_FILE, JSON.stringify([], null, 2));
    }

    if (!fs.existsSync(DISCOUNTS_FILE)) {
        fs.writeFileSync(DISCOUNTS_FILE, JSON.stringify([], null, 2));
    }

    if (!fs.existsSync(CATEGORIES_FILE)) {
        fs.writeFileSync(CATEGORIES_FILE, JSON.stringify([], null, 2));
    }
}

// Initialize data files
initializeDataFiles();

// Helper functions for data management
function readDataFile(filePath) {
    try {
        const data = fs.readFileSync(filePath, 'utf8');
        return JSON.parse(data);
    } catch (error) {
        console.error(`Error reading ${filePath}:`, error);
        return [];
    }
}

function writeDataFile(filePath, data) {
    try {
        fs.writeFileSync(filePath, JSON.stringify(data, null, 2));
        return true;
    } catch (error) {
        console.error(`Error writing ${filePath}:`, error);
        return false;
    }
}

// Middleware
const allowedOrigins = process.env.ALLOWED_ORIGINS 
    ? process.env.ALLOWED_ORIGINS.split(',')
    : [
        'https://teasandcs.netlify.app',
        'https://ordering.teasandcs.com',
        'https://order.teasandcs.com', 
        'https://teasandcs.com',
        'https://www.teasandcs.com',
        'https://teasandcs.com/ndo-fabhair',
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:8080',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://127.0.0.1:8080'
    ];

app.use(cors({
    origin: (origin, cb) => {
        if (!origin) return cb(null, true);
        if (allowedOrigins.includes(origin)) return cb(null, true);
        if (origin.endsWith('.vercel.app')) return cb(null, true);
        if (origin === 'https://teasandcs.com' || origin.startsWith('https://teasandcs.com') || origin.startsWith('http://teasandcs.com')) return cb(null, true);
        cb(null, false);
    },
    credentials: true
}));
app.use(express.json());

// Do NOT put express.static(__dirname) here — the repo still contains legacy api/*.php files.
// Static was shadowing GET /api/products.php and /api/categories.php with raw PHP source (starts with "<"),
// which breaks the admin/PWA JSON parsers. Static is registered after all API routes (see bottom of file).

// PHP-compatible routes (frontend expects .php extension when API_BASE_URL ends with /api)
app.get('/api/categories.php', (req, res) => {
    try {
        const categories = readDataFile(CATEGORIES_FILE);
        res.json({ success: true, categories });
    } catch (error) {
        console.error('Error fetching categories:', error);
        res.status(500).json({ error: 'Failed to fetch categories' });
    }
});

app.get('/api/products.php', (req, res) => {
    try {
        const products = readDataFile(PRODUCTS_FILE);
        res.json({ success: true, products });
    } catch (error) {
        console.error('Error fetching products:', error);
        res.status(500).json({ error: 'Failed to fetch products' });
    }
});

// Create PaymentIntent endpoint
app.post('/create-payment-intent', paymentIntentLimiter, async (req, res) => {
    try {
        if (!process.env.STRIPE_SECRET_KEY) {
            console.error('STRIPE_SECRET_KEY missing in Railway Variables');
            return res.status(500).json({ error: 'Payment server not configured. Check Railway logs.' });
        }
        const { amount, currency = 'gbp' } = req.body;
        const amountNum = Number(amount);
        // Validate amount (frontend sends pounds, e.g. 5.50). Stripe UK practical minimum £0.30; we use £0.50 for card fees UX
        if (amount == null || amount === '' || !Number.isFinite(amountNum) || amountNum < 0.5) {
            return res.status(400).json({
                error: 'Invalid amount — total must be at least £0.50. Check product prices in admin (not £0) and discount %.'
            });
        }
        const amountPence = Math.round(amountNum * 100);
        
        // Create PaymentIntent (automatic_payment_methods enables Apple Pay, Google Pay, etc.)
        const paymentIntent = await stripe.paymentIntents.create({
            amount: amountPence,
            currency: currency,
            automatic_payment_methods: { enabled: true },
            metadata: { integration_check: 'accept_a_payment' },
        });
        
        res.json({
            clientSecret: paymentIntent.client_secret,
        });
        
    } catch (error) {
        console.error('Error creating payment intent:', error);
        res.status(500).json({ error: 'Failed to create payment intent' });
    }
});

// PHP-compatible payment intent (frontend calls /api/create-payment-intent.php)
app.post('/api/create-payment-intent.php', paymentIntentLimiter, async (req, res) => {
    try {
        if (!process.env.STRIPE_SECRET_KEY) {
            console.error('STRIPE_SECRET_KEY missing in Railway Variables');
            return res.status(500).json({ error: 'Payment server not configured. Check Railway logs.' });
        }
        const { amount, currency = 'gbp' } = req.body;
        const amountNumPhp = Number(amount);
        if (amount == null || amount === '' || !Number.isFinite(amountNumPhp) || amountNumPhp < 0.5) {
            return res.status(400).json({
                error: 'Invalid amount — total must be at least £0.50. Check product prices in admin (not £0) and discount %.'
            });
        }
        const amountPence = Math.round(amountNumPhp * 100);
        const paymentIntent = await stripe.paymentIntents.create({
            amount: amountPence,
            currency: currency,
            automatic_payment_methods: { enabled: true },
            metadata: { integration_check: 'accept_a_payment' },
        });
        res.json({ clientSecret: paymentIntent.client_secret });
    } catch (error) {
        console.error('Payment intent error:', error.message || error);
        res.status(500).json({ error: 'Failed to create payment intent' });
    }
});

// Webhook endpoint for Stripe events
app.post('/webhook', express.raw({type: 'application/json'}), (req, res) => {
    const sig = req.headers['stripe-signature'];
    const webhookSecret = process.env.STRIPE_WEBHOOK_SECRET || 'whsec_your_webhook_secret';
    let event;
    
    try {
        event = stripe.webhooks.constructEvent(req.body, sig, webhookSecret);
    } catch (err) {
        console.log(`Webhook signature verification failed.`, err.message);
        return res.status(400).send(`Webhook Error: ${err.message}`);
    }
    
    // Handle the event
    switch (event.type) {
        case 'payment_intent.succeeded':
            const paymentIntent = event.data.object;
            console.log('Payment succeeded:', paymentIntent.id);
            // Here you would update your database, send confirmation emails, etc.
            break;
        case 'payment_intent.payment_failed':
            const failedPayment = event.data.object;
            console.log('Payment failed:', failedPayment.id);
            // Handle failed payment
            break;
        default:
            console.log(`Unhandled event type ${event.type}`);
    }
    
    res.json({received: true});
});

// Admin Authentication Middleware
function authenticateAdmin(req, res, next) {
    const authHeader = req.headers.authorization;
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
        return res.status(401).json({ error: 'Unauthorized' });
    }
    
    const token = authHeader.substring(7);
    // Simple token validation (in production, use JWT or similar)
    const adminToken = process.env.ADMIN_TOKEN || 'harvest-bean-gauge-x7K9mP2q';
    if (token !== adminToken) {
        return res.status(401).json({ error: 'Invalid token' });
    }
    
    next();
}

// Admin API Endpoints

// Get all products
app.get('/api/admin/products', authenticateAdmin, (req, res) => {
    try {
        const products = readDataFile(PRODUCTS_FILE);
        res.json(products);
    } catch (error) {
        console.error('Error fetching products:', error);
        res.status(500).json({ error: 'Failed to fetch products' });
    }
});

// Create or update product
app.post('/api/admin/products', authenticateAdmin, (req, res) => {
    try {
        const products = readDataFile(PRODUCTS_FILE);
        const { id, name, category, regularPrice, largePrice, type, description } = req.body;
        
        if (id) {
            // Update existing product
            const productIndex = products.findIndex(p => p.id === id);
            if (productIndex !== -1) {
                products[productIndex] = { id, name, category, regularPrice, largePrice, type, description };
            } else {
                return res.status(404).json({ error: 'Product not found' });
            }
        } else {
            // Create new product
            const newId = Math.max(...products.map(p => p.id), 0) + 1;
            products.push({ id: newId, name, category, regularPrice, largePrice, type, description });
        }
        
        if (writeDataFile(PRODUCTS_FILE, products)) {
            res.json({ success: true, message: 'Product saved successfully' });
        } else {
            res.status(500).json({ error: 'Failed to save product' });
        }
    } catch (error) {
        console.error('Error saving product:', error);
        res.status(500).json({ error: 'Failed to save product' });
    }
});

// Delete product
app.delete('/api/admin/products/:id', authenticateAdmin, (req, res) => {
    try {
        const products = readDataFile(PRODUCTS_FILE);
        const productId = parseInt(req.params.id);
        const filteredProducts = products.filter(p => p.id !== productId);
        
        if (writeDataFile(PRODUCTS_FILE, filteredProducts)) {
            res.json({ success: true, message: 'Product deleted successfully' });
        } else {
            res.status(500).json({ error: 'Failed to delete product' });
        }
    } catch (error) {
        console.error('Error deleting product:', error);
        res.status(500).json({ error: 'Failed to delete product' });
    }
});

// Admin categories (JSON file — same auth as products; avoids Hostinger categories.php)
app.get('/api/admin/categories', authenticateAdmin, (req, res) => {
    try {
        let categories = readDataFile(CATEGORIES_FILE);
        if (!Array.isArray(categories)) {
            categories = [];
        }
        res.json({ success: true, categories });
    } catch (error) {
        console.error('Error fetching categories:', error);
        res.status(500).json({ error: 'Failed to fetch categories' });
    }
});

app.post('/api/admin/categories', authenticateAdmin, (req, res) => {
    try {
        let categories = readDataFile(CATEGORIES_FILE);
        if (!Array.isArray(categories)) {
            categories = [];
        }
        const { id, name, slug, description, subtitle, icon, order, active, subcategories } = req.body;
        if (id != null) {
            const categoryId = parseInt(id, 10);
            const idx = categories.findIndex(c => c.id === categoryId);
            if (idx === -1) {
                return res.status(404).json({ error: 'Category not found' });
            }
            categories[idx] = {
                ...categories[idx],
                name,
                slug,
                description: description || '',
                subtitle: subtitle != null ? subtitle : categories[idx].subtitle,
                icon: icon != null ? icon : categories[idx].icon,
                order: order != null ? order : categories[idx].order,
                active: active !== false
            };
            if (subcategories !== undefined) {
                categories[idx].subcategories = subcategories;
            }
        } else {
            const newId = Math.max(0, ...categories.map(c => c.id || 0)) + 1;
            const cat = {
                id: newId,
                name,
                slug,
                description: description || '',
                subtitle: subtitle || '',
                icon: icon || '📁',
                order: order != null ? order : newId,
                active: active !== false,
                productCount: 0
            };
            if (subcategories) {
                cat.subcategories = subcategories;
            }
            categories.push(cat);
        }
        if (writeDataFile(CATEGORIES_FILE, categories)) {
            res.json({ success: true, message: 'Category saved successfully' });
        } else {
            res.status(500).json({ error: 'Failed to save category' });
        }
    } catch (error) {
        console.error('Error saving category:', error);
        res.status(500).json({ error: 'Failed to save category' });
    }
});

app.delete('/api/admin/categories/:id', authenticateAdmin, (req, res) => {
    try {
        let categories = readDataFile(CATEGORIES_FILE);
        if (!Array.isArray(categories)) {
            categories = [];
        }
        const categoryId = parseInt(req.params.id, 10);
        const filtered = categories.filter(c => c.id !== categoryId);
        if (filtered.length === categories.length) {
            return res.status(404).json({ error: 'Category not found' });
        }
        if (writeDataFile(CATEGORIES_FILE, filtered)) {
            res.json({ success: true, message: 'Category deleted successfully' });
        } else {
            res.status(500).json({ error: 'Failed to delete category' });
        }
    } catch (error) {
        console.error('Error deleting category:', error);
        res.status(500).json({ error: 'Failed to delete category' });
    }
});

// Get all discounts
app.get('/api/admin/discounts', authenticateAdmin, (req, res) => {
    try {
        const discounts = readDataFile(DISCOUNTS_FILE);
        res.json(discounts);
    } catch (error) {
        console.error('Error fetching discounts:', error);
        res.status(500).json({ error: 'Failed to fetch discounts' });
    }
});

// Create or update discount
app.post('/api/admin/discounts', authenticateAdmin, (req, res) => {
    try {
        const discounts = readDataFile(DISCOUNTS_FILE);
        const { id, name, code, type, value, minOrderAmount, maxUses, description } = req.body;
        
        if (id) {
            // Update existing discount
            const discountIndex = discounts.findIndex(d => d.id === id);
            if (discountIndex !== -1) {
                discounts[discountIndex] = { id, name, code, type, value, minOrderAmount, maxUses, description, usedCount: discounts[discountIndex].usedCount || 0 };
            } else {
                return res.status(404).json({ error: 'Discount not found' });
            }
        } else {
            // Create new discount
            const newId = Math.max(...discounts.map(d => d.id), 0) + 1;
            discounts.push({ id: newId, name, code, type, value, minOrderAmount, maxUses, description, usedCount: 0 });
        }
        
        if (writeDataFile(DISCOUNTS_FILE, discounts)) {
            res.json({ success: true, message: 'Discount saved successfully' });
        } else {
            res.status(500).json({ error: 'Failed to save discount' });
        }
    } catch (error) {
        console.error('Error saving discount:', error);
        res.status(500).json({ error: 'Failed to save discount' });
    }
});

// Delete discount
app.delete('/api/admin/discounts/:id', authenticateAdmin, (req, res) => {
    try {
        const discounts = readDataFile(DISCOUNTS_FILE);
        const discountId = parseInt(req.params.id);
        const filteredDiscounts = discounts.filter(d => d.id !== discountId);
        
        if (writeDataFile(DISCOUNTS_FILE, filteredDiscounts)) {
            res.json({ success: true, message: 'Discount deleted successfully' });
        } else {
            res.status(500).json({ error: 'Failed to delete discount' });
        }
    } catch (error) {
        console.error('Error deleting discount:', error);
        res.status(500).json({ error: 'Failed to delete discount' });
    }
});

// Get all orders
app.get('/api/admin/orders', authenticateAdmin, (req, res) => {
    try {
        const orders = readDataFile(ORDERS_FILE);
        res.json(orders);
    } catch (error) {
        console.error('Error fetching orders:', error);
        res.status(500).json({ error: 'Failed to fetch orders' });
    }
});

// Update order status
app.put('/api/admin/orders/:id/status', authenticateAdmin, (req, res) => {
    try {
        const orders = readDataFile(ORDERS_FILE);
        const orderId = parseInt(req.params.id);
        const { status } = req.body;
        
        const orderIndex = orders.findIndex(o => o.id === orderId);
        if (orderIndex !== -1) {
            orders[orderIndex].status = status;
            if (status === 'completed') {
                orders[orderIndex].completedAt = new Date().toISOString();
            }
            
            if (writeDataFile(ORDERS_FILE, orders)) {
                res.json({ success: true, message: 'Order status updated successfully' });
            } else {
                res.status(500).json({ error: 'Failed to update order status' });
            }
        } else {
            res.status(404).json({ error: 'Order not found' });
        }
    } catch (error) {
        console.error('Error updating order status:', error);
        res.status(500).json({ error: 'Failed to update order status' });
    }
});

// Remove all completed orders (barista "clear completed list") — must be before /:id delete
app.delete('/api/admin/orders/completed', authenticateAdmin, (req, res) => {
    try {
        const orders = readDataFile(ORDERS_FILE);
        if (!Array.isArray(orders)) {
            return res.status(500).json({ error: 'Invalid orders data' });
        }
        const pending = orders.filter(o => o.status !== 'completed');
        const removed = orders.length - pending.length;
        if (writeDataFile(ORDERS_FILE, pending)) {
            res.json({ success: true, message: 'Completed orders cleared', removed });
        } else {
            res.status(500).json({ error: 'Failed to save orders' });
        }
    } catch (error) {
        console.error('Error clearing completed orders:', error);
        res.status(500).json({ error: 'Failed to clear completed orders' });
    }
});

// Delete order
app.delete('/api/admin/orders/:id', authenticateAdmin, (req, res) => {
    try {
        const orders = readDataFile(ORDERS_FILE);
        const orderId = parseInt(req.params.id);
        const filteredOrders = orders.filter(o => o.id !== orderId);
        
        if (writeDataFile(ORDERS_FILE, filteredOrders)) {
            res.json({ success: true, message: 'Order deleted successfully' });
        } else {
            res.status(500).json({ error: 'Failed to delete order' });
        }
    } catch (error) {
        console.error('Error deleting order:', error);
        res.status(500).json({ error: 'Failed to delete order' });
    }
});

// Create new order (called from frontend when payment succeeds)
// Also handle /api/api/orders (frontend uses API_BASE_URL + '/api/orders' where base ends with /api)
const createOrderHandler = (req, res) => {
    try {
        const orders = readDataFile(ORDERS_FILE);
        const { items, total, customerInfo, paymentIntentId, chairNumber, deliveryOption, message } = req.body;
        
        const newId = Math.max(...orders.map(o => o.id), 0) + 1;
        const newOrder = {
            id: newId,
            items,
            total,
            customerInfo,
            paymentIntentId,
            chairNumber: chairNumber || 'Not specified',
            deliveryOption: deliveryOption || '',
            message: message || '',
            status: 'pending',
            createdAt: new Date().toISOString()
        };
        
        orders.push(newOrder);
        
        if (writeDataFile(ORDERS_FILE, orders)) {
            // Emit order notification to admin dashboard (in a real app, use WebSockets or Server-Sent Events)
            console.log('New order received:', newOrder);
            res.json({ success: true, orderId: newId, message: 'Order created successfully' });
        } else {
            res.status(500).json({ error: 'Failed to create order' });
        }
    } catch (error) {
        console.error('Error creating order:', error);
        res.status(500).json({ error: 'Failed to create order' });
    }
};
app.post('/api/orders', orderCreateLimiter, createOrderHandler);
app.post('/api/api/orders', orderCreateLimiter, createOrderHandler);

// Get products for frontend
app.get('/api/products', (req, res) => {
    try {
        const products = readDataFile(PRODUCTS_FILE);
        res.json(products);
    } catch (error) {
        console.error('Error fetching products:', error);
        res.status(500).json({ error: 'Failed to fetch products' });
    }
});

// Get discounts for frontend
app.get('/api/discounts', (req, res) => {
    try {
        const discounts = readDataFile(DISCOUNTS_FILE);
        res.json(discounts);
    } catch (error) {
        console.error('Error fetching discounts:', error);
        res.status(500).json({ error: 'Failed to fetch discounts' });
    }
});

// Validate discount code
app.post('/api/validate-discount', (req, res) => {
    try {
        const { code, orderTotal } = req.body;
        const discounts = readDataFile(DISCOUNTS_FILE);
        
        const discount = discounts.find(d => d.code === code);
        if (!discount) {
            return res.json({ valid: false, message: 'Invalid discount code' });
        }
        
        if (discount.maxUses && discount.usedCount >= discount.maxUses) {
            return res.json({ valid: false, message: 'Discount code has expired' });
        }
        
        if (discount.minOrderAmount && orderTotal < discount.minOrderAmount) {
            return res.json({ valid: false, message: `Minimum order amount of £${discount.minOrderAmount} required` });
        }
        
        let discountAmount = 0;
        if (discount.type === 'percentage') {
            discountAmount = (orderTotal * discount.value) / 100;
        } else {
            discountAmount = discount.value;
        }
        
        res.json({
            valid: true,
            discount: {
                id: discount.id,
                name: discount.name,
                type: discount.type,
                value: discount.value,
                amount: discountAmount
            }
        });
    } catch (error) {
        console.error('Error validating discount:', error);
        res.status(500).json({ error: 'Failed to validate discount' });
    }
});

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({ status: 'OK', timestamp: new Date().toISOString() });
});

// Static files (admin.html, PWA assets) — must be last so /api/* routes win over files on disk
app.use(express.static(__dirname));

app.listen(PORT, () => {
    console.log(`Stripe backend server running on port ${PORT}`);
    console.log(`Health check: http://localhost:${PORT}/health`);
    console.log(`Admin dashboard: http://localhost:${PORT}/admin.html`);
});

// Example usage:
// 1. Install dependencies: npm install express stripe cors
// 2. Replace 'sk_test_your_secret_key_here' with your actual Stripe secret key
// 3. Update the frontend to call: http://localhost:3001/create-payment-intent
// 4. Set up webhook endpoint in Stripe dashboard: http://localhost:3001/webhook
