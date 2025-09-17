@echo off
echo 🚀 Starting Stock Screener Demo Server...
echo 📊 Loading data from CSV files...

REM Check if PHP is installed
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP is not installed. Please install PHP to run this demo.
    echo    Download from https://www.php.net/downloads
    pause
    exit /b 1
)

REM Check if data files exist
if not exist "screener_data.csv" (
    echo ❌ screener_data.csv not found in current directory
    pause
    exit /b 1
)

if not exist "screener_list.csv" (
    echo ❌ screener_list.csv not found in current directory
    pause
    exit /b 1
)

REM Navigate to plugin directory
cd screener-dropdown-plugin

echo ✅ PHP found
echo ✅ Data files found
echo.
echo 🌐 Starting server on http://localhost:8000
echo 📱 The application will open automatically in your browser
echo.
echo 🔧 Features available:
echo    • Dynamic metric loading from CSV
echo    • Real-time filtering
echo    • Professional UI with animations
echo    • CSV export functionality
echo    • Mobile responsive design
echo.
echo ⏹️  Press Ctrl+C to stop the server
echo.

REM Start the PHP development server
start /B php -S localhost:8000 server.php

REM Wait a moment for server to start
timeout /t 2 /nobreak >nul

REM Open in browser
start http://localhost:8000

REM Keep the window open
echo Server is running. Close this window to stop the server.
pause
