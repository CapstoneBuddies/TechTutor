<?php
/**
 * Code Execution Script
 * 
 * This file handles code execution requests using Judge0 API
 * It receives code from the client, sends it to Judge0 for processing,
 * and returns the results
 */

// Include necessary configuration
include 'config.php';
include 'challenges.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method. Only POST is supported.'
    ]);
    exit;
}

// Get the submitted code, challenge ID, and language
$code = isset($_POST['code']) ? $_POST['code'] : '';
$challengeId = isset($_POST['challenge_id']) ? intval($_POST['challenge_id']) : 0;
$language = isset($_POST['language']) ? $_POST['language'] : 'php';

// Validate inputs
if (empty($code)) {
    echo json_encode([
        'success' => false,
        'error' => 'No code provided.'
    ]);
    exit;
}

if ($challengeId <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid challenge ID.'
    ]);
    exit;
}

// Get the challenge details
$challenge = getChallengeById($challengeId);
if (!$challenge) {
    echo json_encode([
        'success' => false,
        'error' => 'Challenge not found.'
    ]);
    exit;
}

// Extract challenge content data
$contentData = json_decode($challenge['content'], true);
$expectedOutput = isset($contentData['expected_output']) ? $contentData['expected_output'] : '';

// Map frontend language names to Judge0 language IDs
$languageMap = [
    'php' => 68,       // PHP 7.4
    'javascript' => 63, // JavaScript (Node.js 12.14.0)
    'cpp' => 54,       // C++ (GCC 9.2.0)
    'java' => 62,      // Java (OpenJDK 13.0.1)
    'python' => 71,    // Python 3.8.1
    'csharp' => 51,    // C# (Mono 6.6.0.161)
    'ruby' => 72       // Ruby 2.7.0
];

// Check if language is supported
if (!isset($languageMap[$language])) {
    echo json_encode([
        'success' => false,
        'error' => 'Unsupported programming language.'
    ]);
    exit;
}

// Configure Judge0 API parameters
$judge0LangId = $languageMap[$language];
$judge0Url = 'https://judge0-ce.p.rapidapi.com';
$apiKey = JUDGE0_API_KEY; // From config file

// Prepare the API request to submit code
$submitUrl = $judge0Url . '/submissions';
$submitHeaders = [
    'X-RapidAPI-Key: ' . $apiKey,
    'X-RapidAPI-Host: judge0-ce.p.rapidapi.com',
    'Content-Type: application/json'
];

$submitData = json_encode([
    'language_id' => $judge0LangId,
    'source_code' => $code,
    'stdin' => isset($contentData['input_data']) ? $contentData['input_data'] : '',
    'expected_output' => $expectedOutput
]);

// Initialize cURL session for submission
$ch = curl_init($submitUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $submitHeaders);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $submitData);
// Execute the API call
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo json_encode([
        'success' => false,
        'error' => 'API request error: ' . curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

curl_close($ch);
$responseData = json_decode($response, true);

// If we don't get a token, there was an error
if (!isset($responseData['token'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit code to execution API.',
        'api_response' => $responseData
    ]);
    exit;
}

$token = $responseData['token'];
// Prepare to poll for results
$getResultUrl = $judge0Url . '/submissions/' . $token;
$getResultHeaders = [
    'X-RapidAPI-Key: ' . $apiKey,
    'X-RapidAPI-Host: judge0-ce.p.rapidapi.com'
];

// Poll for results (with a timeout)
$maxAttempts = 10;
$interval = 1; // seconds
$attempt = 0;
$result = null;

while ($attempt < $maxAttempts) {
    // Wait before polling
    sleep($interval);
    $attempt++;
    
    // Initialize cURL session for getting results
    $ch = curl_init($getResultUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $getResultHeaders);
    
    // Execute the API call
    $resultResponse = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        continue; // Try again
    }
    
    curl_close($ch);
    $result = json_decode($resultResponse, true);
    
    // If the code has finished processing, break the loop
    if (isset($result['status']) && $result['status']['id'] >= 3) {
        break;
    }
}

// If we couldn't get a result after max attempts
if (!$result || !isset($result['status'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Timed out waiting for execution results.'
    ]);
    exit;
}

// Decode the response data
$stdout = isset($result['stdout']) ? $result['stdout'] : '';
$stderr = isset($result['stderr']) ? $result['stderr'] : '';
$compile_output = isset($result['compile_output']) ? $result['compile_output'] : '';
$statusId = $result['status']['id'];
$statusDescription = $result['status']['description'];

// Determine if the solution is correct (output matches expected)
$isCorrect = false;
$executionTime = $result['time'] ?? 0;

// Normalize both outputs (trim whitespace, normalize line endings)
$normalizedStdout = preg_replace('/\s+/', ' ', trim($stdout));
$normalizedExpected = preg_replace('/\s+/', ' ', trim($expectedOutput));

if ($statusId == 3) { // Status 3 = Accepted
    $isCorrect = ($normalizedStdout == $normalizedExpected);
}

// Record completion if correct
$alreadyEarned = false;
$xpEarned = 0;

if ($isCorrect && isset($_SESSION['game'])) {
    $userId = $_SESSION['game'];
    
    // Calculate score based on challenge difficulty
    $difficultyLevel = $challenge['difficulty_id'] ?? 1;
    $basePoints = $challenge['xp_value'] ?? 100;
    $score = $basePoints * ($difficultyLevel * DIFFICULTY_MULTIPLIER);
    
    // Add time bonus if applicable
    if ($executionTime < TIME_BONUS_THRESHOLD) {
        $score += TIME_BONUS_POINTS;
    }
    
    // Check if already completed
    $checkStmt = $pdo->prepare("SELECT `progress_id`, `score` FROM `game_user_progress` WHERE `user_id` = :user_id AND `challenge_id` = :challenge_id");
    $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $checkStmt->bindParam(':challenge_id', $challengeId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        $existingProgress = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $alreadyEarned = true;
        // Only update if new score is higher
        if ($score > $existingProgress['score']) {
            $xpEarned = $score - $existingProgress['score'];
            recordCompletedChallenge($userId, $challengeId, $score, $executionTime);
        } else {
            $xpEarned = 0;
        }
    } else {
        // First time completion
        $xpEarned = $score;
        recordCompletedChallenge($userId, $challengeId, $score, $executionTime);
    }
}

// Prepare the final response
$output = '';
if (!empty($stderr)) {
    $output = $stderr;
} else if (!empty($compile_output)) {
    $output = $compile_output;
} else {
    $output = $stdout;
}

// Return a consistent response format
echo json_encode([
    'success' => ($statusId == 3), // Success if execution completed normally
    'solved' => $isCorrect,        // Used by the frontend
    'is_correct' => $isCorrect,    // Keep our original field too
    'already_earned' => $alreadyEarned,
    'xp_earned' => $xpEarned,
    'status' => [
        'id' => $statusId,
        'description' => $statusDescription
    ],
    'output' => $output,
    'execution_time' => $executionTime,
    'expected_output' => $expectedOutput,
    'next_challenge_id' => $isCorrect ? ($challengeId + 1) : $challengeId
]);
