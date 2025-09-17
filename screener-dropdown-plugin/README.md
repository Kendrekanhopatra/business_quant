
# Screener Dropdown WordPress Plugin

A powerful WordPress plugin for stock screening with dropdown filters using Select2 and DataTables libraries.

## Features

- **Dynamic Filtering**: Add unlimited filter criteria with the ability to delete filters in any sequence
- **Select2 Integration**: Beautiful dropdown interface for metric selection
- **DataTables Integration**: Professional data table with sorting, pagination, and search
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **CSV Export**: Export filtered results to CSV format
- **AJAX-Powered**: Fast, real-time filtering without page reloads
- **Admin Interface**: Easy-to-use admin panel for data management

## Installation

1. Upload the `screener-dropdown-plugin` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create database tables and import CSV data
4. Use the shortcode `[screener-dropdown]` on any page or post

## Usage

### Basic Shortcode
```
[screener-dropdown]
```

### Shortcode with Parameters
```
[screener-dropdown limit="500"]
```

### Available Parameters
- `limit`: Maximum number of results to return (default: 100)

## Data Structure

The plugin uses two main data sources:

### screener_list.csv
Contains the available metrics for filtering:
- `metric`: Name of the financial metric
- `datatype`: Data type (int, string, %, date)
- `statement`: Financial statement category

### screener_data.csv
Contains the actual company data with financial metrics for all companies.

## Filter Operators

The plugin supports different operators based on data type:

### For Numeric Data (int, %)
- Equals
- Greater than
- Less than
- Greater than or equal
- Less than or equal

### For Text Data (string)
- Equals
- Contains

### For Date Data
- Equals
- After (greater than)
- Before (less than)

## Admin Panel

Access the admin panel at **Settings > Screener Dropdown** to:
- View plugin statistics
- Reimport CSV data
- Check system information
- View troubleshooting information

## File Structure

```
screener-dropdown-plugin/
├── screener-dropdown.php          # Main plugin file
├── README.md                      # This file
├── data/                          # CSV data files
│   ├── screener_list.csv         # Metrics definitions
│   └── screener_data.csv         # Company data
├── assets/                        # Plugin assets
│   ├── css/
│   │   └── screener-dropdown.css # Plugin styles
│   └── js/
│       └── screener-dropdown.js  # Plugin JavaScript
├── templates/                     # Template files
│   └── screener-dropdown.php     # Main template
└── admin/                         # Admin interface
    └── admin-page.php            # Admin panel
```

## Database Tables

The plugin creates two database tables:

### wp_screener_list
Stores the available metrics for filtering.

### wp_screener_data
Stores the actual company data with dynamic columns based on CSV structure.

## Customization

### Styling
Modify `assets/css/screener-dropdown.css` to customize the appearance.

### JavaScript
Extend `assets/js/screener-dropdown.js` to add custom functionality.

### Template
Customize `templates/screener-dropdown.php` to modify the HTML structure.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- Modern web browser with JavaScript enabled

## Dependencies

The plugin automatically loads these external libraries:
- jQuery (included with WordPress)
- Select2 4.1.0 (from CDN)
- DataTables 1.13.6 (from CDN)

## Browser Support

- Chrome 60+
- Firefox 60+
- Safari 12+
- Edge 79+
- Internet Explorer 11+ (limited support)

## Performance Considerations

- Large datasets may require server optimization
- Consider using caching plugins for better performance
- Adjust the `limit` parameter based on your server capabilities

## Installation

# Screener Dropdown Plugin - Installation & Setup Guide

## Overview

This WordPress plugin provides a powerful stock screening interface with dropdown filters, similar to the BusinessQuant.com screener. It includes:

- **Advanced Filtering**: Multiple filter criteria with various operators
- **Professional UI**: Select2 dropdowns and DataTables integration
- **Responsive Design**: Works on all devices
- **CSV Export**: Export filtered results
- **Real-time Filtering**: AJAX-powered for fast results

## Quick Start

### Option 1: WordPress Plugin Installation

1. **Upload the Plugin**
   ```bash
   # Copy the plugin to your WordPress plugins directory
   cp -r screener-dropdown-plugin /path/to/wordpress/wp-content/plugins/
   ```

2. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Screener Dropdown" and click "Activate"
   - The plugin will automatically create database tables and import CSV data

3. **Add to a Page**
   - Create a new page or edit an existing one
   - Add the shortcode: `[screener-dropdown]`
   - Publish the page

### Option 2: Standalone HTML (Demo)

For testing without WordPress:

1. **Open Demo Files**
   - `demo.html` - Basic functionality demo
   - `test-page.html` - Enhanced UI with Business Quant styling

2. **View in Browser**
   ```bash
   # Open in your browser
   firefox screener-dropdown-plugin/test-page.html
   # or
   google-chrome screener-dropdown-plugin/test-page.html
   ```

## Automated WordPress Setup

Use the provided setup script for a complete local WordPress installation:

```bash
# Make the script executable
chmod +x setup-wordpress.sh

# Run as root (will install Apache, MySQL, PHP, WordPress)
sudo ./setup-wordpress.sh
```

This script will:
- Install required packages (Apache, MySQL, PHP)
- Download and configure WordPress
- Install and activate the plugin
- Create a test page with the screener

## Manual WordPress Setup

### Prerequisites

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+
- Web server (Apache/Nginx)

### Step-by-Step Installation

1. **Prepare WordPress Environment**
   ```bash
   # Install required packages
   sudo apt update
   sudo apt install apache2 mysql-server php php-mysql php-curl php-gd php-mbstring php-xml php-zip
   
   # Start services
   sudo systemctl start apache2 mysql
   sudo systemctl enable apache2 mysql
   ```

2. **Download WordPress**
   ```bash
   cd /tmp
   wget https://wordpress.org/latest.tar.gz
   tar -xzf latest.tar.gz
   sudo cp -r wordpress/* /var/www/html/
   sudo chown -R www-data:www-data /var/www/html/
   ```

3. **Configure Database**
   ```bash
   sudo mysql -e "CREATE DATABASE wordpress_screener;"
   sudo mysql -e "CREATE USER 'wp_user'@'localhost' IDENTIFIED BY 'wp_password';"
   sudo mysql -e "GRANT ALL PRIVILEGES ON wordpress_screener.* TO 'wp_user'@'localhost';"
   sudo mysql -e "FLUSH PRIVILEGES;"
   ```

4. **Install Plugin**
   ```bash
   # Copy plugin to WordPress
   sudo cp -r screener-dropdown-plugin /var/www/html/wp-content/plugins/
   sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/screener-dropdown-plugin
   ```

5. **Activate and Configure**
   - Complete WordPress installation at `http://localhost`
   - Go to Plugins → Activate "Screener Dropdown"
   - Go to Settings → Screener Dropdown to verify data import

## Plugin Features

### Available Filters

The plugin includes these key financial metrics:

#### Valuation Metrics
- Market Capitalization
- Price to Earnings [P/E]
- Price to Book Value [P/B]
- Price to Sales [P/S]
- Price to Free Cash Flow [P/FCF]
- EV to EBITDA
- EV to Revenue
- Enterprise Value (EV)

#### Profitability Metrics
- Gross Margin % (Annual)
- EBITDA Margin % (Annual)
- Net Profit Margin % (Annual)
- ROE - Return on Equity (%) (Annual)
- ROA - Return on Assets (%) (Annual)
- ROCE - Return on Capital Employed (%) (Annual)

#### Financial Strength
- Current Ratio (Annual)
- Debt to Equity Ratio (Annual)
- Cash Ratio (Annual)
- Interest Cover Ratio (Annual)

#### Growth Metrics
- Revenue Growth (1y) % (Annual)
- Net Income Growth (1y) % (Annual)
- EPS Growth (1y) % (Annual)
- EBITDA Growth (1y) % (Annual)

#### Dividend Metrics
- Dividend Yield %
- Dividend Payout Ratio % (Annual)
- Dividend per Basic Share (Annual)

### Filter Operators

- **Equals**: Exact match
- **Greater than**: Value > input
- **Less than**: Value < input
- **Greater than or equal**: Value >= input
- **Less than or equal**: Value <= input
- **Contains**: Text contains input (for string fields)

### Usage Examples

1. **Find High-Growth Companies**
   - Revenue Growth (1y) % > 20
   - Net Income Growth (1y) % > 15
   - Market Cap > 1000000000

2. **Value Investing Screen**
   - P/E Ratio < 15
   - P/B Ratio < 2
   - ROE > 15%

3. **Dividend Stocks**
   - Dividend Yield % > 3
   - Dividend Payout Ratio % < 60
   - Current Ratio > 1.5

## Customization

### Adding Custom Metrics

1. **Update CSV Files**
   - Add new metrics to `data/screener_list.csv`
   - Include corresponding data in `data/screener_data.csv`

2. **Update Column Mapping**
   - Edit the `map_metric_to_column()` function in `screener-dropdown.php`
   - Add your new metric mappings

3. **Update Templates**
   - Add new options to the select dropdown in `templates/screener-dropdown.php`

### Styling Customization

Edit `assets/css/screener-dropdown.css` to customize:
- Colors and themes
- Layout and spacing
- Button styles
- Table appearance

### JavaScript Customization

Modify `assets/js/screener-dropdown.js` to:
- Add custom validation
- Implement new filter types
- Enhance user interactions
- Add custom export formats

## Troubleshooting

### Common Issues

1. **Plugin Not Loading**
   - Check file permissions: `sudo chown -R www-data:www-data /path/to/plugin`
   - Verify WordPress version compatibility

2. **No Data Showing**
   - Go to Settings → Screener Dropdown → Reimport CSV Data
   - Check CSV file format and encoding

3. **Filters Not Working**
   - Check browser console for JavaScript errors
   - Verify AJAX endpoints are accessible
   - Ensure jQuery is loaded

4. **Performance Issues**
   - Reduce the limit parameter in shortcode: `[screener-dropdown limit="100"]`
   - Optimize database queries
   - Consider adding database indexes

### Debug Mode

Enable WordPress debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs in `/wp-content/debug.log`

## Support

### File Structure
```
screener-dropdown-plugin/
├── screener-dropdown.php          # Main plugin file
├── README.md                      # Documentation
├── data/                          # CSV data files
├── assets/css/                    # Stylesheets
├── assets/js/                     # JavaScript files
├── templates/                     # HTML templates
├── admin/                         # Admin interface
├── demo.html                      # Basic demo
└── test-page.html                # Enhanced demo
```

### Key URLs
- **Plugin Admin**: `/wp-admin/options-general.php?page=screener-dropdown`
- **Demo Page**: `screener-dropdown-plugin/test-page.html`
- **Documentation**: `README.md`

### Database Tables
- `wp_screener_list`: Available metrics
- `wp_screener_data`: Company financial data

## Next Steps

1. **Test the Plugin**: Use the demo files to verify functionality
2. **Customize Filters**: Add your specific financial metrics
3. **Style Integration**: Match your website's design
4. **Performance Optimization**: Tune for your data size
5. **User Training**: Create guides for end users

For additional support, refer to the plugin's admin interface and built-in documentation.


## Troubleshooting

### Common Issues

1. **No data showing**
   - Check if CSV files are in the correct location
   - Reimport data from the admin panel

2. **Filters not working**
   - Ensure JavaScript is enabled
   - Check browser console for errors
   - Verify AJAX requests are working

3. **Slow performance**
   - Reduce the limit parameter
   - Optimize database queries
   - Consider server-side pagination

### Debug Mode

To enable debug mode, add this to your wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Security

The plugin includes several security measures:
- Nonce verification for AJAX requests
- Data sanitization and validation
- SQL injection prevention
- XSS protection

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support and questions, please refer to the plugin documentation or contact the development team.

## Changelog

### Version 1.0.0
- Initial release
- Basic filtering functionality
- Select2 and DataTables integration
- CSV export feature
- Admin interface
- Responsive design

## Credits

- Built for Business Quant
- Uses Select2 library by Kevin Brown
- Uses DataTables library by SpryMedia Ltd
- Developed with WordPress best practices
