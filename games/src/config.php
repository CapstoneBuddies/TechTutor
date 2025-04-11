<?php
// Initialize session at the very beginning
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}
define('ROOT_PATH', realpath(__DIR__ . '/..'));
// Base URL configuration
if (isset($_SERVER['HTTP_HOST']) && (
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
    strpos($_SERVER['HTTP_HOST'], '192.168.') !== false || 
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
    define('BASE', '/game/');
} else {
    define('BASE', '/');
}
define('IMG', BASE.'/assets/img/');
define('LOG_PATH', ROOT_PATH . '/logs/');

//Setting Up error_log setup
ini_set('error_log', LOG_PATH.'error.log' );
ini_set('log_errors', 1);
ini_set('display_errors', 0);
error_reporting(E_ALL);

define('DB_HOST',$_ENV['DB_HOST']);
define('DB_USER',$_ENV['DB_USER']);
define('DB_PASSWORD',$_ENV['DB_PASS']);
define('DB_GAME',$_ENV['DB_GAME']);

// Database connection
$host = DB_HOST;
$dbname = DB_GAME;
$username = DB_USER;
$password = DB_PASSWORD;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>