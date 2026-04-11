#!/bin/bash

# Quick Deployment Script for Teas & C's PWA
# Run this script to prepare your app for Hostinger deployment

echo "🚀 Teas & C's PWA - Quick Deployment Setup"
echo "=========================================="
echo ""

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js first."
    exit 1
fi

echo "✅ Node.js version: $(node --version)"

# Create backup of original config
if [ -f "config.js" ]; then
    cp config.js config.js.backup
    echo "✅ Backed up original config.js"
fi

# Create production directory structure
mkdir -p production-files
echo "✅ Created production-files directory"

# Copy essential files
cp index.html production-files/
cp manifest.json production-files/
cp sw.js production-files/
cp admin.html production-files/
cp footer-component.html production-files/
cp stripe-backend.js production-files/
cp package.json production-files/

# Copy assets
cp -r images production-files/
cp -r Brand production-files/

echo "✅ Copied essential files to production-files/"

# Create production config template
cat > production-files/config.js << 'EOF'
// Production Configuration for Teas & C's PWA
const CONFIG = {
    API_BASE_URL: 'https://YOURDOMAIN.com/api', // REPLACE WITH YOUR DOMAIN
    STRIPE_PUBLISHABLE_KEY: 'pk_live_YOUR_LIVE_KEY', // REPLACE WITH YOUR LIVE KEY
    ADMIN_TOKEN: 'CHANGE_THIS_TO_SOMETHING_SECURE', // CHANGE THIS!
    ENVIRONMENT: 'production'
};

if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    CONFIG.ENVIRONMENT = 'production';
    CONFIG.API_BASE_URL = 'https://YOURDOMAIN.com/api'; // REPLACE WITH YOUR DOMAIN
}

window.CONFIG = CONFIG;
EOF

echo "✅ Created production config template"

# Create environment file template
cat > production-files/.env << 'EOF'
STRIPE_SECRET_KEY=sk_live_YOUR_LIVE_SECRET_KEY
STRIPE_WEBHOOK_SECRET=whsec_YOUR_WEBHOOK_SECRET
PORT=3001
NODE_ENV=production
ADMIN_TOKEN=CHANGE_THIS_TO_SOMETHING_SECURE
EOF

echo "✅ Created environment file template"

# Create deployment instructions
cat > production-files/DEPLOYMENT_INSTRUCTIONS.txt << 'EOF'
Teas & C's PWA - Deployment Instructions
========================================

BEFORE UPLOADING:
1. Edit config.js and replace:
   - YOURDOMAIN.com with your actual domain
   - pk_live_YOUR_LIVE_KEY with your Stripe live publishable key
   - CHANGE_THIS_TO_SOMETHING_SECURE with a secure admin token

2. Edit .env and replace:
   - sk_live_YOUR_LIVE_SECRET_KEY with your Stripe live secret key
   - whsec_YOUR_WEBHOOK_SECRET with your Stripe webhook secret
   - CHANGE_THIS_TO_SOMETHING_SECURE with the same admin token

UPLOAD TO HOSTINGER:
1. Upload all files in this directory to your public_html folder
2. Create an 'api' subdirectory and move backend files there:
   - stripe-backend.js
   - package.json
   - .env

3. SSH into your server and run:
   cd public_html/api
   npm install
   node stripe-backend.js

4. Set up PM2 for production:
   npm install -g pm2
   pm2 start stripe-backend.js --name teas-cs-backend
   pm2 save
   pm2 startup

5. Configure Stripe webhooks:
   - URL: https://yourdomain.com/api/webhook
   - Events: payment_intent.succeeded, payment_intent.payment_failed

TEST YOUR DEPLOYMENT:
- Visit https://yourdomain.com
- Test the ordering flow
- Test admin dashboard at https://yourdomain.com/admin.html
- Verify payments work with test cards

NEED HELP?
- Check the HOSTINGER_DEPLOYMENT_GUIDE.md for detailed instructions
- Test with Stripe test keys first before going live
EOF

echo "✅ Created deployment instructions"

echo ""
echo "🎉 Setup Complete!"
echo ""
echo "Your production files are ready in the 'production-files/' directory."
echo ""
echo "Next steps:"
echo "1. Edit the files in production-files/ with your actual domain and keys"
echo "2. Upload to your Hostinger server"
echo "3. Follow the instructions in DEPLOYMENT_INSTRUCTIONS.txt"
echo ""
echo "⚠️  IMPORTANT: Update your Stripe keys and domain before uploading!"
