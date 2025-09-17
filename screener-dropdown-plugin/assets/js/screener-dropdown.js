/**
 * Screener Dropdown Plugin JavaScript
 */

(function($) {
    'use strict';
    
    var ScreenerDropdown = {
        filterCount: 0,
        dataTable: null,
        metrics: [],

        init: function() {
            this.bindEvents();
            this.initializeDataTable();
            this.loadMetrics();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Add filter button
            $(document).on('click', '#add-filter-btn', function(e) {
                e.preventDefault();
                self.addFilter();
            });
            
            // Remove filter
            $(document).on('click', '.remove-filter-btn', function(e) {
                e.preventDefault();
                $(this).closest('.filter-row').fadeOut(300, function() {
                    $(this).remove();
                    self.updateFilterCount();
                });
            });
            
            // Apply filters
            $(document).on('click', '#apply-filters-btn', function(e) {
                e.preventDefault();
                self.applyFilters();
            });
            
            // Clear filters
            $(document).on('click', '#clear-filters-btn', function(e) {
                e.preventDefault();
                self.clearFilters();
            });
            
            // Export functionality
            $(document).on('click', '#export-btn', function(e) {
                e.preventDefault();
                self.exportToCSV();
            });
            
            // View details
            $(document).on('click', '.view-details', function(e) {
                e.preventDefault();
                var ticker = $(this).data('ticker');
                self.viewDetails(ticker);
            });
            
            // Metric selection change - update operators based on data type
            $(document).on('change', '.metric-select', function() {
                var dataType = $(this).find('option:selected').data('type');
                var operatorSelect = $(this).closest('.filter-row').find('.operator-select');
                self.updateOperators(operatorSelect, dataType);
            });
        },

        loadMetrics: function() {
            var self = this;

            $.ajax({
                url: screener_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_metrics',
                    nonce: screener_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.metrics = response.data;
                        self.addFilter(); // Add first filter after metrics are loaded
                    } else {
                        console.error('Failed to load metrics:', response.data);
                        self.addFilter(); // Add filter with fallback metrics
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading metrics:', error);
                    self.addFilter(); // Add filter with fallback metrics
                }
            });
        },

        initializeDataTable: function() {
            this.dataTable = $('#screener-results-table').DataTable({
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
                ],
                language: {
                    emptyTable: "No companies match your criteria. Try adjusting your filters.",
                    info: "Showing _START_ to _END_ of _TOTAL_ companies",
                    infoEmpty: "Showing 0 to 0 of 0 companies",
                    infoFiltered: "(filtered from _MAX_ total companies)",
                    lengthMenu: "Show _MENU_ companies per page",
                    search: "Search companies:",
                    zeroRecords: "No matching companies found"
                }
            });
        },
        
        addFilter: function() {
            this.filterCount++;
            var filterHtml = this.createFilterHtml();
            var $newFilter = $(filterHtml);
            $newFilter.attr('data-filter-id', this.filterCount);

            // Add with animation
            $newFilter.hide();
            $('#filters-container').append($newFilter);
            $newFilter.fadeIn(300);

            // Initialize Select2 for the new filter
            $newFilter.find('.metric-select').select2({
                placeholder: 'Select a metric...',
                allowClear: true,
                width: '100%'
            });

            this.updateFilterCount();
        },

        createFilterHtml: function() {
            var optionsHtml = '<option value="">Select a metric...</option>';

            if (this.metrics.length > 0) {
                // Group metrics by statement type
                var groupedMetrics = {};

                this.metrics.forEach(function(metric) {
                    var statement = metric.statement || 'Others';
                    if (!groupedMetrics[statement]) {
                        groupedMetrics[statement] = [];
                    }
                    groupedMetrics[statement].push(metric);
                });

                // Create optgroups
                Object.keys(groupedMetrics).sort().forEach(function(statement) {
                    var icon = this.getStatementIcon(statement);
                    optionsHtml += '<optgroup label="' + icon + ' ' + statement + '">';

                    groupedMetrics[statement].forEach(function(metric) {
                        optionsHtml += '<option value="' + metric.metric + '" data-type="' + metric.datatype + '">' +
                                      metric.metric + '</option>';
                    });

                    optionsHtml += '</optgroup>';
                }.bind(this));
            } else {
                // Fallback options if metrics didn't load
                optionsHtml += '<option value="Company Name" data-type="string">Company Name</option>';
                optionsHtml += '<option value="Industry" data-type="string">Industry</option>';
                optionsHtml += '<option value="Market Capitalization" data-type="int">Market Capitalization</option>';
            }

            return '<div class="filter-row">' +
                '<div class="filter-controls">' +
                    '<div class="filter-field">' +
                        '<label><i class="fas fa-chart-bar"></i> Metric:</label>' +
                        '<select class="metric-select" style="width: 250px;">' +
                            optionsHtml +
                        '</select>' +
                    '</div>' +
                    '<div class="filter-field">' +
                        '<label><i class="fas fa-equals"></i> Operator:</label>' +
                        '<select class="operator-select">' +
                            '<option value="">Select operator...</option>' +
                        '</select>' +
                    '</div>' +
                    '<div class="filter-field">' +
                        '<label><i class="fas fa-keyboard"></i> Value:</label>' +
                        '<input type="text" class="filter-value" placeholder="Enter value...">' +
                    '</div>' +
                    '<div class="filter-actions">' +
                        '<button type="button" class="remove-filter-btn btn btn-danger" title="Remove Filter">' +
                            '<i class="fas fa-times"></i>' +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        },

        getStatementIcon: function(statement) {
            var icons = {
                'Valuation Multiples': '📊',
                'Profitability & other metrics': '💰',
                'Growth Metrics': '📈',
                'Balance Sheet': '🏢',
                'Income Statement': '📋',
                'Cash Flow Statement Highlights': '💸',
                'Others': '🔧'
            };
            return icons[statement] || '📊';
        },
        
        updateFilterCount: function() {
            var count = $('#filters-container .filter-row').length;
            if (count === 0) {
                this.addFilter();
            }
        },
        
        updateOperators: function(operatorSelect, dataType) {
            var operators = {
                'string': [
                    {value: 'equals', text: 'Equals'},
                    {value: 'contains', text: 'Contains'}
                ],
                'int': [
                    {value: 'equals', text: 'Equals'},
                    {value: 'greater_than', text: 'Greater than'},
                    {value: 'less_than', text: 'Less than'},
                    {value: 'greater_equal', text: 'Greater than or equal'},
                    {value: 'less_equal', text: 'Less than or equal'}
                ],
                '%': [
                    {value: 'equals', text: 'Equals'},
                    {value: 'greater_than', text: 'Greater than'},
                    {value: 'less_than', text: 'Less than'},
                    {value: 'greater_equal', text: 'Greater than or equal'},
                    {value: 'less_equal', text: 'Less than or equal'}
                ],
                'date': [
                    {value: 'equals', text: 'Equals'},
                    {value: 'greater_than', text: 'After'},
                    {value: 'less_than', text: 'Before'}
                ]
            };
            
            var availableOperators = operators[dataType] || operators['string'];
            
            operatorSelect.empty();
            operatorSelect.append('<option value="">Select operator...</option>');
            
            $.each(availableOperators, function(index, operator) {
                operatorSelect.append('<option value="' + operator.value + '">' + operator.text + '</option>');
            });
        },
        
        applyFilters: function() {
            var self = this;
            var filters = [];
            var isValid = true;
            
            // Clear previous errors
            $('.filter-row').removeClass('error');
            
            $('#filters-container .filter-row').each(function() {
                var metric = $(this).find('.metric-select').val();
                var operator = $(this).find('.operator-select').val();
                var value = $(this).find('.filter-value').val().trim();
                
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
                }
            });
            
            if (!isValid) {
                this.showMessage('Please complete all filter fields or remove incomplete filters.', 'error');
                return;
            }
            
            if (filters.length === 0) {
                this.showMessage('Please add at least one filter.', 'warning');
                return;
            }
            
            // Show loading
            var $btn = $('#apply-filters-btn');
            $btn.prop('disabled', true).text('Applying...');
            
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
                        self.populateTable(response.data);
                        $('#results-count').text(response.data.length + ' companies found');
                        self.showMessage('Filters applied successfully!', 'success');
                    } else {
                        self.showMessage('Error: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    self.showMessage('An error occurred while filtering data: ' + error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Apply Filters');
                }
            });
        },
        
        populateTable: function(data) {
            this.dataTable.clear();
            
            if (data && data.length > 0) {
                var tableData = [];
                
                data.forEach(function(row) {
                    tableData.push([
                        row.ticker || '',
                        row.company_name || row.name || '',
                        row.industry || '',
                        row.sector || '',
                        this.formatNumber(row.market_capitalization),
                        this.formatNumber(row['price_to_earnings_p_e']),
                        this.formatNumber(row['revenue_usd_ttm']),
                        '' // Actions column will be handled by columnDefs
                    ]);
                }.bind(this));
                
                this.dataTable.rows.add(tableData).draw();
            }
        },
        
        clearFilters: function() {
            $('#filters-container').empty();
            this.addFilter();
            this.dataTable.clear().draw();
            $('#results-count').text('0 companies found');
            this.showMessage('All filters cleared.', 'info');
        },
        
        formatNumber: function(value) {
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
        },
        
        exportToCSV: function() {
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
            this.dataTable.rows().every(function() {
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
            link.setAttribute("download", "screener_results_" + new Date().toISOString().split('T')[0] + ".csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.showMessage('Data exported successfully!', 'success');
        },
        
        viewDetails: function(ticker) {
            // This could be expanded to show a modal with detailed company information
            // For now, we'll just show an alert
            this.showMessage('Detailed view for ' + ticker + ' - Feature coming soon!', 'info');
        },
        
        showMessage: function(message, type) {
            // Create a simple notification system
            var alertClass = 'alert-info';
            switch(type) {
                case 'success':
                    alertClass = 'alert-success';
                    break;
                case 'error':
                    alertClass = 'alert-danger';
                    break;
                case 'warning':
                    alertClass = 'alert-warning';
                    break;
            }
            
            var $alert = $('<div class="alert ' + alertClass + ' alert-dismissible" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;">' +
                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                message +
                '</div>');
            
            $('body').append($alert);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $alert.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual dismiss
            $alert.find('.close').on('click', function() {
                $alert.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#screener-dropdown-container').length) {
            ScreenerDropdown.init();
        }
    });
    
})(jQuery);
