<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get the plugin instance to access methods
global $wpdb;
$plugin = new ScreenerDropdownPlugin();
$metrics = $plugin->get_screener_metrics();

// Group metrics by statement for better organization
$grouped_metrics = array();
foreach ($metrics as $metric) {
    $grouped_metrics[$metric['statement']][] = $metric;
}
?>

<div id="screener-dropdown-container" class="screener-dropdown-wrapper">
    <div class="screener-header">
        <h3>Stock Screener</h3>
        <p>Filter stocks based on financial metrics and criteria</p>
    </div>
    
    <div class="screener-filters">
        <div class="filters-header">
            <h4>Filters</h4>
            <button id="add-filter-btn" class="btn btn-primary">+ Add Filter</button>
        </div>
        
        <div id="filters-container">
            <!-- Filters will be added dynamically here -->
        </div>
        
        <div class="filter-actions">
            <button id="apply-filters-btn" class="btn btn-success">Apply Filters</button>
            <button id="clear-filters-btn" class="btn btn-secondary">Clear All</button>
        </div>
    </div>
    
    <div class="screener-results">
        <div class="results-header">
            <h4>Results</h4>
            <div class="results-info">
                <span id="results-count">0 companies found</span>
                <button id="export-btn" class="btn btn-outline">Export CSV</button>
            </div>
        </div>
        
        <div class="table-container">
            <table id="screener-results-table" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Ticker</th>
                        <th>Company Name</th>
                        <th>Industry</th>
                        <th>Sector</th>
                        <th>Market Cap</th>
                        <th>P/E Ratio</th>
                        <th>Revenue (TTM)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be populated via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Filter Template (Hidden) -->
<div id="filter-template" style="display: none;">
    <div class="filter-row">
        <div class="filter-controls">
            <div class="filter-field">
                <label>Metric:</label>
                <select class="metric-select" style="width: 250px;">
                    <option value="">Select a metric...</option>

                    <!-- All metrics from screener_list.csv grouped by statement -->
                    <?php foreach ($grouped_metrics as $statement => $statement_metrics): ?>
                        <optgroup label="<?php echo esc_attr($statement); ?>">
                            <?php foreach ($statement_metrics as $metric): ?>
                                <option value="<?php echo esc_attr($metric['metric']); ?>"
                                        data-type="<?php echo esc_attr($metric['datatype']); ?>">
                                    <?php echo esc_html($metric['metric']); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-field">
                <label>Operator:</label>
                <select class="operator-select">
                    <option value="">Select operator...</option>
                    <option value="equals">Equals</option>
                    <option value="greater_than">Greater than</option>
                    <option value="less_than">Less than</option>
                    <option value="greater_equal">Greater than or equal</option>
                    <option value="less_equal">Less than or equal</option>
                    <option value="contains">Contains</option>
                </select>
            </div>
            
            <div class="filter-field">
                <label>Value:</label>
                <input type="text" class="filter-value" placeholder="Enter value...">
            </div>
            
            <div class="filter-actions">
                <button type="button" class="remove-filter-btn btn btn-danger">×</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var filterCount = 0;
    var dataTable;
    
    // Initialize DataTable
    dataTable = $('#screener-results-table').DataTable({
        processing: true,
        serverSide: false,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        scrollX: true,
        order: [[0, 'asc']],
        columnDefs: [
            {
                targets: -1, // Actions column
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return '<button class="btn btn-sm btn-info view-details" data-ticker="' + row[0] + '">View Details</button>';
                }
            }
        ]
    });
    
    // Add first filter on load
    addFilter();
    
    // Add filter button
    $('#add-filter-btn').on('click', function() {
        addFilter();
    });
    
    // Remove filter
    $(document).on('click', '.remove-filter-btn', function() {
        $(this).closest('.filter-row').remove();
        updateFilterCount();
    });
    
    // Apply filters
    $('#apply-filters-btn').on('click', function() {
        applyFilters();
    });
    
    // Clear filters
    $('#clear-filters-btn').on('click', function() {
        $('#filters-container').empty();
        addFilter();
        dataTable.clear().draw();
        $('#results-count').text('0 companies found');
    });
    
    // Export functionality
    $('#export-btn').on('click', function() {
        exportToCSV();
    });
    
    // View details
    $(document).on('click', '.view-details', function() {
        var ticker = $(this).data('ticker');
        // Implement view details functionality
        alert('View details for: ' + ticker);
    });
    
    function addFilter() {
        filterCount++;
        var filterHtml = $('#filter-template').html();
        var $newFilter = $(filterHtml);
        $newFilter.attr('data-filter-id', filterCount);
        
        $('#filters-container').append($newFilter);
        
        // Initialize Select2 for the new filter
        $newFilter.find('.metric-select').select2({
            placeholder: 'Select a metric...',
            allowClear: true
        });
        
        updateFilterCount();
    }
    
    function updateFilterCount() {
        var count = $('#filters-container .filter-row').length;
        if (count === 0) {
            addFilter();
        }
    }
    
    function applyFilters() {
        var filters = [];
        var isValid = true;
        
        $('#filters-container .filter-row').each(function() {
            var metric = $(this).find('.metric-select').val();
            var operator = $(this).find('.operator-select').val();
            var value = $(this).find('.filter-value').val();
            
            if (metric && operator && value) {
                filters.push({
                    metric: metric,
                    operator: operator,
                    value: value
                });
            } else if (metric || operator || value) {
                // Partial filter - show error
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (!isValid) {
            alert('Please complete all filter fields or remove incomplete filters.');
            return;
        }
        
        if (filters.length === 0) {
            alert('Please add at least one filter.');
            return;
        }
        
        // Show loading
        $('#apply-filters-btn').prop('disabled', true).text('Applying...');
        
        // Make AJAX request
        $.ajax({
            url: screener_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'screener_filter_data',
                nonce: screener_ajax.nonce,
                filters: filters,
                limit: 1000
            },
            success: function(response) {
                if (response.success) {
                    populateTable(response.data);
                    $('#results-count').text(response.data.length + ' companies found');
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while filtering data.');
            },
            complete: function() {
                $('#apply-filters-btn').prop('disabled', false).text('Apply Filters');
            }
        });
    }
    
    function populateTable(data) {
        dataTable.clear();
        
        if (data && data.length > 0) {
            var tableData = [];
            
            data.forEach(function(row) {
                tableData.push([
                    row.ticker || '',
                    row.company_name || row.name || '',
                    row.industry || '',
                    row.sector || '',
                    formatNumber(row.market_capitalization),
                    formatNumber(row['price_to_earnings_p_e']),
                    formatNumber(row['revenue_usd_ttm']),
                    '' // Actions column will be handled by columnDefs
                ]);
            });
            
            dataTable.rows.add(tableData).draw();
        }
    }
    
    function formatNumber(value) {
        if (!value || value === '' || isNaN(value)) {
            return 'N/A';
        }
        
        var num = parseFloat(value);
        if (num >= 1e9) {
            return (num / 1e9).toFixed(2) + 'B';
        } else if (num >= 1e6) {
            return (num / 1e6).toFixed(2) + 'M';
        } else if (num >= 1e3) {
            return (num / 1e3).toFixed(2) + 'K';
        } else {
            return num.toFixed(2);
        }
    }
    
    function exportToCSV() {
        var csvContent = "data:text/csv;charset=utf-8,";
        var headers = [];
        
        // Get headers
        $('#screener-results-table thead th').each(function(index) {
            if (index < $('#screener-results-table thead th').length - 1) { // Skip actions column
                headers.push($(this).text());
            }
        });
        csvContent += headers.join(",") + "\n";
        
        // Get data
        dataTable.rows().every(function() {
            var data = this.data();
            var row = [];
            for (var i = 0; i < data.length - 1; i++) { // Skip actions column
                row.push('"' + data[i] + '"');
            }
            csvContent += row.join(",") + "\n";
        });
        
        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "screener_results.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
});
</script>
