// Configuration for production
// Security: STRIPE_PUBLISHABLE_KEY is safe to expose. ADMIN_TOKEN is visible in DevTools on any
// browser that loads admin/barista pages — use a strong random value, rotate if leaked, and
// keep Railway ADMIN_TOKEN in sync. Do not commit real secrets to a public GitHub repo.
// IMPORTANT: API_BASE_URL must be your Railway Node API root, ending in /api
// (e.g. https://YOUR-SERVICE.up.railway.app/api).
// If you point this at https://teasandcs.com/.../api (Hostinger PHP), admin will get raw "<?php ..." and JSON.parse will fail.
// Backend API runs on Railway (see API_BASE_URL). Static PWA may be served from Railway and/or your domain.
// Production path example: https://teasandcs.com/ndo-fabhair/
// Override with config.local.js for Stripe keys and admin token (not in Git)
// Use window.CONFIG to avoid "already declared" when fallback runs
window.CONFIG = {
    // Backend API URL (Railway)
    API_BASE_URL: 'https://fabordering-production.up.railway.app/api',
    
    // Stripe Configuration
    // Get your keys from: https://dashboard.stripe.com/apikeys
    STRIPE_PUBLISHABLE_KEY: 'pk_live_51S2tRVQktyhqMRw4r7SkPtdmDUB3Mw4IzdZ8maYJBZXlf29UilrMP02CfH9k2C4pcJaA13KHysKfPUbac2qyrQg500RS2wGYUU',
    
    // Admin Configuration
    // Create a secure token for admin access
    ADMIN_TOKEN: 'harvest-bean-gauge-x7K9mP2q',
    
    // Environment
    ENVIRONMENT: 'production'
};
 