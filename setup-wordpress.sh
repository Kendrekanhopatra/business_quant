#!/bin/bash

# WordPress Setup Script for Screener Dropdown Plugin Testing
# This script sets up a local WordPress environment for testing the plugin

set -e

echo "=== WordPress Setup for Screener Dropdown Plugin ==="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "Please run this script as root (use sudo)"
    exit 1
fi

# Variables
WP_DIR="/var/www/html/wordpress"
DB_NAME="wordpress_screener"
DB_USER="wp_user"
DB_PASS="wp_password"
WP_ADMIN_USER="admin"
WP_ADMIN_PASS="admin123"
WP_ADMIN_EMAIL="admin@localhost.com"

echo "Installing required packages..."
apt update
apt install -y apache2 mysql-server php php-mysql php-curl php-gd php-mbstring php-xml php-zip wget unzip

echo "Starting services..."
systemctl start apache2
systemctl start mysql
systemctl enable apache2
systemctl enable mysql

echo "Configuring MySQL..."
# Set up MySQL database
mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "Downloading WordPress..."
cd /tmp
wget https://wordpress.org/latest.tar.gz
tar -xzf latest.tar.gz

echo "Setting up WordPress..."
mkdir -p $WP_DIR
cp -r wordpress/* $WP_DIR/
chown -R www-data:www-data $WP_DIR
chmod -R 755 $WP_DIR

echo "Configuring WordPress..."
cd $WP_DIR
cp wp-config-sample.php wp-config.php

# Update wp-config.php with database settings
sed -i "s/database_name_here/$DB_NAME/" wp-config.php
sed -i "s/username_here/$DB_USER/" wp-config.php
sed -i "s/password_here/$DB_PASS/" wp-config.php

# Generate WordPress salts
SALTS=$(curl -s https://api.wordpress.org/secret-key/1.1/salt/)
sed -i '/AUTH_KEY/,/NONCE_SALT/d' wp-config.php
echo "$SALTS" >> wp-config.php
echo "" >> wp-config.php
echo "/* That's all, stop editing! Happy publishing. */" >> wp-config.php
echo "" >> wp-config.php
echo "/** Absolute path to the WordPress directory. */" >> wp-config.php
echo "if ( ! defined( 'ABSPATH' ) ) {" >> wp-config.php
echo "    define( 'ABSPATH', __DIR__ . '/' );" >> wp-config.php
echo "}" >> wp-config.php
echo "" >> wp-config.php
echo "/** Sets up WordPress vars and included files. */" >> wp-config.php
echo "require_once ABSPATH . 'wp-settings.php';" >> wp-config.php

echo "Configuring Apache..."
cat > /etc/apache2/sites-available/wordpress.conf << EOF
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot $WP_DIR
    
    <Directory $WP_DIR>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/wordpress_error.log
    CustomLog \${APACHE_LOG_DIR}/wordpress_access.log combined
</VirtualHost>
EOF

# Enable the site and required modules
a2ensite wordpress.conf
a2enmod rewrite
a2dissite 000-default.conf
systemctl reload apache2

echo "Installing WordPress via WP-CLI..."
cd /tmp
wget https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp

cd $WP_DIR
sudo -u www-data wp core install \
    --url="http://localhost" \
    --title="WordPress Screener Test Site" \
    --admin_user="$WP_ADMIN_USER" \
    --admin_password="$WP_ADMIN_PASS" \
    --admin_email="$WP_ADMIN_EMAIL"

echo "Installing Screener Dropdown Plugin..."
PLUGIN_SOURCE="/home/rex/mist/projects/quant/SampleDataset_Screener_BusinessQuant/screener-dropdown-plugin"
PLUGIN_DEST="$WP_DIR/wp-content/plugins/screener-dropdown-plugin"

if [ -d "$PLUGIN_SOURCE" ]; then
    cp -r "$PLUGIN_SOURCE" "$PLUGIN_DEST"
    chown -R www-data:www-data "$PLUGIN_DEST"
    
    # Activate the plugin
    sudo -u www-data wp plugin activate screener-dropdown-plugin
    echo "Plugin installed and activated!"
else
    echo "Warning: Plugin source directory not found at $PLUGIN_SOURCE"
fi

echo "Creating a test page with the shortcode..."
sudo -u www-data wp post create \
    --post_type=page \
    --post_title="Stock Screener" \
    --post_content="[screener-dropdown]" \
    --post_status=publish

echo ""
echo "=== Setup Complete! ==="
echo ""
echo "WordPress is now installed and configured:"
echo "  URL: http://localhost"
echo "  Admin URL: http://localhost/wp-admin"
echo "  Username: $WP_ADMIN_USER"
echo "  Password: $WP_ADMIN_PASS"
echo ""
echo "The Screener Dropdown plugin has been installed and activated."
echo "A test page with the [screener-dropdown] shortcode has been created."
echo ""
echo "To access the plugin admin page:"
echo "  Go to: http://localhost/wp-admin/options-general.php?page=screener-dropdown"
echo ""
echo "Database Information:"
echo "  Database: $DB_NAME"
echo "  Username: $DB_USER"
echo "  Password: $DB_PASS"
echo ""
echo "Note: The plugin will automatically import CSV data on first activation."
echo "This may take a few minutes depending on the size of your data files."
echo ""
