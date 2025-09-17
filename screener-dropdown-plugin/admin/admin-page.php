<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submissions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'reimport_data') {
        $plugin = new ScreenerDropdownPlugin();
        $plugin->activate(); // This will recreate tables and reimport data
        echo '<div class="notice notice-success"><p>Data reimported successfully!</p></div>';
    }
}

global $wpdb;
$screener_list_table = $wpdb->prefix . 'screener_list';
$screener_data_table = $wpdb->prefix . 'screener_data';

// Get table statistics
$list_count = $wpdb->get_var("SELECT COUNT(*) FROM $screener_list_table");
$data_count = $wpdb->get_var("SELECT COUNT(*) FROM $screener_data_table");
?>

<div class="wrap">
    <h1>Screener Dropdown Settings</h1>
    
    <div class="card" style="max-width: none;">
        <h2 class="title">Plugin Overview</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Plugin Version</th>
                <td><?php echo SCREENER_DROPDOWN_VERSION; ?></td>
            </tr>
            <tr>
                <th scope="row">Shortcode</th>
                <td><code>[screener-dropdown]</code></td>
            </tr>
            <tr>
                <th scope="row">Metrics Available</th>
                <td><?php echo number_format($list_count); ?> metrics</td>
            </tr>
            <tr>
                <th scope="row">Companies in Database</th>
                <td><?php echo number_format($data_count); ?> companies</td>
            </tr>
        </table>
    </div>
    
    <div class="card" style="max-width: none; margin-top: 20px;">
        <h2 class="title">Data Management</h2>
        <p>Manage the screener data and metrics.</p>
        
        <form method="post" action="">
            <input type="hidden" name="action" value="reimport_data">
            <p>
                <input type="submit" class="button button-secondary" value="Reimport CSV Data" 
                       onclick="return confirm('This will delete all existing data and reimport from CSV files. Are you sure?');">
            </p>
            <p class="description">
                This will recreate the database tables and reimport data from the CSV files in the plugin's data directory.
            </p>
        </form>
    </div>
    
    <div class="card" style="max-width: none; margin-top: 20px;">
        <h2 class="title">Usage Instructions</h2>
        <ol>
            <li><strong>Add the shortcode:</strong> Use <code>[screener-dropdown]</code> on any page or post where you want the screener to appear.</li>
            <li><strong>Configure filters:</strong> Users can add multiple filters using the dropdown interface.</li>
            <li><strong>Apply filters:</strong> Click "Apply Filters" to see results in the data table.</li>
            <li><strong>Export data:</strong> Users can export filtered results as CSV files.</li>
        </ol>
        
        <h3>Shortcode Parameters</h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Description</th>
                    <th>Default</th>
                    <th>Example</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>limit</code></td>
                    <td>Maximum number of results to return</td>
                    <td>100</td>
                    <td><code>[screener-dropdown limit="500"]</code></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="card" style="max-width: none; margin-top: 20px;">
        <h2 class="title">Database Tables</h2>
        <p>The plugin creates the following database tables:</p>
        
        <h3><?php echo $screener_list_table; ?></h3>
        <p>Contains the list of available metrics for filtering.</p>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Column</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>id</td>
                    <td>mediumint(9)</td>
                    <td>Primary key</td>
                </tr>
                <tr>
                    <td>metric</td>
                    <td>varchar(255)</td>
                    <td>Metric name</td>
                </tr>
                <tr>
                    <td>datatype</td>
                    <td>varchar(50)</td>
                    <td>Data type (int, string, %, date)</td>
                </tr>
                <tr>
                    <td>statement</td>
                    <td>varchar(255)</td>
                    <td>Financial statement category</td>
                </tr>
            </tbody>
        </table>
        
        <h3><?php echo $screener_data_table; ?></h3>
        <p>Contains the actual company data for screening.</p>
        <p><em>This table has dynamic columns based on the CSV data structure.</em></p>
    </div>
    
    <div class="card" style="max-width: none; margin-top: 20px;">
        <h2 class="title">Sample Metrics</h2>
        <p>Here are some example metrics available for filtering:</p>
        
        <?php
        $sample_metrics = $wpdb->get_results(
            "SELECT * FROM $screener_list_table ORDER BY statement, metric LIMIT 20",
            ARRAY_A
        );
        
        if ($sample_metrics): ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Data Type</th>
                        <th>Statement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sample_metrics as $metric): ?>
                        <tr>
                            <td><?php echo esc_html($metric['metric']); ?></td>
                            <td><?php echo esc_html($metric['datatype']); ?></td>
                            <td><?php echo esc_html($metric['statement']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><em>Showing first 20 metrics. Total: <?php echo number_format($list_count); ?> metrics available.</em></p>
        <?php else: ?>
            <p><em>No metrics found. Please reimport the CSV data.</em></p>
        <?php endif; ?>
    </div>
    
    <div class="card" style="max-width: none; margin-top: 20px;">
        <h2 class="title">Troubleshooting</h2>
        <h3>Common Issues</h3>
        <ul>
            <li><strong>No data showing:</strong> Make sure the CSV files are in the correct location and reimport the data.</li>
            <li><strong>Filters not working:</strong> Check that JavaScript is enabled and there are no console errors.</li>
            <li><strong>Slow performance:</strong> Consider reducing the limit parameter or optimizing database queries.</li>
        </ul>
        
        <h3>File Locations</h3>
        <ul>
            <li><strong>CSV Data:</strong> <code><?php echo SCREENER_DROPDOWN_PLUGIN_PATH; ?>data/</code></li>
            <li><strong>Assets:</strong> <code><?php echo SCREENER_DROPDOWN_PLUGIN_PATH; ?>assets/</code></li>
            <li><strong>Templates:</strong> <code><?php echo SCREENER_DROPDOWN_PLUGIN_PATH; ?>templates/</code></li>
        </ul>
    </div>
    
    <div class="card" style="max-width: none; margin-top: 20px;">
        <h2 class="title">System Information</h2>
        <table class="form-table">
            <tr>
                <th scope="row">WordPress Version</th>
                <td><?php echo get_bloginfo('version'); ?></td>
            </tr>
            <tr>
                <th scope="row">PHP Version</th>
                <td><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr>
                <th scope="row">MySQL Version</th>
                <td><?php echo $wpdb->db_version(); ?></td>
            </tr>
            <tr>
                <th scope="row">Plugin Directory</th>
                <td><?php echo SCREENER_DROPDOWN_PLUGIN_PATH; ?></td>
            </tr>
            <tr>
                <th scope="row">Plugin URL</th>
                <td><?php echo SCREENER_DROPDOWN_PLUGIN_URL; ?></td>
            </tr>
        </table>
    </div>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.card h2.title {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.widefat th {
    background-color: #f9f9f9;
}

code {
    background-color: #f1f1f1;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: Consolas, Monaco, monospace;
}
</style>
