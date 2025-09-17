#!/bin/bash

# Stock Screener Demo Launcher
echo "🚀 Starting Stock Screener Demo Server..."
echo "📊 Loading data from CSV files..."

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP to run this demo."
    echo "   On Ubuntu/Debian: sudo apt install php"
    echo "   On macOS: brew install php"
    echo "   On Windows: Download from https://www.php.net/downloads"
    exit 1
fi

# Check if data files exist
if [ ! -f "screener_data.csv" ]; then
    echo "❌ screener_data.csv not found in current directory"
    exit 1
fi

if [ ! -f "screener_list.csv" ]; then
    echo "❌ screener_list.csv not found in current directory"
    exit 1
fi

# Navigate to plugin directory
cd screener-dropdown-plugin

echo "✅ PHP found: $(php --version | head -n 1)"
echo "✅ Data files found"
echo ""
echo "🌐 Starting server on http://localhost:8000"
echo "📱 The application will open automatically in your browser"
echo ""
echo "🔧 Features available:"
echo "   • Dynamic metric loading from CSV"
echo "   • Real-time filtering"
echo "   • Professional UI with animations"
echo "   • CSV export functionality"
echo "   • Mobile responsive design"
echo ""
echo "⏹️  Press Ctrl+C to stop the server"
echo ""

# Start the PHP development server
php -S localhost:8000 server.php &
SERVER_PID=$!

# Wait a moment for server to start
sleep 2

# Try to open in browser (works on most systems)
if command -v xdg-open &> /dev/null; then
    xdg-open http://localhost:8000
elif command -v open &> /dev/null; then
    open http://localhost:8000
elif command -v start &> /dev/null; then
    start http://localhost:8000
else
    echo "🌐 Please open http://localhost:8000 in your browser"
fi

# Wait for server process
wait $SERVER_PID
