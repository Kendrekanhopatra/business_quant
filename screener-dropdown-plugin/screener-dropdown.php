<?php
/**
 * Plugin Name: Screener Dropdown
 * Plugin URI: 
 * Description: A WordPress plugin for stock screening with dropdown filters using Select2 and DataTables
 * Version: 1.0.0
 * Author: Business Quant
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SCREENER_DROPDOWN_VERSION', '1.0.0');
define('SCREENER_DROPDOWN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCREENER_DROPDOWN_PLUGIN_PATH', plugin_dir_path(__FILE__));

class ScreenerDropdownPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Register shortcode
        add_shortcode('screener-dropdown', array($this, 'screener_dropdown_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_screener_filter_data', array($this, 'ajax_filter_data'));
        add_action('wp_ajax_nopriv_screener_filter_data', array($this, 'ajax_filter_data'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function enqueue_scripts() {
        // jQuery (WordPress includes this)
        wp_enqueue_script('jquery');
        
        // Select2
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
        
        // DataTables
        wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), '1.13.6', true);
        wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', array(), '1.13.6');
        
        // Plugin custom scripts
        wp_enqueue_script('screener-dropdown-js', SCREENER_DROPDOWN_PLUGIN_URL . 'assets/js/screener-dropdown.js', array('jquery', 'select2-js', 'datatables-js'), SCREENER_DROPDOWN_VERSION, true);
        wp_enqueue_style('screener-dropdown-css', SCREENER_DROPDOWN_PLUGIN_URL . 'assets/css/screener-dropdown.css', array('select2-css', 'datatables-css'), SCREENER_DROPDOWN_VERSION);
        
        // Localize script for AJAX
        wp_localize_script('screener-dropdown-js', 'screener_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('screener_nonce')
        ));
    }
    
    public function screener_dropdown_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 100
        ), $atts);
        
        ob_start();
        include SCREENER_DROPDOWN_PLUGIN_PATH . 'templates/screener-dropdown.php';
        return ob_get_clean();
    }
    
    public function ajax_filter_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'screener_nonce')) {
            wp_die('Security check failed');
        }
        
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 100;
        
        $data = $this->get_filtered_data($filters, $limit);
        
        wp_send_json_success($data);
    }
    
    private function get_filtered_data($filters, $limit) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'screener_data';

        $where_clauses = array();
        $where_values = array();

        foreach ($filters as $filter) {
            $metric = sanitize_text_field($filter['metric']);
            $operator = sanitize_text_field($filter['operator']);
            $value = sanitize_text_field($filter['value']);

            // Map metric names to actual column names in the database
            $column_name = $this->map_metric_to_column($metric);

            switch ($operator) {
                case 'equals':
                    if (is_numeric($value)) {
                        $where_clauses[] = "`$column_name` = %f";
                        $where_values[] = floatval($value);
                    } else {
                        $where_clauses[] = "`$column_name` = %s";
                        $where_values[] = $value;
                    }
                    break;
                case 'greater_than':
                    $where_clauses[] = "`$column_name` > %f";
                    $where_values[] = floatval($value);
                    break;
                case 'less_than':
                    $where_clauses[] = "`$column_name` < %f";
                    $where_values[] = floatval($value);
                    break;
                case 'greater_equal':
                    $where_clauses[] = "`$column_name` >= %f";
                    $where_values[] = floatval($value);
                    break;
                case 'less_equal':
                    $where_clauses[] = "`$column_name` <= %f";
                    $where_values[] = floatval($value);
                    break;
                case 'contains':
                    $where_clauses[] = "`$column_name` LIKE %s";
                    $where_values[] = '%' . $value . '%';
                    break;
            }
        }

        $sql = "SELECT * FROM $table_name";
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        $sql .= " LIMIT %d";
        $where_values[] = $limit;

        $prepared_sql = $wpdb->prepare($sql, $where_values);
        $results = $wpdb->get_results($prepared_sql, ARRAY_A);

        return $results;
    }

    private function map_metric_to_column($metric) {
        // Map user-friendly metric names to actual CSV column names
        $mapping = array(
            'market_capitalization' => 'Market Capitalization',
            'price_to_earnings_p_e' => 'Price to Earnings [P/E]',
            'price_to_book_value_p_b' => 'Price to Book Value [P/B]',
            'price_to_sales_p_s' => 'Price to Sales [P/S]',
            'price_to_free_cash_flow_p_fcf' => 'Price to Free Cash Flow [P/FCF]',
            'ev_to_ebitda' => 'EV to EBITDA',
            'ev_to_revenue' => 'EV to Revenue',
            'enterprise_value_ev' => 'Enterprise Value (EV)',
            'gross_margin_percent_annual' => 'Gross Margin % (Annual)',
            'ebitda_margin_percent_annual' => 'EBITDA Margin % (Annual)',
            'net_profit_margin_percent_annual' => 'Net Profit Margin % (Annual)',
            'roe_return_on_equity_percent_annual' => 'ROE - Return on Equity (%) (Annual)',
            'roa_return_on_assets_percent_annual' => 'ROA - Return on Assets (%) (Annual)',
            'roce_return_on_capital_employed_percent_annual' => 'ROCE - Return on Capital Employed (%) (Annual)',
            'current_ratio_annual' => 'Current Ratio (Annual)',
            'debt_to_equity_ratio_annual' => 'Debt to Equity Ratio (Annual)',
            'cash_ratio_annual' => 'Cash Ratio (Annual)',
            'interest_cover_ratio_annual' => 'Interest Cover Ratio (Annual)',
            'revenue_growth_1y_percent_annual' => 'Revenue Growth (1y) % (Annual)',
            'net_income_growth_1y_percent_annual' => 'Net Income Growth (1y) % (Annual)',
            'eps_growth_1y_percent_annual' => 'EPS Growth (1y) % (Annual)',
            'ebitda_growth_1y_percent_annual' => 'EBITDA Growth (1y) % (Annual)',
            'dividend_yield_percent' => 'Dividend Yield %',
            'dividend_payout_ratio_percent_annual' => 'Dividend Payout Ratio % (Annual)',
            'dividend_per_basic_share_annual' => 'Dividend per Basic Share (Annual)',
            'company_name' => 'Company Name',
            'industry' => 'Industry',
            'sector' => 'Sector',
            'ticker' => 'Ticker'
        );

        // Return mapped column name or sanitized original metric name
        if (isset($mapping[$metric])) {
            return sanitize_key($mapping[$metric]);
        }

        return sanitize_key($metric);
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Screener Dropdown Settings',
            'Screener Dropdown',
            'manage_options',
            'screener-dropdown',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        include SCREENER_DROPDOWN_PLUGIN_PATH . 'admin/admin-page.php';
    }
    
    public function activate() {
        $this->create_tables();
        $this->import_csv_data();
    }
    
    public function deactivate() {
        // Clean up if needed
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create screener_list table
        $table_name = $wpdb->prefix . 'screener_list';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            metric varchar(255) NOT NULL,
            datatype varchar(50) NOT NULL,
            statement varchar(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create screener_data table (dynamic based on CSV columns)
        $this->create_data_table();
    }
    
    private function create_data_table() {
        global $wpdb;
        
        $csv_file = SCREENER_DROPDOWN_PLUGIN_PATH . 'data/screener_data.csv';
        if (!file_exists($csv_file)) {
            return;
        }
        
        $handle = fopen($csv_file, 'r');
        $headers = fgetcsv($handle);
        fclose($handle);
        
        $table_name = $wpdb->prefix . 'screener_data';
        $charset_collate = $wpdb->get_charset_collate();
        
        $columns = array();
        $columns[] = "id mediumint(9) NOT NULL AUTO_INCREMENT";
        
        foreach ($headers as $header) {
            $column_name = sanitize_key($header);
            $columns[] = "`$column_name` TEXT";
        }
        
        $columns[] = "PRIMARY KEY (id)";
        
        $sql = "CREATE TABLE $table_name (
            " . implode(',', $columns) . "
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function import_csv_data() {
        $this->import_screener_list();
        $this->import_screener_data();
    }
    
    private function import_screener_list() {
        global $wpdb;
        
        $csv_file = SCREENER_DROPDOWN_PLUGIN_PATH . 'data/screener_list.csv';
        if (!file_exists($csv_file)) {
            return;
        }
        
        $table_name = $wpdb->prefix . 'screener_list';
        
        // Clear existing data
        $wpdb->query("TRUNCATE TABLE $table_name");
        
        $handle = fopen($csv_file, 'r');
        $headers = fgetcsv($handle); // Skip header row
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $wpdb->insert(
                $table_name,
                array(
                    'metric' => $data[0],
                    'datatype' => $data[1],
                    'statement' => $data[2]
                )
            );
        }
        
        fclose($handle);
    }
    
    private function import_screener_data() {
        global $wpdb;
        
        $csv_file = SCREENER_DROPDOWN_PLUGIN_PATH . 'data/screener_data.csv';
        if (!file_exists($csv_file)) {
            return;
        }
        
        $table_name = $wpdb->prefix . 'screener_data';
        
        // Clear existing data
        $wpdb->query("TRUNCATE TABLE $table_name");
        
        $handle = fopen($csv_file, 'r');
        $headers = fgetcsv($handle);
        
        $batch_size = 100;
        $batch_data = array();
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $row_data = array();
            for ($i = 0; $i < count($headers); $i++) {
                $column_name = sanitize_key($headers[$i]);
                $row_data[$column_name] = isset($data[$i]) ? $data[$i] : '';
            }
            
            $batch_data[] = $row_data;
            
            if (count($batch_data) >= $batch_size) {
                $this->insert_batch_data($table_name, $batch_data);
                $batch_data = array();
            }
        }
        
        // Insert remaining data
        if (!empty($batch_data)) {
            $this->insert_batch_data($table_name, $batch_data);
        }
        
        fclose($handle);
    }
    
    private function insert_batch_data($table_name, $batch_data) {
        global $wpdb;
        
        foreach ($batch_data as $row_data) {
            $wpdb->insert($table_name, $row_data);
        }
    }
    
    public function get_screener_metrics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'screener_list';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY statement, metric", ARRAY_A);
        
        return $results;
    }
}

// Initialize the plugin
new ScreenerDropdownPlugin();
