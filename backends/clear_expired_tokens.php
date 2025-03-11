<?php

include "db.php"; 

// SQL query to delete expired remember_me tokens
$sql = "DELETE FROM login_tokens WHERE expiration_date < NOW()";

if ($conn->query($sql) === TRUE) {
    echo "Expired tokens removed successfully.";
} else {
    log_error("Error deleting records: " . $conn->error,'database.log');
}

// SQL query to delete expired remember_me tokens
$sql = "DELETE FROM login_tokens WHERE expiration_date < NOW()";

if ($conn->query($sql) === TRUE) {
    echo "Expired tokens removed successfully.";
} else {
    log_error("Error deleting records: " . $conn->error,'tokens.log');
}

// Close the database connection
$conn->close();
?>
