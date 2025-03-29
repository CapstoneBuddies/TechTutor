<?php

include "db.php"; 

// SQL query to delete expired remember_me and reset tokens
$sql = "DELETE FROM login_tokens WHERE expiration_date < NOW()";

if ($conn->query($sql) === TRUE) {
    echo "Expired tokens removed successfully.";
} else {
    log_error("Error deleting records: " . $conn->error,'database');
}

// SQL query to delete expired remember_me tokens
$sql = "DELETE FROM login_tokens WHERE expiration_date < NOW()";

if ($conn->query($sql) === TRUE) {
    echo "Expired tokens removed successfully.";
} else {
    log_error("Error deleting records: " . $conn->error,'tokens.log');
}

// Remove email_verification tokens
$stmt = $conn->prepare("DELETE l FROM login_tokens l JOIN users u ON u.uid = l.user_id WHERE u.is_verified = 1 AND l.type = 'email_verification';");
$stmt->execute();

// Close the database connection
$conn->close();
?>
