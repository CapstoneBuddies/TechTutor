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

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


// Check if the user is online
if(!isset($_SESSION['user'])) {
    header("Location: ./");
}

?>