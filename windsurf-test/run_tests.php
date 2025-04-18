<?php
/**
 * Simple Test Runner for TechTutor
 * Runs unit tests and collects results for reporting
 */

// Path settings
define('APP_ROOT', dirname(__DIR__));
define('TEST_ROOT', __DIR__);

// Include required files
require_once APP_ROOT . '/assets/vendor/autoload.php';
require_once APP_ROOT . '/backends/config.php';
require_once APP_ROOT . '/backends/db.php';
require_once APP_ROOT . '/backends/main.php';

// Start session if not active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set up test credentials
define('TEST_ADMIN_EMAIL', 'admin@test.com');
define('TEST_TECHGURU_EMAIL', 'tutor@test.com');
define('TEST_TECHKID_EMAIL', 'student@test.com');
define('TEST_PASSWORD', 'Abc123!!');

// Storage for test results
$testResults = [];
$testSummary = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'errors' => 0,
    'skipped' => 0
];

// Simple function to add a test result
function addTestResult($class, $method, $status, $message = '', $time = 0) {
    global $testResults, $testSummary;
    
    $testResults[] = [
        'class' => $class,
        'test' => $method,
        'status' => $status,
        'message' => $message,
        'time' => $time
    ];
    
    $testSummary['total']++;
    
    if ($status == 'pass') {
        $testSummary['passed']++;
    } elseif ($status == 'fail') {
        $testSummary['failed']++;
    } elseif ($status == 'error') {
        $testSummary['errors']++;
    } elseif ($status == 'skipped') {
        $testSummary['skipped']++;
    }
}

// Load and run all test files
$testFiles = [
    'UtilsTest.php',
    'AuthTest.php', 
    'DatabaseTest.php',
    'UserManagementTest.php',
    'NotificationsTest.php'
];

echo "Running TechTutor Unit Tests\n";
echo "===========================\n\n";

foreach ($testFiles as $testFile) {
    echo "Running $testFile...\n";
    
    // Start timing
    $startTime = microtime(true);
    
    // Include the test file
    include_once TEST_ROOT . '/' . $testFile;
    
    // Get the class name
    $className = pathinfo($testFile, PATHINFO_FILENAME);
    
    if (class_exists($className)) {
        // Create an instance with the required parameter for the TestCase constructor
        $reflectionClass = new ReflectionClass($className);
        
        // Check if the class is a TestCase subclass
        if ($reflectionClass->isSubclassOf('PHPUnit\Framework\TestCase')) {
            // Create a new instance with the required name parameter
            $testInstance = $reflectionClass->newInstance('Test');
        } else {
            // Create a regular class instance
            $testInstance = $reflectionClass->newInstance();
        }
        
        // Find all test methods
        $methods = get_class_methods($testInstance);
        
        // Run setup if exists
        if (method_exists($testInstance, 'setUp')) {
            $testInstance->setUp();
        }
        
        $classResults = [
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'errors' => 0,
            'skipped' => 0
        ];
        
        // Run each test method
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                $methodStart = microtime(true);
                
                try {
                    // Run the test
                    $testInstance->$method();
                    
                    // If no exception, test passed
                    $methodTime = microtime(true) - $methodStart;
                    addTestResult($className, $method, 'pass', 'Test passed', round($methodTime, 4));
                    $classResults['passed']++;
                    $classResults['total']++;
                    echo ".";
                } catch (PHPUnit\Framework\SkippedTestError $e) {
                    // Test was skipped
                    $methodTime = microtime(true) - $methodStart;
                    addTestResult($className, $method, 'skipped', $e->getMessage(), round($methodTime, 4));
                    $classResults['skipped']++;
                    $classResults['total']++;
                    echo "S";
                } catch (PHPUnit\Framework\AssertionFailedError $e) {
                    // Test failed an assertion
                    $methodTime = microtime(true) - $methodStart;
                    addTestResult($className, $method, 'fail', $e->getMessage(), round($methodTime, 4));
                    $classResults['failed']++;
                    $classResults['total']++;
                    echo "F";
                } catch (Exception $e) {
                    // Test had an error
                    $methodTime = microtime(true) - $methodStart;
                    addTestResult($className, $method, 'error', get_class($e) . ': ' . $e->getMessage(), round($methodTime, 4));
                    $classResults['errors']++;
                    $classResults['total']++;
                    echo "E";
                }
            }
        }
        
        // Run teardown if exists
        if (method_exists($testInstance, 'tearDown')) {
            $testInstance->tearDown();
        }
        
        // Report class results
        echo "\n{$className}: ";
        echo "{$classResults['total']} tests, ";
        echo "{$classResults['passed']} passed, ";
        echo "{$classResults['failed']} failed, ";
        echo "{$classResults['errors']} errors, ";
        echo "{$classResults['skipped']} skipped.\n";
    } else {
        echo "Error: Class $className not found in $testFile\n";
    }
    
    echo "\n";
}

echo "\nTests completed. Generating report...\n";

// Pass results to report generator
file_put_contents(TEST_ROOT . '/test_results.json', json_encode([
    'results' => $testResults,
    'summary' => $testSummary
]));

echo "Results saved. Run generate_test_report.php to create the HTML report.\n";
