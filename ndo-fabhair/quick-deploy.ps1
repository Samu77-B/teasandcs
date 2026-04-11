# Quick Deployment Script for Teas & C's PWA (PowerShell)
# Run this script to prepare your app for Hostinger deployment

Write-Host "🚀 Teas & C's PWA - Quick Deployment Setup" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""

# Check if Node.js is installed
try {
    $nodeVersion = node --version
    Write-Host "✅ Node.js version: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Node.js is not installed. Please install Node.js first." -ForegroundColor Red
    exit 1
}

# Create backup of original config
if (Test-Path "config.js") {
    Copy-Item "config.js" "config.js.backup"
    Write-Host "✅ Backed up original config.js" -ForegroundColor Green
}

# Create production directory structure
if (!(Test-Path "production-files")) {
    New-Item -ItemType Directory -Name "production-files" | Out-Null
}
Write-Host "✅ Created production-files directory" -ForegroundColor Green

# Copy essential files
$filesToCopy = @(
    "index.html",
    "manifest.json", 
    "sw.js",
    "admin.html",
    "orders-display.html",
    "footer-component.html",
    "stripe-backend.js",
    "package.json"
)

foreach ($file in $filesToCopy) {
    if (Test-Path $file) {
        Copy-Item $file "production-files\$file"
    }
}

# Copy directories
$dirsToCopy = @("images", "Brand")
foreach ($dir in $dirsToCopy) {
    if (Test-Path $dir) {
        Copy-Item $dir "production-files\$dir" -Recurse
    }
}

Write-Host "✅ Copied essential files to production-files/" -ForegroundColor Green

# Create production config template
$configContent = @"
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
"@

$configContent | Out-File -FilePath "production-files\config.js" -Encoding UTF8
Write-Host "✅ Created production config template" -ForegroundColor Green

# Create environment file template
$envContent = @"
STRIPE_SECRET_KEY=sk_live_YOUR_LIVE_SECRET_KEY
STRIPE_WEBHOOK_SECRET=whsec_YOUR_WEBHOOK_SECRET
PORT=3001
NODE_ENV=production
ADMIN_TOKEN=CHANGE_THIS_TO_SOMETHING_SECURE
"@

$envContent | Out-File -FilePath "production-files\.env" -Encoding UTF8
Write-Host "✅ Created environment file template" -ForegroundColor Green

# Create deployment instructions
$instructionsContent = @"
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
"@

$instructionsContent | Out-File -FilePath "production-files\DEPLOYMENT_INSTRUCTIONS.txt" -Encoding UTF8
Write-Host "✅ Created deployment instructions" -ForegroundColor Green

Write-Host ""
Write-Host "🎉 Setup Complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Your production files are ready in the 'production-files/' directory." -ForegroundColor Yellow
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Edit the files in production-files/ with your actual domain and keys" -ForegroundColor White
Write-Host "2. Upload to your Hostinger server" -ForegroundColor White
Write-Host "3. Follow the instructions in DEPLOYMENT_INSTRUCTIONS.txt" -ForegroundColor White
Write-Host ""
Write-Host "⚠️  IMPORTANT: Update your Stripe keys and domain before uploading!" -ForegroundColor Red
