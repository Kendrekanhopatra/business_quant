<?php
/**
 * API Backend for Stock Screener
 * Handles real-time data filtering and processing
 */

// Increase memory limit for large datasets
ini_set('memory_limit', '512M');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class ScreenerAPI {
    private $dataFile;
    private $metricsFile;
    private $data = [];
    private $metrics = [];
    
    public function __construct() {
        $this->dataFile = '../screener_data.csv';
        $this->metricsFile = '../screener_list.csv';
        $this->loadData();
        $this->loadMetrics();
    }
    
    private function loadData() {
        if (!file_exists($this->dataFile)) {
            throw new Exception('Data file not found');
        }
        
        $handle = fopen($this->dataFile, 'r');
        if ($handle === false) {
            throw new Exception('Cannot open data file');
        }
        
        // Read header
        $headers = fgetcsv($handle);
        if ($headers === false) {
            throw new Exception('Cannot read headers from data file');
        }
        
        // Clean headers
        $headers = array_map('trim', $headers);
        
        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $record = array_combine($headers, $row);
                $this->data[] = $record;
            }
        }
        
        fclose($handle);
    }
    
    private function loadMetrics() {
        if (!file_exists($this->metricsFile)) {
            throw new Exception('Metrics file not found');
        }
        
        $handle = fopen($this->metricsFile, 'r');
        if ($handle === false) {
            throw new Exception('Cannot open metrics file');
        }
        
        // Read header
        $headers = fgetcsv($handle);
        if ($headers === false) {
            throw new Exception('Cannot read headers from metrics file');
        }
        
        // Read metrics
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $this->metrics[] = array_combine($headers, $row);
            }
        }
        
        fclose($handle);
    }
    
    public function getMetrics() {
        return [
            'success' => true,
            'data' => $this->metrics
        ];
    }
    
    public function filterData($filters, $limit = 1000) {
        $filteredData = $this->data;
        
        foreach ($filters as $filter) {
            $metric = $filter['metric'];
            $operator = $filter['operator'];
            $value = $filter['value'];
            
            $filteredData = array_filter($filteredData, function($row) use ($metric, $operator, $value) {
                return $this->applyFilter($row, $metric, $operator, $value);
            });
        }
        
        // Limit results
        $filteredData = array_slice($filteredData, 0, $limit);
        
        // Format data for frontend
        $formattedData = array_map(function($row) {
            return [
                'ticker' => $row['Ticker'] ?? '',
                'company_name' => $row['Company Name'] ?? '',
                'industry' => $row['Industry'] ?? '',
                'sector' => $row['Sector'] ?? '',
                'market_capitalization' => $this->formatNumber($row['Market Capitalization'] ?? ''),
                'price_to_earnings_p_e' => $this->formatNumber($row['Price to Earnings [P/E]'] ?? ''),
                'revenue_usd_ttm' => $this->formatNumber($row['Revenue (USD) (TTM)'] ?? ''),
                'raw_data' => $row
            ];
        }, $filteredData);
        
        return [
            'success' => true,
            'data' => $formattedData,
            'total' => count($formattedData)
        ];
    }
    
    private function applyFilter($row, $metric, $operator, $value) {
        if (!isset($row[$metric])) {
            return false;
        }
        
        $rowValue = $row[$metric];
        
        // Handle empty values
        if ($rowValue === '' || $rowValue === null) {
            return false;
        }
        
        // Determine if numeric comparison
        $isNumeric = is_numeric($value) && is_numeric($rowValue);
        
        switch ($operator) {
            case 'equals':
                return $isNumeric ? 
                    (float)$rowValue == (float)$value : 
                    strcasecmp($rowValue, $value) === 0;
                    
            case 'greater_than':
                return $isNumeric && (float)$rowValue > (float)$value;
                
            case 'less_than':
                return $isNumeric && (float)$rowValue < (float)$value;
                
            case 'greater_equal':
                return $isNumeric && (float)$rowValue >= (float)$value;
                
            case 'less_equal':
                return $isNumeric && (float)$rowValue <= (float)$value;
                
            case 'contains':
                return stripos($rowValue, $value) !== false;
                
            default:
                return false;
        }
    }
    
    private function formatNumber($value) {
        if (!is_numeric($value) || $value === '') {
            return $value;
        }
        
        $num = (float)$value;
        if ($num >= 1e12) {
            return number_format($num / 1e12, 2) . 'T';
        } elseif ($num >= 1e9) {
            return number_format($num / 1e9, 2) . 'B';
        } elseif ($num >= 1e6) {
            return number_format($num / 1e6, 2) . 'M';
        } elseif ($num >= 1e3) {
            return number_format($num / 1e3, 2) . 'K';
        } else {
            return number_format($num, 2);
        }
    }
    
    public function getStats() {
        return [
            'success' => true,
            'data' => [
                'total_companies' => count($this->data),
                'total_metrics' => count($this->metrics),
                'last_updated' => date('Y-m-d H:i:s')
            ]
        ];
    }
}

// Main API handler
try {
    $api = new ScreenerAPI();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'screener_filter_data':
                $filters = $input['filters'] ?? [];
                $limit = (int)($input['limit'] ?? 1000);
                
                if (empty($filters)) {
                    echo json_encode([
                        'success' => false,
                        'data' => 'No filters provided'
                    ]);
                    exit;
                }
                
                $result = $api->filterData($filters, $limit);
                echo json_encode($result);
                break;
                
            case 'get_metrics':
                $result = $api->getMetrics();
                echo json_encode($result);
                break;
                
            case 'get_stats':
                $result = $api->getStats();
                echo json_encode($result);
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'data' => 'Invalid action'
                ]);
        }
    } else {
        // GET request - return basic info
        echo json_encode([
            'success' => true,
            'message' => 'Stock Screener API is running',
            'endpoints' => [
                'POST /api.php' => 'Filter data with action=screener_filter_data',
                'POST /api.php' => 'Get metrics with action=get_metrics',
                'POST /api.php' => 'Get stats with action=get_stats'
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'data' => 'Error: ' . $e->getMessage()
    ]);
}
?>
