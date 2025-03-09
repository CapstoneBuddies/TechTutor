<?php
require_once 'config.php';
require_once 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    
    // Only update password if it's changed (not ********)
    $passwordUpdate = "";
    $types = "ssss";
    $params = array($fullName, $email, $address, $phone);
    
    if (isset($_POST['password']) && $_POST['password'] != "********") {
        $passwordUpdate = ", password = ?";
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $types .= "s";
        $params[] = $hashedPassword;
    }
    
    // Add user_id to params array
    $types .= "i";
    $params[] = $userId;
    
    // Update database
    $sql = "UPDATE users SET full_name = ?, email = ?, address = ?, phone = ?" . $passwordUpdate . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Update session variables
        $_SESSION['name'] = $fullName;
        $_SESSION['email'] = $email;
        $_SESSION['address'] = $address;
        $_SESSION['phone'] = $phone;
        
        header("Location: ../pages/profile.php");
    } else {
        echo "Error updating profile.";
    }
    $stmt->close();
} else {
    header("Location: ../pages/profile.php");
}
?>
