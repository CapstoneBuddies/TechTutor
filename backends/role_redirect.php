<?php
include_once 'config.php';

if (!isset($_SESSION['role'])) {
    header("Location: login");
    exit();
}

$uri = str_replace(BASE.'dashboard/', '', $_SERVER['REQUEST_URI']);
if($uri !== BASE.'dashboard') {
    header("Location: ./");
    exit();
}


// Define role-based dashboard paths
$roleDashboards = [
    'TECHKID'  => '../pages/techkid/main_dashboard.php',
    'TECHGURU' => '../pages/techguru/main_dashboard.php',
    'ADMIN'    => '../pages/admin/main_dashboard.php'
];

// Redirect based on user role
if (isset($roleDashboards[$_SESSION['role']])) {
    include_once $roleDashboards[$_SESSION['role']];
} else {
    header("Location: login");
    exit();
}
