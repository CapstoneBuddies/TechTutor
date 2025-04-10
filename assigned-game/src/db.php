<?php
// filepath: c:\xampp\htdocs\Game\php-game-project\src\db.php
$host = 'localhost';
$dbname = 'game_db'; // Ensure this matches the database name you created
$username = 'root'; // Replace with your MySQL username
$password = ''; // Replace with your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Test the database connection
try {
    $stmt = $pdo->query("SHOW TABLES");
    echo "Database connection successful. Tables in the database:";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<br>" . $row['Tables_in_game_db'];
    }
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}



// Check if the badge is uploaded and process it.
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_POST['submit'])) {
        // Check if file was uploaded
        if (isset($_FILES['badge_image']) && $_FILES['badge_image']['error'] == 0) {
            $user_id = $_POST['user_id'];
            $badge_name = $_POST['badge_name'];

            // Read the image file content
            $imageData = file_get_contents($_FILES['badge_image']['tmp_name']);

            // Prepare SQL
            $stmt = $pdo->prepare("INSERT INTO badges (user_id, badge_name, badge_image) VALUES (:user_id, :badge_name, :badge_image)");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':badge_name', $badge_name);
            $stmt->bindParam(':badge_image', $imageData, PDO::PARAM_LOB); // Important: use PDO::PARAM_LOB for large data

            $stmt->execute();

            echo "Badge uploaded successfully!";
        } else {
            echo "No image uploaded or an error occurred.";
        }
    }
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>