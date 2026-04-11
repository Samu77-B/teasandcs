Write-Host "🚀 Auto-Deploy to GitHub Pages" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green

Write-Host ""
Write-Host "📁 Checking for changes..." -ForegroundColor Yellow
git add .

Write-Host ""
Write-Host "📝 Committing changes..." -ForegroundColor Yellow
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
git commit -m "Auto-update: $timestamp"

Write-Host ""
Write-Host "🚀 Pushing to GitHub..." -ForegroundColor Yellow
git push origin main

Write-Host ""
Write-Host "✅ Deployment triggered!" -ForegroundColor Green
Write-Host "🌐 Your site will update in 2-5 minutes at:" -ForegroundColor Cyan
Write-Host "   https://samu77-b.github.io/teas-cs-pwa/" -ForegroundColor White

Write-Host ""
Write-Host "💡 You can also manually trigger deployment by:" -ForegroundColor Gray
Write-Host "   1. Going to your GitHub repository" -ForegroundColor Gray
Write-Host "   2. Clicking 'Actions' tab" -ForegroundColor Gray
Write-Host "   3. Clicking 'Run workflow' on the latest deployment" -ForegroundColor Gray

Write-Host ""
Read-Host "Press Enter to continue..."
