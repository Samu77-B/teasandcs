#!/usr/bin/env node

/**
 * Deployment script for Teas & C's Backend
 * This script helps prepare the backend for production deployment
 */

const fs = require('fs');
const path = require('path');

console.log('🚀 Preparing Teas & C\'s Backend for Production Deployment...\n');

// Create production-ready package.json
const packageJson = {
    "name": "teas-cs-backend",
    "version": "1.0.0",
    "description": "Production backend for Teas & C's PWA",
    "main": "stripe-backend.js",
    "scripts": {
        "start": "node stripe-backend.js",
        "dev": "nodemon stripe-backend.js",
        "pm2": "pm2 start stripe-backend.js --name teas-cs-backend",
        "pm2:stop": "pm2 stop teas-cs-backend",
        "pm2:restart": "pm2 restart teas-cs-backend",
        "pm2:logs": "pm2 logs teas-cs-backend"
    },
    "dependencies": {
        "express": "^4.18.2",
        "stripe": "^14.0.0",
        "cors": "^2.8.5",
        "compression": "^1.7.4",
        "helmet": "^7.0.0",
        "dotenv": "^16.3.1"
    },
    "engines": {
        "node": ">=18.0.0"
    },
    "keywords": ["stripe", "payment", "pwa", "teas", "coffee", "ordering"],
    "author": "Teas & C's",
    "license": "MIT"
};

// Create logs directory
const logsDir = path.join(__dirname, 'logs');
if (!fs.existsSync(logsDir)) {
    fs.mkdirSync(logsDir, { recursive: true });
    console.log('✅ Created logs directory');
}

// Write production package.json
fs.writeFileSync('package-production.json', JSON.stringify(packageJson, null, 2));
console.log('✅ Created production package.json');

// Create PM2 ecosystem file
const pm2Config = {
    apps: [{
        name: 'teas-cs-backend',
        script: 'stripe-backend.js',
        instances: 1,
        exec_mode: 'fork',
        env: {
            NODE_ENV: 'production',
            PORT: 3001
        },
        error_file: './logs/err.log',
        out_file: './logs/out.log',
        log_file: './logs/combined.log',
        time: true,
        autorestart: true,
        max_restarts: 10,
        min_uptime: '10s'
    }]
};

fs.writeFileSync('ecosystem.config.js', `module.exports = ${JSON.stringify(pm2Config, null, 2)};`);
console.log('✅ Created PM2 ecosystem configuration');

// Create deployment checklist
const checklist = `# Teas & C's Backend Deployment Checklist

## Pre-Deployment
- [ ] Update .env file with production Stripe keys
- [ ] Update CORS origins in stripe-backend.js
- [ ] Test locally with production keys
- [ ] Backup existing data

## Deployment Steps
1. Upload files to Hostinger:
   - stripe-backend.js
   - package-production.json (rename to package.json)
   - .env
   - ecosystem.config.js

2. SSH into your server:
   \`\`\`bash
   cd /domains/yourdomain.com/public_html/api
   npm install
   \`\`\`

3. Install PM2:
   \`\`\`bash
   npm install -g pm2
   \`\`\`

4. Start the application:
   \`\`\`bash
   pm2 start ecosystem.config.js
   pm2 save
   pm2 startup
   \`\`\`

5. Verify deployment:
   \`\`\`bash
   pm2 status
   pm2 logs teas-cs-backend
   curl https://yourdomain.com/api/health
   \`\`\`

## Post-Deployment
- [ ] Test payment processing
- [ ] Test admin dashboard
- [ ] Monitor logs for errors
- [ ] Set up monitoring alerts
- [ ] Configure automated backups

## Troubleshooting
- Check PM2 status: \`pm2 status\`
- View logs: \`pm2 logs teas-cs-backend\`
- Restart app: \`pm2 restart teas-cs-backend\`
- Check server resources: \`pm2 monit\`
`;

fs.writeFileSync('DEPLOYMENT_CHECKLIST.md', checklist);
console.log('✅ Created deployment checklist');

console.log('\n🎉 Backend preparation complete!');
console.log('\nNext steps:');
console.log('1. Update .env file with your production Stripe keys');
console.log('2. Update CORS origins in stripe-backend.js');
console.log('3. Upload files to your Hostinger server');
console.log('4. Follow the deployment checklist');
console.log('\nFiles created:');
console.log('- package-production.json');
console.log('- ecosystem.config.js');
console.log('- DEPLOYMENT_CHECKLIST.md');
console.log('- logs/ directory');
