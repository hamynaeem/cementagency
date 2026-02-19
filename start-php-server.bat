@echo off
echo ========================================
echo   🔧 PHP Backend Quick Fix & Restart  
echo ========================================  
echo.

REM Kill existing PHP processes on port 8000
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8000') do (
    echo Stopping process %%a on port 8000...
    taskkill /f /pid %%a >nul 2>&1
)

echo.
echo 🚀 Starting PHP development server...
cd /d "%~dp0apps\CementAgency\Apis"

echo 📍 Server starting at: http://localhost:8000
echo 📁 Document root: %cd%
echo.
echo 🔗 Test URLs:
echo   - Basic test: http://localhost:8000/index.php/apis/test  
echo   - Business: http://localhost:8000/index.php/apis/business/1
echo   - Vouchers: http://localhost:8000/index.php/apis/qryvouchers
echo   - Bookings: http://localhost:8000/index.php/apis/qrybooking
echo   - Expenses: http://localhost:8000/index.php/apis/qryexpense
echo.
echo ✅ Server is running! Keep this window open.
echo 💡 Press Ctrl+C to stop the server
echo.

php -S localhost:8000