<?php
// Initialize session at the very beginning
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}
require_once __DIR__.'/../../backends/config.php';

// Database connection
$host = DB_HOST;
$dbname = DB_GAME;
$username = DB_USER;
$password = DB_PASSWORD;

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Game Module Configuration
define('GAME_ROOT_PATH', __DIR__);
define('GAME_ASSETS_PATH', GAME_ROOT_PATH . '/assets');
define('GAME_CHALLENGES_PATH', GAME_ROOT_PATH . '/challenges');
define('JUDGE0_API_KEY', $_ENV['JUDGE0_API_KEY']);

// Points and scoring configuration
define('DIFFICULTY_MULTIPLIER', 1.5);  // Points get multiplied by difficulty level * this value
define('TIME_BONUS_THRESHOLD', 120);   // Completing challenges under this many seconds gives bonus points
define('TIME_BONUS_POINTS', 25);       // Bonus points for completing under the threshold

// User session verification
function isUserLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

function getUserId() {
    return $_SESSION['user'] ?? null;
}