<?php
    include 'config.php';
    include 'challenges.php';
    global $pdo;

    // Check if the user is logged in
    if(!isset($_SESSION['game'])) {
        header("Location: ".BASE."login");
        exit;
    }

    // Initialize the global challenges variable (for backward compatibility)
    $challenges = getChallenges('programming');

    // Check if user has admin privileges
    $isAdmin = isset($_SESSION['role']) && ($_SESSION['role'] === 'TECHGURU' || $_SESSION['role'] === 'ADMIN');

    // Get the selected challenge ID (default to 1 if not set)
    $selectedChallengeId = isset($_GET['challenge']) ? (int)$_GET['challenge'] : 1;

    // Find the selected challenge
    $selectedChallenge = null;
    foreach ($challenges as $challenge) {
        if ($challenge['challenge_id'] == $selectedChallengeId) {
            $selectedChallenge = $challenge;
            break;
        }
    }

    // If no challenge found, use the first one
    if (!$selectedChallenge && !empty($challenges)) {
        $selectedChallenge = $challenges[0];
    }

    // Get the user's completed challenges
    $userId = isset($_SESSION['game']) ? $_SESSION['game'] : 1;
    $completedChallenges = [];

    try {
        $stmt = $pdo->prepare("
            SELECT c.challenge_name 
            FROM `game_user_progress` gup
            JOIN `challenge_details_view` c ON gup.challenge_id = c.challenge_id
            WHERE gup.user_id = :user_id AND gup.score > 0
        ");
        $stmt->execute([':user_id' => $userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as $result) {
            $completedChallenges[] = $result['challenge_name'];
        }
    } catch (PDOException $e) {
        // Error handling
        log_error("Error fetching completed challenges: " . $e->getMessage());
    }

    // Find the next challenge (for redirect after completion)
    $nextChallengeId = null;
    $nextChallenge = null;
    $challengeCount = count($challenges);

    for ($i = 0; $i < $challengeCount; $i++) {
        if ($challenges[$i]['challenge_id'] == $selectedChallengeId && $i < $challengeCount - 1) {
            $nextChallenge = $challenges[$i + 1];
            $nextChallengeId = $nextChallenge['challenge_id'];
            break;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Quest - <?php echo htmlspecialchars($selectedChallenge['name'] ?? 'Challenge'); ?> | Gaming Academy</title>
    <script src="<?php echo BASE; ?>assets/vendor/monaco-editor/min/vs/loader.js"></script>
    <!-- Favicons -->
    <link href="<?php echo BASE; ?>assets/img/stand_alone_logo.png" rel="icon">
    <link href="<?php echo BASE; ?>assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <!-- Add Bootstrap CSS for carousel -->
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css">
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
        
        /* Custom modal styles (for vanilla JS implementation) */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            outline: 0;
        }
        
        .modal.show {
            display: block;
        }
        
        .modal-dialog {
            position: relative;
            width: auto;
            margin: 1.75rem auto;
            max-width: 500px;
        }
        
        .modal-dialog.modal-lg {
            max-width: 800px;
        }
        
        .modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            background-color: #fff;
            color: #333;
            border-radius: 0.3rem;
            outline: 0;
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .modal-title {
            margin: 0;
            color: #333;
        }
        
        .modal-body {
            position: relative;
            flex: 1 1 auto;
            padding: 1rem;
        }
        
        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 1rem;
            border-top: 1px solid #dee2e6;
        }
        
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .btn-close {
            box-sizing: content-box;
            width: 1em;
            height: 1em;
            padding: 0.25em;
            color: #000;
            background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
            border: 0;
            border-radius: 0.25rem;
            opacity: 0.5;
            cursor: pointer;
        }
        
        .btn-close:hover {
            opacity: 0.75;
        }
        
        /* Additional modal styling for dark themes */
        .modal-content {
            background-color: #2a2a2a;
            color: #fff;
            border: 1px solid #444;
        }
        
        .modal-header {
            border-bottom: 1px solid #444;
        }
        
        .modal-footer {
            border-top: 1px solid #444;
        }
        
        .modal-title {
            color: #fff;
        }
        
        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
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
    <script>
        const currentChallenge = <?php echo json_encode($selectedChallenge); ?>;
        let nextChallengeId = "<?php echo $nextChallengeId; ?>";
        const BASE = "<?php echo BASE; ?>";
        const GAME_IMG = "<?php echo GAME_IMG; ?>";
    </script>
</head>
<body>
    <!-- Back Arrow -->
    <a href="./" class="back-arrow">
        <i class="fas fa-arrow-left"></i>
    </a>
    
    <h1>Code Quest</h1>
    
    <!-- Header -->
    <header class="code-header">
        <div class="actions">
            <?php if ($isAdmin): ?>
            <button id="add-challenge-btn" class="btn btn-success btn-sm me-2">
                <i class="bi bi-plus-circle"></i> Add Challenge
            </button>
            <?php endif; ?>
            <?php if($_SESSION['role'] === 'ADMIN'): ?>
            <button id="challenge-selector" class="btn btn-primary btn-sm"><i class="bi bi-list-task"></i> Challenges</button>
            <?php endif; ?>
            <a href="leaderboards" id="leaderboard-btn" class="btn btn-info btn-sm"><i class="bi bi-trophy"></i> Leaderboard</a>
            <a href="badges" id="badges-btn" class="btn btn-warning btn-sm"><i class="bi bi-award"></i> Badges</a>
        </div>
    </header>
    
    <!-- Challenge Selector -->
    <div class="challenge-selector">
        <select id="challenge-select" onchange="window.location.href='?challenge='+this.value">
            <?php foreach ($challenges as $challenge): ?>
                <?php 
                    $isCompleted = in_array($challenge['name'], $completedChallenges);
                    $checkmarkIcon = $isCompleted ? ' ✓' : '';
                    $completedClass = $isCompleted ? 'challenge-option-completed' : '';
                ?>
                <option value="<?php echo $challenge['challenge_id']; ?>" 
                        <?php echo ($challenge['challenge_id'] == $selectedChallengeId) ? 'selected' : ''; ?>
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
        <?php 
        // Get user badges from the database
        $userBadges = [];
        try {
            $stmt = $pdo->prepare("
                SELECT b.name, b.image_path as image, ub.earned_at as date 
                FROM `game_user_badges` ub
                JOIN `game_badges` b ON ub.badge_id = b.badge_id
                WHERE ub.user_id = :user_id
                ORDER BY ub.earned_at DESC
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $userBadges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            log_error("Error fetching user badges: " . $e->getMessage());
        }
        
        if (!empty($userBadges)): 
        ?>
            <div id="badgesCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="false">
                <div class="carousel-inner">
                    <?php 
                    $badges = array_values($userBadges);
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
                                        <img src="<?php echo GAME_IMG.'badges/'.$badges[$j]['image']; ?>" alt="<?php echo htmlspecialchars($badges[$j]['name']); ?>" class="img-fluid" style="width: 80px; height: 80px; border-radius: 10px;">
                                        <p class="mt-2 mb-0"><?php echo htmlspecialchars($badges[$j]['name']); ?></p>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($badges[$j]['date'])); ?></small>
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

    <!-- Add Challenge Modal -->
    <?php if ($isAdmin): ?>
    <div class="modal" id="addChallengeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Coding Challenge</h5>
                    <button type="button" class="btn-close" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addChallengeForm" method="post" action="add_challenge">
                        <input type="hidden" name="challenge_type" value="programming">
                        <div class="mb-3">
                            <label for="challenge_name" class="form-label">Challenge Name</label>
                            <input type="text" class="form-control" id="challenge_name" name="challenge_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Max 500 characters)</label>
                            <textarea class="form-control" id="description" name="description" rows="3" maxlength="500" required></textarea>
                            <small class="text-muted" id="description-counter">0/500 characters</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="difficulty" class="form-label">Difficulty</label>
                                <select class="form-select" id="difficulty" name="difficulty" required>
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="xp_value" class="form-label">XP Value</label>
                                <input type="number" class="form-control" id="xp_value" name="xp_value" min="10" max="500" value="100" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="starter_code" class="form-label">Starter Code (Comments/Hints)</label>
                            <textarea class="form-control" id="starter_code" name="starter_code" rows="5" placeholder="// Write your code here&#10;// Hint: This is what you need to do"></textarea>
                            <small class="text-muted">Include comments to help users understand what they need to do</small>
                        </div>
                        <div class="mb-3">
                            <label for="expected_output" class="form-label">Expected Output</label>
                            <textarea class="form-control" id="expected_output" name="expected_output" rows="3" placeholder="Exact output that will be used to validate the solution" required></textarea>
                            <small class="text-muted">This will be checked against user submissions for all programming languages</small>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" id="close-add-challenge-modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Challenge</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for viewing challenges -->
    <div class="modal" id="challengeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="challengeModalLabel">Manage Challenges</h5>
                    <button type="button" class="btn-close" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Difficulty</th>
                                    <th>XP</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($challenges as $challenge): ?>
                                <tr>
                                    <td><?php echo $challenge['challenge_id']; ?></td>
                                    <td><?php echo htmlspecialchars($challenge['name']); ?></td>
                                    <td>
                                        <?php 
                                            $difficultyLabel = '';
                                            $difficultyClass = '';
                                            
                                            switch ($challenge['difficulty_id']) {
                                                case 1:
                                                    $difficultyLabel = 'Easy';
                                                    $difficultyClass = 'bg-success';
                                                    break;
                                                case 2:
                                                    $difficultyLabel = 'Medium';
                                                    $difficultyClass = 'bg-warning';
                                                    break;
                                                case 3:
                                                    $difficultyLabel = 'Hard';
                                                    $difficultyClass = 'bg-danger';
                                                    break;
                                                default:
                                                    $difficultyLabel = 'Unknown';
                                                    $difficultyClass = 'bg-secondary';
                                            }
                                        ?>
                                        <span class="badge <?php echo $difficultyClass; ?>"><?php echo $difficultyLabel; ?></span>
                                    </td>
                                    <td><?php echo $challenge['xp_value']; ?> XP</td>
                                    <td>
                                        <button class="btn btn-sm btn-danger delete-challenge-btn" 
                                                data-challenge-id="<?php echo $challenge['challenge_id']; ?>"
                                                data-challenge-name="<?php echo htmlspecialchars($challenge['name']); ?>" title="Delete">
                                             <i class="bi bi-trash"></i> 
                                        </button>
                                        <button class="btn btn-sm btn-primary update-challenge-btn"
                                                data-challenge-id="<?php echo $challenge['challenge_id']; ?>"
                                                data-challenge-name="<?php echo htmlspecialchars($challenge['name']); ?>"
                                                data-challenge-description="<?php echo htmlspecialchars($challenge['description']); ?>"
                                                data-challenge-starter-code="<?php echo htmlspecialchars($challenge['starter_code'] ?? ''); ?>"
                                                data-challenge-expected-output="<?php echo htmlspecialchars($challenge['expected_output']); ?>"
                                                data-challenge-xp="<?php echo $challenge['xp_value']; ?>"
                                                data-challenge-difficulty="<?php echo $challenge['difficulty_id']; ?>"
                                                title="Update">
                                            <i class="bi bi-pencil"></i> 
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="close-challenge-modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the challenge: <span id="challenge-to-delete"></span>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="close-delete-confirm-modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Challenge Modal -->
    <div class="modal" id="updateChallengeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Coding Challenge</h5>
                    <button type="button" class="btn-close" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateChallengeForm" method="post" action="update_challenge">
                        <input type="hidden" name="challenge_id" id="update_challenge_id">
                        <input type="hidden" name="challenge_type" value="programming">
                        <div class="mb-3">
                            <label for="update_challenge_name" class="form-label">Challenge Name</label>
                            <input type="text" class="form-control" id="update_challenge_name" name="challenge_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="update_description" class="form-label">Description (Max 500 characters)</label>
                            <textarea class="form-control" id="update_description" name="description" rows="3" maxlength="500" required></textarea>
                            <small class="text-muted" id="update-description-counter">0/500 characters</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="update_difficulty" class="form-label">Difficulty</label>
                                <select class="form-select" id="update_difficulty" name="difficulty" required>
                                    <option value="1">Easy</option>
                                    <option value="2">Medium</option>
                                    <option value="3">Hard</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="update_xp_value" class="form-label">XP Value</label>
                                <input type="number" class="form-control" id="update_xp_value" name="xp_value" min="10" max="500" value="100" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="update_starter_code" class="form-label">Starter Code (Comments/Hints)</label>
                            <textarea class="form-control" id="update_starter_code" name="starter_code" rows="5" placeholder="// Write your code here&#10;// Hint: This is what you need to do"></textarea>
                            <small class="text-muted">Include comments to help users understand what they need to do</small>
                        </div>
                        <div class="mb-3">
                            <label for="update_expected_output" class="form-label">Expected Output</label>
                            <textarea class="form-control" id="update_expected_output" name="expected_output" rows="3" placeholder="Exact output that will be used to validate the solution" required></textarea>
                            <small class="text-muted">This will be checked against user submissions for all programming languages</small>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" id="close-update-challenge-modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Bootstrap and jQuery for carousel -->
    <script src="<?php echo BASE; ?>assets/vendor/jQuery/jquery-3.6.4.min.js"></script>
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE; ?>assets/js/codequest.js"></script>

    <!-- Add JavaScript for character counter -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Global variable to store the challenge ID to delete
            var challengeIdToDelete = null;
            
            // Function to show modal with backdrop
            function showModal(modalId) {
                var modal = document.getElementById(modalId);
                if (!modal) return;
                
                // Show the modal
                document.body.classList.add('modal-open');
                modal.style.display = 'block';
                modal.classList.add('show');
                
                // Create and add backdrop
                var backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop';
                backdrop.style.position = 'fixed';
                backdrop.style.top = '0';
                backdrop.style.left = '0';
                backdrop.style.width = '100%';
                backdrop.style.height = '100%';
                backdrop.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
                backdrop.style.zIndex = '1040';
                document.body.appendChild(backdrop);
            }
            
            // Function to hide modal and remove backdrop
            function hideModal(modalId) {
                var modal = document.getElementById(modalId);
                if (!modal) return;
                
                // Hide the modal
                modal.style.display = 'none';
                modal.classList.remove('show');
                document.body.classList.remove('modal-open');
                
                // Remove backdrop
                var backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.parentNode.removeChild(backdrop);
                }
            }
            
            // Initialize modals - hide all modals on page load
            document.querySelectorAll('.modal').forEach(function(modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            });
            
            // Remove any leftover backdrops
            var existingBackdrops = document.querySelectorAll('.modal-backdrop');
            existingBackdrops.forEach(function(backdrop) {
                backdrop.parentNode.removeChild(backdrop);
            });
            
            // Show challenges management modal
            document.getElementById('challenge-selector').addEventListener('click', function(e) {
                e.preventDefault();
                showModal('challengeModal');
            });
            
            // Show add challenge modal
            if (document.getElementById('add-challenge-btn')) {
                document.getElementById('add-challenge-btn').addEventListener('click', function(e) {
                    e.preventDefault();
                    showModal('addChallengeModal');
                });
            }
            
            // Register click handlers for all close buttons
            document.querySelectorAll('.btn-close, #close-challenge-modal, #close-add-challenge-modal, #close-delete-confirm-modal, #close-update-challenge-modal').forEach(function(button) {
                button.addEventListener('click', function() {
                    var modal = this.closest('.modal');
                    if (modal) {
                        hideModal(modal.id);
                    }
                });
            });
            
            // Close modal when clicking outside of modal content
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal') && e.target.classList.contains('show')) {
                    hideModal(e.target.id);
                }
            });
            
            // Add event delegation for delete buttons
            document.addEventListener('click', function(e) {
                // Check if clicked element is a delete button or its child
                var deleteButton = e.target.closest('.delete-challenge-btn');
                if (deleteButton) {
                    // Get challenge data from button attributes
                    challengeIdToDelete = deleteButton.getAttribute('data-challenge-id');
                    var challengeName = deleteButton.getAttribute('data-challenge-name');
                    
                    // Set the challenge name in the confirmation modal
                    document.getElementById('challenge-to-delete').textContent = challengeName;
                    
                    // Show the delete confirmation modal
                    showModal('deleteConfirmModal');
                }
                
                // Check if clicked element is an update button or its child
                var updateButton = e.target.closest('.update-challenge-btn');
                if (updateButton) {
                    // Get challenge data from button attributes
                    var challengeId = updateButton.getAttribute('data-challenge-id');
                    var challengeName = updateButton.getAttribute('data-challenge-name');
                    var challengeDescription = updateButton.getAttribute('data-challenge-description');
                    var challengeStarterCode = updateButton.getAttribute('data-challenge-starter-code');
                    var challengeExpectedOutput = updateButton.getAttribute('data-challenge-expected-output');
                    var challengeXp = updateButton.getAttribute('data-challenge-xp');
                    var challengeDifficulty = updateButton.getAttribute('data-challenge-difficulty');
                    
                    // Populate the update form with challenge data
                    document.getElementById('update_challenge_id').value = challengeId;
                    document.getElementById('update_challenge_name').value = challengeName;
                    document.getElementById('update_description').value = challengeDescription;
                    document.getElementById('update_starter_code').value = challengeStarterCode;
                    document.getElementById('update_expected_output').value = challengeExpectedOutput;
                    document.getElementById('update_xp_value').value = challengeXp;
                    document.getElementById('update_difficulty').value = challengeDifficulty;
                    
                    // Update the character counter
                    var updateDescriptionCounter = document.getElementById('update-description-counter');
                    if (updateDescriptionCounter) {
                        updateDescriptionCounter.textContent = challengeDescription.length + '/500 characters';
                    }
                    
                    // Show the update challenge modal
                    showModal('updateChallengeModal');
                }
            });
            
            // Handle the delete confirmation
            if (document.getElementById('confirm-delete')) {
                document.getElementById('confirm-delete').addEventListener('click', function() {
                    if (!challengeIdToDelete) {
                        console.error('No challenge ID selected for deletion');
                        return;
                    }
                    
                    // Create form data for the request
                    var formData = new FormData();
                    formData.append('challenge_id', challengeIdToDelete);
                    
                    // Send deletion request
                    fetch('<?php echo BASE; ?>games/src/delete_challenge.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Server responded with status: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            // Remove the row from the table
                            var row = document.querySelector(`[data-challenge-id="${challengeIdToDelete}"]`).closest('tr');
                            if (row) {
                                row.remove();
                            }
                            
                            // Show success message
                            alert('Challenge deleted successfully!');
                            
                            // Close the delete confirmation modal
                            hideModal('deleteConfirmModal');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(function(error) {
                        console.error('Error:', error);
                        alert('An error occurred: ' + error.message);
                        hideModal('deleteConfirmModal');
                    });
                });
            }
            
            // Handle update form submission
            document.getElementById('updateChallengeForm').addEventListener('submit', function(event) {
                event.preventDefault();
                
                // Get form data
                var formData = new FormData(this);
                
                // Send update request
                fetch('<?php echo BASE; ?>games/src/update_challenge.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Server responded with status: ' + response.status);
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        // Show success message
                        alert('Challenge updated successfully!');
                        
                        // Close the update modal
                        hideModal('updateChallengeModal');
                        
                        // Refresh the page to show updated data
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    alert('An error occurred: ' + error.message);
                });
            });
            
            // Add character counter functionality for description field
            var descriptionArea = document.getElementById('description');
            var counter = document.getElementById('description-counter');
            
            if (descriptionArea && counter) {
                descriptionArea.addEventListener('input', function() {
                    var count = this.value.length;
                    counter.textContent = count + '/500 characters';
                    
                    if (count > 500) {
                        counter.classList.add('text-danger');
                    } else {
                        counter.classList.remove('text-danger');
                    }
                });
            }
            
            // Add character counter functionality for update description field
            var updateDescriptionArea = document.getElementById('update_description');
            var updateCounter = document.getElementById('update-description-counter');
            
            if (updateDescriptionArea && updateCounter) {
                updateDescriptionArea.addEventListener('input', function() {
                    var count = this.value.length;
                    updateCounter.textContent = count + '/500 characters';
                    
                    if (count > 500) {
                        updateCounter.classList.add('text-danger');
                    } else {
                        updateCounter.classList.remove('text-danger');
                    }
                });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>