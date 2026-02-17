@echo off
echo Installing sp_ManageCashbook stored procedure...
echo.

REM Configuration - Update these values for your database
set DB_HOST=localhost
set DB_USER=root
set DB_NAME=db_cement
set SQL_FILE=sp_ManageCashbook.sql

echo Connecting to MySQL database: %DB_NAME%
echo Host: %DB_HOST%
echo User: %DB_USER%
echo.

REM Execute the SQL file
mysql -h %DB_HOST% -u %DB_USER% -p %DB_NAME% < %SQL_FILE%

if %ERRORLEVEL% equ 0 (
    echo.
    echo ✅ SUCCESS: sp_ManageCashbook stored procedure installed successfully!
    echo.
    echo Your journal voucher save functionality should now work.
    echo You can test it by going to the journal voucher form and clicking Save.
    echo.
) else (
    echo.
    echo ❌ ERROR: Failed to install stored procedure.
    echo Please check:
    echo - Database connection details are correct
    echo - MySQL is running
    echo - User has sufficient permissions
    echo - Database name 'db_cement' exists
    echo.
)

pause