#!/bin/bash

# Deployment script for T&C PWA
echo "🚀 Preparing T&C PWA for deployment..."

# Check if config.js exists
if [ ! -f "config.js" ]; then
    echo "❌ config.js not found. Please create it first."
    exit 1
fi

# Update config.js for production
echo "📝 Updating configuration for production..."

# Get Railway URL from user
read -p "Enter your Railway backend URL (e.g., https://your-app.railway.app): " RAILWAY_URL

# Update config.js
sed -i.bak "s|http://localhost:3001|$RAILWAY_URL|g" config.js
sed -i.bak "s|development|production|g" config.js

echo "✅ Configuration updated!"
echo "📋 Next steps:"
echo "1. Update your Stripe keys in config.js"
echo "2. Deploy to Railway: https://railway.app"
echo "3. Deploy to Netlify: https://netlify.com"
echo "4. Test your deployment!"

echo "🎉 Ready for deployment!"
