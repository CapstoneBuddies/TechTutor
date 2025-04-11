<?php
    include 'config.php';
    include 'challenges.php';
    global $pdo;

// Get the selected challenge ID (default to 1 if not set)
$selectedChallengeId = isset($_GET['challenge']) ? (int)$_GET['challenge'] : 1;

// Find the selected challenge
$selectedChallenge = null;
foreach ($challenges as $challenge) {
    if ($challenge['id'] == $selectedChallengeId) {
        $selectedChallenge = $challenge;
        break;
    }
}

// If no challenge found, use the first one
if (!$selectedChallenge && !empty($challenges)) {
    $selectedChallenge = $challenges[0];
}

// Check if the user is starting the game
if (isset($_POST['start_game'])) {
    $_SESSION['game_started'] = true;

    // Redirect to the IDE or game logic page
    header('Location: ide.php'); // Adjust this path if needed
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Quest - <?php echo htmlspecialchars($selectedChallenge['name'] ?? 'Challenge'); ?> | Gaming Academy</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.34.1/min/vs/loader.min.js"></script>
    <!-- Add Bootstrap CSS for carousel -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Add Font Awesome for the back arrow -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a2e;
            color: #fff;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
        #editor {
            width: 80%;
            height: 400px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-top: 20px;
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
            min-height: 100px;
        }
        #challenge {
            margin: 20px 0;
            font-size: 1.2rem;
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            width: 80%;
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
            color: #333;
        }
        .badge-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        .back-arrow {
            position: fixed;
            top: 20px;
            left: 20px;
            font-size: 24px;
            color: #fff;
            cursor: pointer;
            z-index: 1000;
        }
        .back-arrow:hover {
            color: #0f3460;
        }
        .badges-carousel {
            width: 80%;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
        }
        .badges-carousel .carousel-item {
            text-align: center;
        }
        .badges-carousel .carousel-control-prev,
        .badges-carousel .carousel-control-next {
            width: 5%;
        }
        .badges-title {
            text-align: center;
            margin-bottom: 15px;
            color: #fff;
        }
        .no-badges {
            text-align: center;
            color: #ccc;
            padding: 20px;
        }
        .challenge-selector {
            margin: 20px 0;
            width: 80%;
        }
        .challenge-selector select {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            background-color: #16213e;
            color: #fff;
            border: 1px solid #444;
        }
        .challenge-selector select:focus {
            outline: none;
            border-color: #0f3460;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        #unlock-answer {
            padding: 10px 20px;
            background: #444;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        #unlock-answer:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        #unlock-answer:not(:disabled):hover {
            background: #666;
        }
    </style>
</head>
<body>
    <!-- Back Arrow -->
    <a href="./" class="back-arrow">
        <i class="fas fa-arrow-left"></i>
    </a>
    
    <h1>Code Quest</h1>
    
    <!-- Challenge Selector -->
    <div class="challenge-selector">
        <select id="challenge-select" onchange="window.location.href='ide.php?challenge='+this.value">
            <?php foreach ($challenges as $challenge): ?>
                <option value="<?php echo $challenge['id']; ?>" <?php echo ($challenge['id'] == $selectedChallengeId) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($challenge['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div id="challenge">
        <strong>Challenge:</strong> <?php echo htmlspecialchars($selectedChallenge['description'] ?? 'Select a challenge'); ?>
    </div>
    
    <div id="editor"></div>
    
    <div class="button-group">
        <button id="run-code">Run Code</button>
        <button id="unlock-answer" disabled>Unlock Answer</button>
    </div>
    
    <pre id="output"></pre>

    <!-- Badges Carousel -->
    <div class="badges-carousel">
        <h5 class="badges-title">Badges Earned</h5>
        <?php if (!empty($_SESSION['badges'])): ?>
            <div id="badgesCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="false">
                <div class="carousel-inner">
                    <?php 
                    $badges = array_values($_SESSION['badges']);
                    $totalBadges = count($badges);
                    $badgesPerSlide = 3;
                    $totalSlides = ceil($totalBadges / $badgesPerSlide);
                    
                    for ($i = 0; $i < $totalSlides; $i++): 
                        $activeClass = ($i === 0) ? 'active' : '';
                    ?>
                        <div class="carousel-item <?php echo $activeClass; ?>">
                            <div class="d-flex justify-content-center">
                                <?php for ($j = $i * $badgesPerSlide; $j < min(($i + 1) * $badgesPerSlide, $totalBadges); $j++): ?>
                                    <div class="badge-card mx-2">
                                        <img src="<?php echo $badges[$j]['image']; ?>" alt="<?php echo htmlspecialchars($badges[$j]['name']); ?>" class="img-fluid" style="width: 80px; height: 80px; border-radius: 10px;">
                                        <p class="mt-2 mb-0"><?php echo htmlspecialchars($badges[$j]['name']); ?></p>
                                        <small class="text-muted"><?php echo htmlspecialchars($badges[$j]['date']); ?></small>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <?php if ($totalSlides > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#badgesCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#badgesCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-badges">No badges earned yet.</div>
        <?php endif; ?>
    </div>

    <!-- Badge Modal -->
    <div id="badge-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: #fff; padding: 20px; border-radius: 10px; text-align: center; width: 300px; color: #000;">
            <h2>Congratulations!</h2>
            <p>You earned a badge!</p>
            <img id="badge-image" src="" alt="Badge" style="width: 100px; height: 100px; border-radius: 10px; display: block; margin: 0 auto;">
            <h3 id="badge-name"></h3>
            <button id="close-modal" style="margin-top: 20px; padding: 10px 20px; background: #0f3460; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Close</button>
            <button id="go-to-dashboard" style="margin-top: 20px; padding: 10px 20px; background: #0f3460; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Go to Dashboard</button>
        </div>
    </div>

    <!-- Add Bootstrap and jQuery for carousel -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Current challenge data
        const currentChallenge = <?php echo json_encode($selectedChallenge); ?>;
        
        // Initialize incorrect attempts from localStorage or set to 0
        let incorrectAttemptsData = localStorage.getItem('incorrectAttempts') ? 
            JSON.parse(localStorage.getItem('incorrectAttempts')) : {};
            
        // Get attempts for current challenge or set to 0
        let incorrectAttempts = incorrectAttemptsData[currentChallenge.id] || 0;
        
        // Update unlock button if needed
        if (incorrectAttempts >= 3) {
            document.getElementById('unlock-answer').disabled = false;
            document.getElementById('unlock-answer').style.display = 'block';
        }

        document.getElementById('close-modal').addEventListener('click', function () {
            document.getElementById('badge-modal').style.display = 'none';
        });

        document.getElementById('go-to-dashboard').addEventListener('click', function () {
            window.location.href = './';
        });

        // Load Monaco Editor
        require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.34.1/min/vs' } });
        require(['vs/editor/editor.main'], function () {
            window.editor = monaco.editor.create(document.getElementById('editor'), {
                value: currentChallenge.starter_code || "// Write your PHP code here",
                language: "php",
                theme: "vs-dark"
            });
        }); 

        // Run Code Button
        document.getElementById('run-code').addEventListener('click', function () {
            const code = window.editor.getValue();
            const outputElement = document.getElementById('output');
            
            outputElement.textContent = "Running code...";

            fetch('execute.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `code=${encodeURIComponent(code)}&challenge_id=${currentChallenge.id}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const output = data.output.trim();
                
                if (data.solved) {
                    outputElement.textContent = "Correct! You solved the challenge!";
                    
                    // Reset attempts for this challenge on success
                    incorrectAttempts = 0;
                    incorrectAttemptsData[currentChallenge.id] = 0;
                    localStorage.setItem('incorrectAttempts', JSON.stringify(incorrectAttemptsData));
                    
                    // Hide unlock button
                    document.getElementById('unlock-answer').disabled = true;
                    document.getElementById('unlock-answer').style.display = 'none';
                    
                    // Set badge details in the modal
                    document.getElementById('badge-name').textContent = currentChallenge.badge_name || "Achievement Badge";
                    document.getElementById('badge-image').src = currentChallenge.badge_image || "assets/badges/goodjob.png";
                    
                    // Show the badge modal
                    document.getElementById('badge-modal').style.display = 'flex';
                } else {
                    incorrectAttempts++;
                    incorrectAttemptsData[currentChallenge.id] = incorrectAttempts;
                    localStorage.setItem('incorrectAttempts', JSON.stringify(incorrectAttemptsData));
                    
                    outputElement.textContent = `Incorrect. Your output: ${output}`;
                    
                    if (incorrectAttempts >= 3) {
                        document.getElementById('unlock-answer').disabled = false;
                        document.getElementById('unlock-answer').style.display = 'block';
                    }
                }
            })
            .catch(error => {
                outputElement.textContent = "Error: " + error.message;
            });
        });

        // Unlock Answer Button
        document.getElementById('unlock-answer').addEventListener('click', function () {
            // Display the hint in the output
            document.getElementById('output').textContent = `Hint: Expected output is "${currentChallenge.expected_output}"`;

            // Generate a solution based on the challenge
            let solution = "";
            switch (currentChallenge.id) {
                case 1: // Hello World
                    solution = `<?php\n// Solution\necho 'hello world';\n?>`;
                    break;
                case 2: // Factorial
                    solution = `<?php\n// Solution\nfunction factorial($n) {\n    if ($n <= 1) return 1;\n    return $n * factorial($n - 1);\n}\n\n$n = 5;\necho factorial($n);\n?>`;
                    break;
                case 3: // String Reversal
                    solution = `<?php\n// Solution\nfunction reverseString($str) {\n    return strrev($str);\n}\n\n$str = 'hello';\necho reverseString($str);\n?>`;
                    break;
                default:
                    solution = `<?php\n// Please try solving this challenge on your own.\n// The expected output is: ${currentChallenge.expected_output}\n?>`;
            }
            
            window.editor.setValue(solution);
        });
    </script>
</body>
</html>