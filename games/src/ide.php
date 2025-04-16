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

// Get the user's completed challenges
$userId = isset($_SESSION['user']) ? $_SESSION['user'] : 1;
$completedChallenges = [];

try {
    $stmt = $pdo->prepare("SELECT challenge_name FROM game_history 
                         WHERE user_id = :user_id AND result = 'Solved'");
    $stmt->execute([':user_id' => $userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $result) {
        $completedChallenges[] = $result['challenge_name'];
    }
} catch (PDOException $e) {
    // Error handling
    error_log("Error fetching completed challenges: " . $e->getMessage());
}

// Find the next challenge (for redirect after completion)
$nextChallengeId = null;
$nextChallenge = null;
$challengeCount = count($challenges);

for ($i = 0; $i < $challengeCount; $i++) {
    if ($challenges[$i]['id'] == $selectedChallengeId && $i < $challengeCount - 1) {
        $nextChallenge = $challenges[$i + 1];
        $nextChallengeId = $nextChallenge['id'];
        break;
    }
}

// Check if the user is starting the game
if (isset($_POST['start_game'])) {
    $_SESSION['game_started'] = true;

    // Redirect to the IDE or game logic page
    header('Location: '.BASE.'game/codequest'); // Adjust this path if needed
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
    <!-- Favicons -->
    <link href="<?php echo BASE; ?>assets/img/stand_alone_logo.png" rel="icon">
    <link href="<?php echo BASE; ?>assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <!-- Add Bootstrap CSS for carousel -->
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Add Font Awesome for the back arrow -->
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/fontawesome/css/all.min.css">
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
        .challenge-option-completed {
            color: #6cea6c !important;
            font-weight: bold;
        }
        .challenge-selector select option:hover {
            background-color: #333;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        #unlock-answer {
            margin-top: 20px;
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
        
        /* Level up animation */
        @keyframes levelUpGlow {
            0% { box-shadow: 0 0 5px rgba(255, 215, 0, 0.3); }
            50% { box-shadow: 0 0 20px rgba(255, 215, 0, 0.8); }
            100% { box-shadow: 0 0 5px rgba(255, 215, 0, 0.3); }
        }
        
        #level-up-modal .modal-content {
            animation: levelUpGlow 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        
        #level-up-modal .level-icon {
            animation: bounce 2s infinite;
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
        <select id="challenge-select" onchange="window.location.href='?challenge='+this.value">
            <?php foreach ($challenges as $challenge): ?>
                <?php 
                    $isCompleted = in_array($challenge['name'], $completedChallenges);
                    $checkmarkIcon = $isCompleted ? ' ✓' : '';
                    $completedClass = $isCompleted ? 'challenge-option-completed' : '';
                ?>
                <option value="<?php echo $challenge['id']; ?>" 
                        <?php echo ($challenge['id'] == $selectedChallengeId) ? 'selected' : ''; ?>
                        class="<?php echo $completedClass; ?>">
                    <?php echo htmlspecialchars($challenge['name'] . $checkmarkIcon); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div id="challenge">
        <strong>Challenge:</strong> <?php echo htmlspecialchars($selectedChallenge['description'] ?? 'Select a challenge'); ?>
    </div>
    
    <div id="editor"></div>
    
    <div class="language-selector" style="width: 80%; margin: 20px 0 0 0; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <label for="language-select" style="margin-right: 10px; color: #fff;">Language:</label>
            <select id="language-select" style="padding: 8px; background-color: #16213e; color: #fff; border: 1px solid #444; border-radius: 5px;">
                <option value="php">PHP</option>
                <option value="javascript">JavaScript</option>
                <option value="cpp">C++</option>
                <option value="java">Java</option>
                <option value="python">Python</option>
                <option value="csharp">C#</option>
                <option value="ruby">Ruby</option>
            </select>
        </div>
        <div class="button-group">
            <button class="bs-btn" id="run-code">Run Code</button>
            <button class="bs-btn" id="unlock-answer" disabled>Unlock Answer</button>
        </div>
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
                                        <img src="<?php echo GAME_IMG.'badges/programming/'.$badges[$j]['image']; ?>" alt="<?php echo htmlspecialchars($badges[$j]['name']); ?>" class="img-fluid" style="width: 80px; height: 80px; border-radius: 10px;">
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
            <div id="xp-info" style="margin-top: 10px; font-size: 1.1rem; color: #28a745;"></div>
            <button id="close-modal" style="margin-top: 20px; padding: 10px 20px; background: #0f3460; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Close</button>
            <button id="go-to-next-challenge" style="margin-top: 20px; padding: 10px 20px; background: #0f3460; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Next Challenge</button>
        </div>
    </div>

    <!-- Level Up Modal -->
    <div id="level-up-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-content" style="background: #fff; padding: 20px; border-radius: 10px; text-align: center; width: 300px; color: #000; box-shadow: 0 0 20px rgba(255, 215, 0, 0.6);">
            <h2 style="color: #0d6efd;">Level Up!</h2>
            <div class="level-icon" style="margin: 20px 0;">
                <i class="fas fa-level-up-alt" style="font-size: 50px; color: #0d6efd;"></i>
            </div>
            <p>You've reached <strong>Level <span id="new-level">0</span></strong></p>
            <p id="level-title" style="color: #28a745; font-weight: bold;"></p>
            <p style="margin-top: 15px; font-size: 0.9rem;">Keep solving challenges to earn more XP!</p>
            <button id="close-level-modal" style="margin-top: 20px; padding: 10px 20px; background: #0d6efd; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Continue</button>
        </div>
    </div>

    <!-- Add Bootstrap and jQuery for carousel -->
    <script src="<?php echo BASE; ?>assets/vendor/jQuery/jquery-3.6.4.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

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

        document.getElementById('go-to-next-challenge').addEventListener('click', function () {
            <?php if ($nextChallengeId): ?>
                window.location.href = '?challenge=<?php echo $nextChallengeId; ?>';
            <?php else: ?>
                // If there's no next challenge, go to dashboard
                window.location.href = './';
            <?php endif; ?>
        });
        
        document.getElementById('close-level-modal').addEventListener('click', function() {
            document.getElementById('level-up-modal').style.display = 'none';
        });

        // Load Monaco Editor
        require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.34.1/min/vs' } });
        require(['vs/editor/editor.main'], function () {
            // Language starters
            const languageStarters = {
                php: currentChallenge.starter_code || "<?php\n// Write your PHP code here\n\n?>",
                javascript: "// Write your JavaScript code here\n\n",
                cpp: "#include <iostream>\n\nint main() {\n    // Write your C++ code here\n    \n    return 0;\n}",
                java: "public class Main {\n    public static void main(String[] args) {\n        // Write your Java code here\n        \n    }\n}",
                python: "# Write your Python code here\n\n",
                csharp: "using System;\n\nclass Program {\n    static void Main() {\n        // Write your C# code here\n        \n    }\n}",
                ruby: "# Write your Ruby code here\n\n"
            };
            
            // Initial language
            let currentLanguage = "php";
            
            // Create editor
            window.editor = monaco.editor.create(document.getElementById('editor'), {
                value: languageStarters[currentLanguage],
                language: currentLanguage,
                theme: "vs-dark"
            });
            
            // Language selector change event
            document.getElementById('language-select').addEventListener('change', function() {
                currentLanguage = this.value;
                
                // Get current code
                const currentCode = window.editor.getValue();
                
                // Check if code is default or empty
                const isDefaultOrEmpty = currentCode.trim() === "" || 
                    Object.values(languageStarters).some(starter => 
                        currentCode.trim() === starter.trim());
                
                // Update model
                monaco.editor.setModelLanguage(window.editor.getModel(), currentLanguage);
                
                // If code is default or empty, set to new language starter
                if (isDefaultOrEmpty) {
                    window.editor.setValue(languageStarters[currentLanguage]);
                }
            });
        });

        // Run Code Button
        document.getElementById('run-code').addEventListener('click', function () {
            const code = window.editor.getValue();
            const outputElement = document.getElementById('output');
            const language = document.getElementById('language-select').value;
            
            outputElement.textContent = "Running code...";

            fetch('execute', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `code=${encodeURIComponent(code)}&challenge_id=${currentChallenge.id}&language=${language}`
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
                    // Mark challenge as completed in the UI
                    const challengeSelect = document.getElementById('challenge-select');
                    const selectedOption = challengeSelect.options[challengeSelect.selectedIndex];
                    
                    if (!selectedOption.textContent.includes('✓')) {
                        selectedOption.textContent += ' ✓';
                        selectedOption.classList.add('challenge-option-completed');
                    }
                    
                    if (data.already_earned) {
                        // Challenge already completed before
                        outputElement.textContent = "Correct! You've already solved this challenge before.";
                    } else {
                        // First time solving this challenge
                        outputElement.textContent = "Correct! You solved the challenge!";
                        
                        // Set badge details in the modal
                        document.getElementById('badge-name').textContent = currentChallenge.badge_name || "Achievement Badge";
                        document.getElementById('badge-image').src = currentChallenge.badge_image || "<?php echo GAME_IMG; ?>/badges/goodjob.png";
                        
                        // Display XP earned
                        if (data.xp_earned > 0) {
                            document.getElementById('xp-info').textContent = `+ ${data.xp_earned} XP`;
                        }
                        
                        // Check if user leveled up
                        if (data.level_info && data.level_info.leveled_up) {
                            // Set level up modal content
                            document.getElementById('new-level').textContent = data.level_info.new_level;
                            
                            // Set the level title
                            const levelTitle = data.level_info.level_info && data.level_info.level_info.badge_name 
                                ? data.level_info.level_info.badge_name 
                                : data.level_info.level_title || `Level ${data.level_info.new_level}`;
                                
                            document.getElementById('level-title').textContent = levelTitle;
                            
                            // Show level up modal after badge modal is closed
                            document.getElementById('close-modal').addEventListener('click', function levelUpHandler() {
                                document.getElementById('level-up-modal').style.display = 'flex';
                                // Remove this handler to prevent multiple bindings
                                document.getElementById('close-modal').removeEventListener('click', levelUpHandler);
                            }, { once: true });
                        }
                        
                        // Show the badge modal
                        document.getElementById('badge-modal').style.display = 'flex';
                    }
                    
                    // Reset attempts for this challenge on success
                    incorrectAttempts = 0;
                    incorrectAttemptsData[currentChallenge.id] = 0;
                    localStorage.setItem('incorrectAttempts', JSON.stringify(incorrectAttemptsData));
                    
                    // Hide unlock button
                    document.getElementById('unlock-answer').disabled = true;
                    document.getElementById('unlock-answer').style.display = 'none';
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

            // Get current language
            const currentLanguage = document.getElementById('language-select').value;
            
            // Generate a solution based on the challenge and selected language
            let solution = "";
            
            // Solutions for different languages
            const solutions = {
                // Challenge 1: Hello World
                1: {
                    php: `<?php\n// Solution\necho 'hello world';\n?>`,
                    javascript: `// Solution\nconsole.log('hello world');`,
                    cpp: `#include <iostream>\n\nint main() {\n    // Solution\n    std::cout << "hello world";\n    return 0;\n}`,
                    java: `public class Main {\n    public static void main(String[] args) {\n        // Solution\n        System.out.println("hello world");\n    }\n}`,
                    python: `# Solution\nprint("hello world")`,
                    csharp: `using System;\n\nclass Program {\n    static void Main() {\n        // Solution\n        Console.WriteLine("hello world");\n    }\n}`,
                    ruby: `# Solution\nputs "hello world"`
                },
                // Challenge 2: Factorial
                2: {
                    php: `<?php\n// Solution\nfunction factorial($n) {\n    if ($n <= 1) return 1;\n    return $n * factorial($n - 1);\n}\n\n$n = 5;\necho factorial($n);\n?>`,
                    javascript: `// Solution\nfunction factorial(n) {\n    if (n <= 1) return 1;\n    return n * factorial(n - 1);\n}\n\nconst n = 5;\nconsole.log(factorial(n));`,
                    cpp: `#include <iostream>\n\nint factorial(int n) {\n    if (n <= 1) return 1;\n    return n * factorial(n - 1);\n}\n\nint main() {\n    int n = 5;\n    std::cout << factorial(n);\n    return 0;\n}`,
                    java: `public class Main {\n    public static void main(String[] args) {\n        int n = 5;\n        System.out.println(factorial(n));\n    }\n    \n    public static int factorial(int n) {\n        if (n <= 1) return 1;\n        return n * factorial(n - 1);\n    }\n}`,
                    python: `# Solution\ndef factorial(n):\n    if n <= 1:\n        return 1\n    return n * factorial(n - 1)\n\nn = 5\nprint(factorial(n))`,
                    csharp: `using System;\n\nclass Program {\n    static void Main() {\n        int n = 5;\n        Console.WriteLine(Factorial(n));\n    }\n    \n    static int Factorial(int n) {\n        if (n <= 1) return 1;\n        return n * Factorial(n - 1);\n    }\n}`,
                    ruby: `# Solution\ndef factorial(n)\n    return 1 if n <= 1\n    n * factorial(n - 1)\nend\n\nn = 5\nputs factorial(n)`
                },
                // Challenge 3: String Reversal
                3: {
                    php: `<?php\n// Solution\nfunction reverseString($str) {\n    return strrev($str);\n}\n\n$str = 'hello';\necho reverseString($str);\n?>`,
                    javascript: `// Solution\nfunction reverseString(str) {\n    return str.split('').reverse().join('');\n}\n\nconst str = 'hello';\nconsole.log(reverseString(str));`,
                    cpp: `#include <iostream>\n#include <string>\n#include <algorithm>\n\nint main() {\n    std::string str = "hello";\n    std::reverse(str.begin(), str.end());\n    std::cout << str;\n    return 0;\n}`,
                    java: `public class Main {\n    public static void main(String[] args) {\n        String str = "hello";\n        StringBuilder sb = new StringBuilder(str);\n        System.out.println(sb.reverse().toString());\n    }\n}`,
                    python: `# Solution\ndef reverse_string(s):\n    return s[::-1]\n\nstr = "hello"\nprint(reverse_string(str))`,
                    csharp: `using System;\n\nclass Program {\n    static void Main() {\n        string str = "hello";\n        char[] charArray = str.ToCharArray();\n        Array.Reverse(charArray);\n        Console.WriteLine(new string(charArray));\n    }\n}`,
                    ruby: `# Solution\ndef reverse_string(str)\n    str.reverse\nend\n\nstr = "hello"\nputs reverse_string(str)`
                }
            };
            
            // Get solution for current challenge and language
            if (solutions[currentChallenge.id] && solutions[currentChallenge.id][currentLanguage]) {
                solution = solutions[currentChallenge.id][currentLanguage];
            } else {
                // Generic message if no specific solution is available
                solution = `// Please try solving this challenge on your own.\n// The expected output is: ${currentChallenge.expected_output}`;
            }
            
            window.editor.setValue(solution);
        });
    </script>
</body>
</html>