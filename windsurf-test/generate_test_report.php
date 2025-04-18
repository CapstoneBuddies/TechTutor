<?php
/**
 * Test Report Generator for TechTutor
 * Creates a tabular HTML report from test results
 */

// Check if the results file exists
$resultsFile = __DIR__ . '/test_results.json';
if (!file_exists($resultsFile)) {
    echo "Error: No test results found. Please run run_tests.php first.\n";
    echo "Command: php run_tests.php\n";
    exit(1);
}

// Load test results
$resultsData = json_decode(file_get_contents($resultsFile), true);
$testResults = $resultsData['results'] ?? [];
$testSummary = $resultsData['summary'] ?? [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'errors' => 0,
    'skipped' => 0
];

// For backward compatibility if incomplete/risky not included
if (!isset($testSummary['incomplete'])) {
    $testSummary['incomplete'] = 0;
}
if (!isset($testSummary['risky'])) {
    $testSummary['risky'] = 0;
}

// Create HTML header for the report
$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor Unit Test Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .test-summary {
            margin-bottom: 30px;
        }
        .test-details {
            margin-top: 20px;
        }
        .test-pass {
            background-color: #d4edda;
        }
        .test-fail {
            background-color: #f8d7da;
        }
        .test-error {
            background-color: #f8d7da;
        }
        .test-skip, .test-skipped {
            background-color: #fff3cd;
        }
        .test-incomplete {
            background-color: #e2e3e5;
        }
        .test-risky {
            background-color: #e2e3e5;
        }
        h1, h2 {
            color: #333;
        }
        .timestamp {
            color: #666;
            font-style: italic;
            margin-bottom: 20px;
        }
        .credentials {
            margin-top: 30px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .status-counts {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .status-count {
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">TechTutor Unit Test Report</h1>
        <p class="timestamp">Generated on: ' . date('Y-m-d H:i:s') . '</p>
        
        <div class="credentials mb-4">
            <h3>Test Credentials</h3>
            <div class="row">
                <div class="col-md-4">
                    <strong>Admin:</strong> admin@test.com<br>
                    <strong>Password:</strong> Abc123!!
                </div>
                <div class="col-md-4">
                    <strong>TechGuru:</strong> tutor@test.com<br>
                    <strong>Password:</strong> Abc123!!
                </div>
                <div class="col-md-4">
                    <strong>TechKid:</strong> student@test.com<br>
                    <strong>Password:</strong> Abc123!!
                </div>
            </div>
        </div>';

// Generate summary table
$html .= '
        <div class="test-summary card">
            <div class="card-header bg-primary text-white">
                <h2>Test Summary</h2>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Total Tests</th>
                            <th>Passed</th>
                            <th>Failed</th>
                            <th>Errors</th>
                            <th>Skipped</th>
                            <th>Incomplete</th>
                            <th>Risky</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>' . $testSummary['total'] . '</td>
                            <td class="table-success">' . $testSummary['passed'] . '</td>
                            <td class="table-danger">' . $testSummary['failed'] . '</td>
                            <td class="table-danger">' . $testSummary['errors'] . '</td>
                            <td class="table-warning">' . $testSummary['skipped'] . '</td>
                            <td class="table-secondary">' . $testSummary['incomplete'] . '</td>
                            <td class="table-secondary">' . $testSummary['risky'] . '</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="status-counts">
                    <div class="status-count bg-success text-white">Passed: ' . $testSummary['passed'] . '</div>
                    <div class="status-count bg-danger text-white">Failed: ' . $testSummary['failed'] . '</div>
                    <div class="status-count bg-danger text-white">Errors: ' . $testSummary['errors'] . '</div>
                    <div class="status-count bg-warning text-dark">Skipped: ' . $testSummary['skipped'] . '</div>
                </div>
            </div>
        </div>';

// Generate detailed results table
$html .= '
        <div class="test-details card">
            <div class="card-header bg-primary text-white">
                <h2>Test Details</h2>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Module</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Time (s)</th>
                        </tr>
                    </thead>
                    <tbody>';

// Add each test result to the table
foreach ($testResults as $result) {
    $statusClass = 'test-' . $result['status'];
    $statusText = ucfirst($result['status']);
    
    $html .= '
                        <tr class="' . $statusClass . '">
                            <td>' . htmlspecialchars($result['module']) . '</td>
                            <td>' . htmlspecialchars($result['unit']) . '</td>
                            <td>' . htmlspecialchars($statusText) . '</td>
                            <td>' . htmlspecialchars($result['message']) . '</td>
                            <td>' . htmlspecialchars($result['time']) . '</td>
                        </tr>';
}

// Close the table and HTML
$html .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';

// Output the HTML report to a file
$reportFile = __DIR__ . '/test_report.html';
file_put_contents($reportFile, $html);

// Output location to user
echo "Test report generated: " . $reportFile . "\n";
echo "Open in your browser to view the detailed report.\n";

// If running from command line, show a basic summary
if (PHP_SAPI === 'cli') {
    echo "\nTest Summary:\n";
    echo "Total Tests: " . $testSummary['total'] . "\n";
    echo "Passed: " . $testSummary['passed'] . "\n";
    echo "Failed: " . $testSummary['failed'] . "\n";
    echo "Errors: " . $testSummary['errors'] . "\n";
    echo "Skipped: " . $testSummary['skipped'] . "\n";
}

// Delete the results file to avoid confusion with future runs
// Uncomment if you want to automatically delete the results
// unlink($resultsFile);
