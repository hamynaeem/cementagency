@echo off
echo ================================
echo   Cement Agency Database Setup
echo ================================
echo.
echo This script will help you set up the database for your Cement Agency application.
echo.
echo Choose an option:
echo 1. Quick Setup - SQLite database (Recommended)
echo 2. Test MySQL connection and create database  
echo 3. Start XAMPP (if installed)
echo 4. Debug backend API endpoints
echo 5. Check current database status
echo.
set /p choice="Enter your choice (1-5): "

if "%choice%"=="1" (
    echo.
    echo 🚀 Setting up SQLite database (no dependencies required)...
    start http://localhost:8000/setup_sqlite.php
    echo.
    echo ✅ After setup completes:
    echo  1. Reload your Angular app browser tab
    echo  2. The Day Book should now show sample data
    goto end
)

if "%choice%"=="2" (
    echo.
    echo Opening MySQL setup test...
    start http://localhost:8000/test_db_connection.php
    goto end
)

if "%choice%"=="3" (
    echo.
    echo Attempting to start XAMPP...
    if exist "C:\xampp\xampp-control.exe" (
        start "" "C:\xampp\xampp-control.exe"
        echo XAMPP Control Panel opened. Please start Apache and MySQL services.
    ) else (
        echo XAMPP not found at C:\xampp\
        echo Please install XAMPP or start your MySQL service manually.
    )
    goto end
)

if "%choice%"=="4" (
    echo.
    echo Opening backend debug tool...
    start http://localhost:8000/debug.php
    goto end
)

if "%choice%"=="5" (
    echo.
    echo Checking API status...
    start http://localhost:8000/index.php/apis/test
    goto end
)

echo Invalid choice. Please run the script again.

:end
echo.
echo 💡 Troubleshooting Tips:
echo  - Make sure PHP server is running: php -S localhost:8000 in APIs folder
echo  - Angular proxy should point to localhost:8000 in cement.proxy.conf.json
echo  - Start Angular with: npm run cement
echo.
pause