<?php
session_start();

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

// Check if the user is starting the game
if (isset($_POST['start_game'])) {
    $_SESSION['game_started'] = true;

    // Redirect to the IDE or game logic page
    header('Location: ide.php'); // Adjust this path if needed
    exit();
}

// Simulate the result of the challenge (replace this with actual logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $expectedOutput = 'hello world';

    // Simulate code execution and output
    $output = strtolower(trim($code)) === 'echo \'hello world\';' ? 'hello world' : 'incorrect';

    // Determine if the challenge was solved
    $solved = $output === $expectedOutput;

    // Save the date of the attempt in the game history
    if (!isset($_SESSION['game_history'])) {
        $_SESSION['game_history'] = [];
    }
    $_SESSION['game_history'][] = date('Y-m-d H:i:s');

    // Save the badge result in the database if solved
    if ($solved) {
        if (!isset($_SESSION['badges'])) {
            $_SESSION['badges'] = [];
        }

        $newBadge = [
            'name' => 'Hello World Badge',
            'image' => __DIR__ . '/../assets/badges/goodjob.png', // Path to the badge image
            'date' => date('Y-m-d H:i:s')
        ];

        if (!isset($_SESSION['badges'][$newBadge['name']])) {
            $_SESSION['badges'][$newBadge['name']] = $newBadge;

            // Read the image file as binary data
            $imageData = file_get_contents($newBadge['image']);

            // Save the badge to the database
            $userId = 1; // Replace with the actual user ID if you have a user system
            $stmt = $pdo->prepare("INSERT INTO badges (user_id, badge_name, badge_image, date_earned) 
                                   VALUES (:user_id, :badge_name, :badge_image, :date_earned)");
            $stmt->execute([
                ':user_id' => $userId,
                ':badge_name' => $newBadge['name'],
                ':badge_image' => $imageData, // Store the binary data
                ':date_earned' => $newBadge['date']
            ]);
        }
    }

    // Redirect back to the dashboard
    $_SESSION['game_message'] = $solved ? 'Challenge Solved!' : 'Challenge Not Solved!';
    header('Location: ../index.php');
    exit();
}

// Placeholder for game logic
echo "Welcome to the IDE! Start coding your challenge here.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Quest</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.34.1/min/vs/loader.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a2e;
            color: #fff;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        #editor {
            width: 80%;
            height: 50%;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        #run-code {
            margin-top: 20px;
            padding: 10px 20px;
            background: #0f3460;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        #run-code:hover {
            background: #533483;
        }
        #output {
            margin-top: 20px;
            width: 80%;
            background: #16213e;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            white-space: pre-wrap;
        }
        #challenge {
            margin-bottom: 20px;
            font-size: 1.2rem;
            text-align: center;
        }
        .badge-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            width: 120px;
            text-align: center;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .badge-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <h1>Code Quest</h1>
    <div id="challenge">Challenge: The goal is to print "hello world" using PHP language.</div>
    <div id="editor"></div>
    <button id="run-code">Run Code</button>
    <button id="unlock-answer" disabled>Unlock Answer</button>
    <pre id="output"></pre>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Badges Earned</h5>
                <div class="d-flex flex-wrap">
                    <?php if (!empty($_SESSION['badges'])): ?>
                        <?php foreach ($_SESSION['badges'] as $badgeName => $badge): ?>
                            <div class="badge-card text-center m-2">
                                <img src="<?php echo $badge['image']; ?>" alt="<?php echo htmlspecialchars($badge['name']); ?>" class="img-fluid" style="width: 100px; height: 100px; border-radius: 10px;">
                                <p class="mt-2 mb-0"><?php echo htmlspecialchars($badge['name']); ?></p>
                                <small class="text-muted"><?php echo htmlspecialchars($badge['date']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No badges earned yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

 

    <!-- Add Modal HTML -->
    <div id="badge-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: #fff; padding: 20px; border-radius: 10px; text-align: center; width: 300px; color: #000;">
            <h2>Congratulations!</h2>
            <p>You earned a badge!</p>
            <img src="../assets/badges/goodjob.png" alt="Badge" style="width: 100px; height: 100px; border-radius: 10px; display: block; margin: 0 auto;">
            <button id="close-modal" style="margin-top: 20px; padding: 10px 20px; background: #0f3460; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Close</button>
            <button id="go-to-dashboard" style="margin-top: 20px; padding: 10px 20px; background: #0f3460; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Go to Dashboard</button>
        </div>
    </div>

    <script>
        document.getElementById('close-modal').addEventListener('click', function () {
            document.getElementById('badge-modal').style.display = 'none';
        });

        document.getElementById('go-to-dashboard').addEventListener('click', function () {
            window.location.href = '../index.php';
        });
    </script>

    <script>
        let incorrectAttempts = 0;

        // Load Monaco Editor
        require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.34.1/min/vs' } });
        require(['vs/editor/editor.main'], function () {
            window.editor = monaco.editor.create(document.getElementById('editor'), {
                value: "// Write your PHP code here\n// Your goal is to print 'hello world'\n\n// Example:\n// echo 'hello world';",
                language: "php",
                theme: ""
            });
        }); 

        // Run Code Button
        document.getElementById('run-code').addEventListener('click', function () {
            const code = window.editor.getValue();

            fetch('execute.php', { // Use HTTP
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `code=${encodeURIComponent(code)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const output = data.output.trim().toLowerCase(); // Normalize output
                const expectedOutput = "hello world"; // Expected output for the challenge

                if (output === expectedOutput) {
                    document.getElementById('output').textContent = "Correct! You solved the challenge!";
                    incorrectAttempts = 0; // Reset attempts on success

                    // Show the badge modal
                    document.getElementById('badge-modal').style.display = 'flex';

                    // Save the badge to the database
                    fetch('save_badge.php', { // Use HTTP
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            name: "Hello World Badge",
                            image: "assets/image/image.png",
                            date: new Date().toISOString().split('T')[0] // Current date
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(badge => {
                        // Dynamically update the "Badges Earned" section
                        const badgesContainer = document.querySelector('.d-flex.flex-wrap');
                        const badgeCard = document.createElement('div');
                        badgeCard.className = 'badge-card text-center m-2';
                        badgeCard.innerHTML = `
                            <img src="${badge.image}" alt="${badge.name}" class="img-fluid" style="width: 100px; height: 100px; border-radius: 10px;">
                            <p class="mt-2 mb-0">${badge.name}</p>
                            <small class="text-muted">${badge.date}</small>
                        `;
                        badgesContainer.appendChild(badgeCard);
                    })
                    .catch(error => {
                        console.error("Error saving badge:", error);
                    });
                } else {
                    incorrectAttempts++;
                    document.getElementById('output').textContent = `Incorrect. Your output: ${output}`;
                    if (incorrectAttempts >= 3) {
                        document.getElementById('unlock-answer').disabled = false; // Unlock the button
                    }
                }
            })
            .catch(error => {
                document.getElementById('output').textContent = "Error: " + error.message;
            });
        });

        // Unlock Answer Button
        document.getElementById('unlock-answer').addEventListener('click', function () {
            // Display the hint in the output
            document.getElementById('output').textContent = "Hint: Use the 'echo' statement to print 'hello world'.";

            // Update the editor with the correct answer
            const correctAnswer = `// Correct solution to print 'hello world'\necho 'hello world';`;
            window.editor.setValue(correctAnswer);

            // Automatically execute the correct answer
            const code = correctAnswer;

            fetch('execute.php', { // Use HTTP
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `code=${encodeURIComponent(code)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const output = data.output.trim().toLowerCase(); // Normalize output
                const expectedOutput = "hello world"; // Expected output for the challenge

                if (output === expectedOutput) {
                    document.getElementById('output').textContent = "Correct! You solved the challenge!";
                    incorrectAttempts = 0; // Reset attempts on success

                    // Show the badge modal
                    document.getElementById('badge-modal').style.display = 'flex';
                } else {
                    document.getElementById('output').textContent = `Unexpected error. Your output: ${output}`;
                }
            })
            .catch(error => {
                document.getElementById('output').textContent = "Error: " + error.message;
            });
        });

        // Close Modal Button
        document.getElementById('close-modal').addEventListener('click', function () {
            document.getElementById('badge-modal').style.display = 'none';
        });
    </script>
</body>
</html>