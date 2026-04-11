@echo off
echo 🚀 Auto-Deploy to GitHub Pages
echo ================================

echo.
echo 📁 Checking for changes...
git add .

echo.
echo 📝 Committing changes...
git commit -m "Auto-update: %date% %time%"

echo.
echo 🚀 Pushing to GitHub...
git push origin main

echo.
echo ✅ Deployment triggered!
echo 🌐 Your site will update in 2-5 minutes at:
echo    https://samu77-b.github.io/teas-cs-pwa/
echo.
echo 💡 You can also manually trigger deployment by:
echo    1. Going to your GitHub repository
echo    2. Clicking "Actions" tab
echo    3. Clicking "Run workflow" on the latest deployment
echo.
pause
