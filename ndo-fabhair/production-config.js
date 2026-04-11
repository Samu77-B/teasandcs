// Production Configuration for Teas & C's PWA
// Copy this file and rename to config.js for production deployment

const CONFIG = {
    // Backend API URL - Update this to your Hostinger domain
    API_BASE_URL: 'https://yourdomain.com/api', // Replace 'yourdomain.com' with your actual domain
    
    // Stripe Configuration - Use your LIVE keys for production
    STRIPE_PUBLISHABLE_KEY: 'pk_live_your_live_publishable_key_here', // Get from Stripe Dashboard
    
    // Admin Configuration - CHANGE THIS TO SOMETHING SECURE
    ADMIN_TOKEN: 'your-secure-admin-token-2024', // Generate a strong random token
    
    // Environment
    ENVIRONMENT: 'production'
};

// Auto-detect environment and update URLs
if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    // Production environment
    CONFIG.ENVIRONMENT = 'production';
    // Update this to your actual domain
    CONFIG.API_BASE_URL = 'https://yourdomain.com/api'; // Replace with your actual domain
}

// Make config available globally
window.CONFIG = CONFIG;
