<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = 'localhost';
$dbname = 'game_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$challenges = [
    [
        "description" => "Write a function to calculate the factorial of a number.",
        "starter_code" => "// Write your PHP code here\nfunction factorial(\$n) {\n    // Your code here\n}\n\n// Test the function\n\$n = 5;\necho factorial(\$n);",
        "expected_output" => "120"
    ],
    [
        "description" => "Write a function to reverse a string.",
        "starter_code" => "// Write your PHP code here\nfunction reverseString(\$str) {\n    // Your code here\n}\n\n// Test the function\n\$str = 'hello';\necho reverseString(\$str);",
        "expected_output" => "olleh"
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the submitted code
    $code = $_POST['code'] ?? '';
    $expectedOutput = 'hello world';

    // Simulate code execution and output
    $output = strtolower(trim($code)) === 'echo \'hello world\';' ? 'hello world' : 'incorrect';

    // Determine if the challenge was solved
    $solved = $output === $expectedOutput;
    $result = $solved ? 'Solved' : 'Not Solved';

    // Save the result in the database
    $challengeName = 'Print Hello World'; // Example challenge name
    $userId = 1; // Replace with the actual user ID if you have a user system
    $stmt = $pdo->prepare("INSERT INTO game_history (user_id, challenge_name, result) VALUES (:user_id, :challenge_name, :result)");
    $stmt->execute([
        ':user_id' => $userId,
        ':challenge_name' => $challengeName,
        ':result' => $result
    ]);

    // Return the result to the frontend
    echo json_encode([
        'output' => $output,
        'solved' => $solved
    ]);
}
?>