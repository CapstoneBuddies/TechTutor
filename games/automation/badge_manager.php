<?php
// Include necessary files
require_once __DIR__.'/../src/config.php';
include_once __DIR__.'/../src/challenges.php';

// Check if the user has enough priviledge (Only Admin-level Role)
if(isset($_SESSION['user']) && !$_SESSION['role'] === 'ADMIN') {
    $_SESSION['msg'] = "Invalid Action";
    header("location: home");
    exit;
}

// Set higher memory limits for image processing
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);

// Set image paths
// Direct path to template in the current directory
$templatePath = __DIR__ . '/badge-template.png'; 
$outputDirectory = ROOT_PATH.'/..'.GAME_IMG.'badges/';

// Function to ensure directory exists
function ensureDirectoryExists($dir) {
    if (!file_exists($dir)) {
        log_error('Creating directory: ' . $dir, 1);
        return mkdir($dir, 0755, true);
    }
    return true;
}

// Function for simplified badge generation
function generateSimpleBadge($challengeName, $challengeType, $outputPath) {
    global $templatePath;
    $fontPath = __DIR__.'/fonts/Calistoga-Regular.ttf';
    
    try {
        log_error('Starting simplified badge generation for: ' . $challengeName, 1);

        // Check if template exists
        if (!file_exists($templatePath)) {
            throw new Exception("Template image not found");
        }
        
        // Check if font exists
        if (!file_exists($fontPath)) {
            throw new Exception("Font file not found: " . $fontPath);
        }
        
        // Create image from template
        $image = imagecreatefrompng($templatePath);
        if (!$image) {
            throw new Exception("Failed to create image from template");
        }
        
        // Enable alpha blending
        imagealphablending($image, true);
        imagesavealpha($image, true);
        


        // Set metallic white color (light gray to look metallic)
        $textColor = imagecolorallocate($image, 245, 245, 245);
        
        // CHALLENGE NAME: Calculate center position for main text
        $mainFontSize = 20;
        $mainY = 300;
        
        // Calculate text width for centering using exact same code as test_gd.php
        $text1 =  $challengeName;
        $bbox = imagettfbbox($mainFontSize, 0, $fontPath, $text1);
        $textWidth = $bbox[2] - $bbox[0];
        $boxLeft = 105;
        $boxRight = 395;
        $boxWidth = $boxRight - $boxLeft;
        $mainX = (int) round($boxLeft + ($boxWidth - $textWidth) / 2);

        imagettftext($image, $mainFontSize, 0, $mainX, $mainY, $textColor, $fontPath, $challengeName);
        
        log_error("Name Add Text");

        // CHALLENGE TYPE: Calculate center position for secondary text
        $typeFontSize = 14.5;
        $typeY = 340;
        
        // Calculate text width for centering
        $bbox = imagettfbbox($typeFontSize, 0, $fontPath, ucfirst($challengeType));
        $textWidth = $bbox[2] - $bbox[0];
        $boxLeft = 192;
        $boxRight = 305;
        $boxWidth = $boxRight - $boxLeft;
        $typeX = (int) round($boxLeft + ($boxWidth - $textWidth) / 2);
        
        log_error("Type Box");

        // Draw challenge type
        imagettftext($image, $typeFontSize, 0, $typeX, $typeY, $textColor, $fontPath, ucfirst($challengeType));
        
        log_error("I run here");

        // Save the image
        ensureDirectoryExists(dirname($outputPath));
        if (!imagepng($image, $outputPath)) {
            throw new Exception("Failed to save image");
        }
        
        imagedestroy($image);
        log_error('Badge successfully generated at: ' . $outputPath, 1);
        
        return true;
    } catch (Exception $e) {
        log_error('Badge Generation Error: ' . $e->getMessage(), 1);
        return false;
    }
}

// Function to get all challenges that need badges
function getChallengesWithoutBadges() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT c.challenge_id, c.name, c.challenge_type 
        FROM game_challenges c
        LEFT JOIN game_badges b ON c.name = b.name
        WHERE b.badge_id IS NULL
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get all existing badges
function getAllBadges() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT b.badge_id, b.name, b.description, b.image_path, c.name as challenge_name, c.challenge_type
        FROM game_badges b
        JOIN game_challenges c ON b.name = c.name
        ORDER BY c.challenge_type, c.name
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Create a safe filename from challenge name
function createSafeFilename($challengeName) {
    return preg_replace('/[^a-z0-9_-]/i', '_', strtolower($challengeName));
}

// Function to create a new badge
function createBadge($challengeId, $name, $description, $imagePath) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO game_badges (challenge_id, name, description, image_path, created_at)
            VALUES (:challenge_id, :name, :description, :image_path, NOW())
        ");
        $result = $stmt->execute([
            ':challenge_id' => $challengeId,
            ':name' => $name,
            ':description' => $description,
            ':image_path' => $imagePath
        ]);
        
        if (!$result) {
            throw new Exception("Failed to create badge in database");
        }
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        log_error('Database Error in createBadge: ' . $e->getMessage(), 1);
        throw $e;
    } catch (Exception $e) {
        log_error('General Error in createBadge: ' . $e->getMessage(), 1);
        throw $e;
    }
}

// Get challenges with missing badge images
function getChallengesWithMissingBadgeImages() {
    global $pdo, $outputDirectory;
    
    $stmt = $pdo->prepare("
        SELECT c.challenge_id, c.name, c.challenge_type, b.image_path
        FROM game_badges b
        JOIN game_challenges c ON b.name = c.name
        WHERE b.image_path IS NOT NULL
    ");
    $stmt->execute();
    $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $missing = [];
    foreach ($challenges as $challenge) {
        $imagePath = $outputDirectory . $challenge['image_path'];
        if (!file_exists($imagePath)) {
            $missing[] = $challenge;
        }
    }
    
    return $missing;
}

// Function to generate missing badge images
function generateMissingBadgeImages() {
    global $outputDirectory;
    
    $missing = getChallengesWithMissingBadgeImages();
    $results = [
        'total' => count($missing),
        'generated' => 0,
        'errors' => []
    ];
    
    foreach ($missing as $challenge) {
        $outputPath = $outputDirectory . $challenge['image_path'];
        
        try {
            if (generateSimpleBadge($challenge['name'], $challenge['challenge_type'], $outputPath)) {
                $results['generated']++;
            } else {
                $results['errors'][] = "Failed to generate badge for: " . $challenge['name'];
            }
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }
    }
    
    return $results;
}

// Check badge images and generate if needed
function checkBadgeImages($onlyGenerateMissing = false) {
    global $pdo, $outputDirectory;
    
    $stmt = $pdo->prepare("
        SELECT c.challenge_id, c.name, c.challenge_type, b.badge_id, b.image_path
        FROM game_challenges c
        LEFT JOIN game_badges b ON c.name = b.name
    ");
    $stmt->execute();
    $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = [
        'total' => count($challenges),
        'with_badge' => 0,
        'without_badge' => 0,
        'with_image' => 0,
        'without_image' => 0,
        'generated' => 0,
        'errors' => []
    ];
    
    foreach ($challenges as $challenge) {
        if ($challenge['badge_id']) {
            $results['with_badge']++;
            
            // Check if image exists
            $imagePath = $challenge['image_path'] 
                ? $outputDirectory . $challenge['image_path'] 
                : null;
            
            if ($imagePath && file_exists($imagePath)) {
                $results['with_image']++;
            } else {
                $results['without_image']++;
                
                // Generate missing image if requested
                if ($onlyGenerateMissing) {
                    try {
                        $type = $challenge['challenge_type'];
                        $safeName = createSafeFilename($challenge['name']);
                        $newImagePath = $type . '/' . $safeName . '.png';
                        $outputPath = $outputDirectory . $newImagePath;
                        
                        // Ensure directory exists
                        ensureDirectoryExists(dirname($outputPath));
                        
                        // Generate badge
                        if (generateSimpleBadge($challenge['name'], $type, $outputPath)) {
                            $results['generated']++;
                            
                            // Update image path in database if it's different
                            if ($challenge['image_path'] !== $newImagePath) {
                                $updateStmt = $pdo->prepare("
                                    UPDATE game_badges 
                                    SET image_path = :image_path 
                                    WHERE badge_id = :badge_id
                                ");
                                $updateStmt->execute([
                                    ':image_path' => $newImagePath,
                                    ':badge_id' => $challenge['badge_id']
                                ]);
                            }
                        } else {
                            $results['errors'][] = "Failed to generate badge for: " . $challenge['name'];
                        }
                    } catch (Exception $e) {
                        $results['errors'][] = $e->getMessage();
                    }
                }
            }
        } else {
            $results['without_badge']++;
        }
    }
    
    return $results;
}

// Get challenge info
function getChallengeInfo($challengeId) {
    global $pdo;

    $query = $pdo->prepare("SELECT * FROM game_challenges WHERE challenge_id = :challenge_id");
    $query->bindValue(':challenge_id',$challengeId);
    $query->execute();

    return $query->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission
if (isset($_POST['action'])) {
    $message = '';
    
    if ($_POST['action'] === 'create_badge') {
        // Handle create badge action
        // ... existing code ...
    }
    elseif ($_POST['action'] === 'update_badge') {
        // Handle update badge
        // ... existing code ...
    }
    elseif ($_POST['action'] === 'generate_missing_images') {
        $results = generateMissingBadgeImages();
        echo "<div class='alert alert-success'>
            <i class='fas fa-check-circle'></i> Generated " . $results['generated'] . " of " . $results['total'] . " missing badge images.
        </div>";
        
        if (!empty($results['errors'])) {
            echo "<div class='alert alert-danger'><strong>Errors:</strong><br>";
            foreach ($results['errors'] as $error) {
                echo htmlspecialchars($error) . "<br>";
            }
            echo "</div>";
        }
    }
    elseif ($_POST['action'] === 'check_badge_images') {
        $results = checkBadgeImages();
        
        echo "<div class='alert alert-info'>
            <i class='fas fa-info-circle'></i> Badge Image Status:<br>
            Total Challenges: " . $results['total'] . "<br>
            With Badge Records: " . $results['with_badge'] . "<br>
            Without Badge Records: " . $results['without_badge'] . "<br>
            With Badge Images: " . $results['with_image'] . "<br>
            With Missing Images: " . $results['without_image'] . "
        </div>";
        
        if (!empty($results['errors'])) {
            echo "<div class='alert alert-danger'><strong>Errors:</strong><br>";
            foreach ($results['errors'] as $error) {
                echo htmlspecialchars($error) . "<br>";
            }
            echo "</div>";
        }
    }
    elseif ($_POST['action'] === 'generate_all') {
        // Handle generate all challenges without badges
        $challenges = getChallengesWithoutBadges();
        $generated = 0;
        $errors = [];
        
        foreach ($challenges as $challenge) {
            try {
                $name = $challenge['name'];
                $type = $challenge['challenge_type'];
                $challengeId = $challenge['challenge_id'];
                
                // Create safe filename and paths
                $safeName = createSafeFilename($name);
                $imagePath = $type . '/' . $safeName . '.png';
                $outputPath = $outputDirectory . $imagePath;
                
                // Ensure directory exists
                ensureDirectoryExists(dirname($outputPath));
                
                // Generate badge image
                if (generateSimpleBadge($name, $type, $outputPath)) {
                    // Create badge entry in database
                    $description = "Badge for completing the " . $name . " challenge";
                    createBadge($challengeId, $name, $description, $imagePath);
                    $generated++;
                } else {
                    $errors[] = "Failed to generate badge for: " . $name;
                }
            } catch (Exception $e) {
                log_error('Generate All Error: ' . $e->getMessage(), 1);
                $errors[] = $e->getMessage();
            }
        }
        
        echo "<div class='alert alert-success'>
            <i class='fas fa-check-circle'></i> Generated " . $generated . " of " . count($challenges) . " badges.
        </div>";
        
        if (!empty($errors)) {
            echo "<div class='alert alert-danger'><strong>Errors:</strong><br>";
            foreach ($errors as $error) {
                echo htmlspecialchars($error) . "<br>";
            }
            echo "</div>";
        }
    }
    elseif ($_POST['action'] === 'generate_single') {
        try {
            // Validate challenge_id
            if (!isset($_POST['challenge_id']) || empty($_POST['challenge_id'])) {
                throw new Exception("No challenge selected");
            }
            
            // Check if template exists
            if (!file_exists($templatePath)) {
                throw new Exception("Badge template image not found at: $templatePath");
            }
            
            $challenge = getChallengeInfo($_POST['challenge_id']);
            if (!$challenge) {
                throw new Exception("Challenge not found");
            }
            
            // Generate Badge
            $name = $challenge['name'];
            $type = $challenge['challenge_type'];
            $challengeId = $challenge['challenge_id'];
            $safeName = createSafeFilename($name);
            $imagePath = $type . '/' . $safeName . '.png';
            $outputPath = $outputDirectory . $imagePath;
            
            log_error('Debug - Badge paths - Safe name: ' . $safeName . ', Image path: ' . $imagePath . ', Output path: ' . $outputPath, 1);
            
            // Check if badge already exists in database
            $stmt = $pdo->prepare("SELECT badge_id FROM game_badges JOIN game_challenges ON game_challenges.name = game_badges.name WHERE game_challenges.challenge_id = :challenge_id");
            $stmt->execute([':challenge_id' => $challengeId]);
            $existingBadge = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingBadge) {
                $message = "A badge already exists for this challenge in the database. Regenerating image only.";
            }
            
            // Create directory if it doesn't exist
            ensureDirectoryExists(dirname($outputPath));
            
            // Generate the badge image
            if (generateSimpleBadge($name, $type, $outputPath)) {
                // Create badge entry in database if it doesn't exist
                if (!$existingBadge) {
                    $description = "Badge for completing the " . $name . " challenge";
                    createBadge($challengeId, $name, $description, $imagePath);
                    $message = "Badge successfully created for \"$name\" challenge!";
                } else {
                    $message = "Badge image successfully updated for \"$name\" challenge!";
                }
                
                echo "<div class='alert alert-success'>
                    <i class='fas fa-check-circle'></i> $message<br>
                    <img src='" . BASE . GAME_IMG . "badges/" . $imagePath . "' class='mt-2' style='max-width: 150px;'>
                </div>";
            } else {
                throw new Exception("Failed to generate badge image");
            }
        } catch (Exception $e) {
            log_error('Badge Generation Error: ' . $e->getMessage(), 1);
            echo "<div class='alert alert-danger'>
                <i class='fas fa-exclamation-circle'></i> Error: " . htmlspecialchars($e->getMessage()) . "
            </div>";
        }
    }
}

// Get data for display
$existingBadges = getAllBadges();
$challengesWithoutBadges = getChallengesWithoutBadges();
$challengesWithMissingImages = getChallengesWithMissingBadgeImages();

// Group challenges by type
$challengesByType = [];
foreach ($challengesWithoutBadges as $challenge) {
    if (!isset($challengesByType[$challenge['challenge_type']])) {
        $challengesByType[$challenge['challenge_type']] = [];
    }
    $challengesByType[$challenge['challenge_type']][] = $challenge;
}

// Group badges by type
$badgesByType = [];
foreach ($existingBadges as $badge) {
    if (!isset($badgesByType[$badge['challenge_type']])) {
        $badgesByType[$badge['challenge_type']] = [];
    }
    $badgesByType[$badge['challenge_type']][] = $badge;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badge Manager | Gaming Academy</title>
    <!-- Favicons -->
    <link href="<?php echo BASE; ?>assets/img/stand_alone_logo.png" rel="icon">
    <link href="<?php echo BASE; ?>assets/img/apple-touch-icon.png" rel="apple-touch-icon">    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/fontawesome/css/all.min.css">
    <style>
        body {
            background-color: #1a1a2e;
            color: #fff;
            padding: 20px;
        }
        
        /* Make all paragraph text white and larger */
        p {
            color: #ffffff !important;
            font-size: 1.05rem;
            margin-bottom: 1rem;
        }
        
        /* Make form labels more visible */
        .form-label {
            color: #ffffff !important;
            font-weight: 500;
            font-size: 1.05rem;
            margin-bottom: 0.5rem;
        }
        
        .card {
            background-color: #222f3e;
            border: none;
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #0f3460;
            color: #fff;
            font-weight: bold;
        }
        .card-body {
            color: #ffffff;
        }
        .btn-primary {
            background-color: #e94560;
            border-color: #e94560;
        }
        .btn-primary:hover {
            background-color: #d43050;
            border-color: #d43050;
        }
        .alert {
            border-radius: 0;
        }
        .badge-card {
            background-color: #17223b;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .badge-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .badge-image {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .badge-title {
            font-weight: bold;
            color: #fff;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        .badge-desc {
            color: #fff;
            font-size: 0.9rem;
        }
        .type-title {
            margin-top: 30px;
            margin-bottom: 15px;
            color: #e94560;
            border-bottom: 2px solid #e94560;
            padding-bottom: 5px;
        }
        /* Ensure all text is white with improved contrast */
        .form-control, .form-select {
            background-color: #2c3e50;
            border: 1px solid #34495e;
            color: #fff;
        }
        .form-control:focus, .form-select:focus {
            background-color: #2c3e50;
            color: #fff;
            border-color: #e94560;
            box-shadow: 0 0 0 0.25rem rgba(233, 69, 96, 0.25);
        }
        .form-select option {
            background-color: #2c3e50;
            color: #fff;
        }
        .small {
            color: #ffffff !important;
            opacity: 0.8;
        }
        /* Keep error messages in their original color */
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        /* Improve visibility of list group items */
        .list-group-item.bg-secondary {
            color: #ffffff !important;
            background-color: #384b5e !important;
            border-color: #2c3e50;
        }
        .list-group-item.bg-dark {
            background-color: #1c2331 !important;
            border-color: #121921;
        }
        /* Improve text contrast for muted text */
        .text-muted {
            color: #e0e0e0 !important;
            opacity: 0.7;
        }
        /* More visible buttons */
        .btn-warning {
            color: #000000;
            font-weight: bold;
        }
        .btn-secondary {
            background-color: #4a6b8a;
            border-color: #4a6b8a;
        }
        .btn-secondary:hover {
            background-color: #3d5a75;
            border-color: #3d5a75;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="fas fa-award"></i> Badge Manager</h1>
                <p class="text-white">Use this tool to manage challenge badges for Gaming Academy.</p>
                <?php if (isset($message)): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </div>
                    
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <a href="<?php echo BASE; ?>game/" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Game Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-magic"></i> Auto-Generate Badges
                    </div>
                    <div class="card-body">
                        <p class="text-white">Automatically generate badges for all challenges that don't have a badge yet.</p>
                        
                        <?php if (empty($challengesWithoutBadges)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> All challenges have badges!
                            </div>
                        <?php else: ?>
                            <form method="post" action="">
                                <input type="hidden" name="action" value="generate_all">
                                <button type="submit" class="btn btn-primary mb-3">
                                    <i class="fas fa-wand-magic-sparkles"></i> Generate All Missing Badges
                                </button>
                            </form>
                            
                            <p class="text-white">The following challenges need badges:</p>
                            <ul class="list-group">
                                <?php foreach ($challengesByType as $type => $challenges): ?>
                                    <li class="list-group-item bg-dark text-white">
                                        <strong><?php echo ucfirst($type); ?> Challenges</strong>
                                    </li>
                                    <?php foreach ($challenges as $challenge): ?>
                                        <li class="list-group-item bg-secondary text-white">
                                            <?php echo htmlspecialchars($challenge['name']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-plus-circle"></i> Create Single Badge
                    </div>
                    <div class="card-body">
                        <p class="text-white">Generate a badge for a specific challenge.</p>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="challenge_id" class="form-label text-white fw-bold">Select Challenge:</label>
                                <select name="challenge_id" id="challenge_id" class="form-select">
                                    <?php 
                                    // Get all challenges for dropdown
                                    $stmt = $pdo->prepare("
                                        SELECT challenge_id, name, challenge_type 
                                        FROM game_challenges 
                                        ORDER BY challenge_type, name
                                    ");
                                    $stmt->execute();
                                    $allChallenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    // Group by type
                                    $allChallengesByType = [];
                                    foreach ($allChallenges as $challenge) {
                                        if (!isset($allChallengesByType[$challenge['challenge_type']])) {
                                            $allChallengesByType[$challenge['challenge_type']] = [];
                                        }
                                        $allChallengesByType[$challenge['challenge_type']][] = $challenge;
                                    }
                                    
                                    foreach ($allChallengesByType as $type => $challenges): 
                                    ?>
                                        <optgroup label="<?php echo ucfirst($type); ?> Challenges">
                                            <?php foreach ($challenges as $challenge): ?>
                                                <option value="<?php echo $challenge['challenge_id']; ?>">
                                                    <?php echo htmlspecialchars($challenge['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="hidden" name="action" value="generate_single">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paint-brush"></i> Generate Badge
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-check-circle"></i> Badge Image Management
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <form method="post" action="">
                                    <input type="hidden" name="action" value="check_badge_images">
                                    <button type="submit" class="btn btn-primary w-100 mb-3">
                                        <i class="fas fa-sync"></i> Check Badge Images
                                    </button>
                                </form>
                                <p class="small text-muted">Checks if all badge images exist on disk and generates missing ones.</p>
                            </div>
                            <div class="col-md-6">
                                <form method="post" action="">
                                    <input type="hidden" name="action" value="generate_missing_images">
                                    <button type="submit" class="btn btn-warning w-100 mb-3">
                                        <i class="fas fa-images"></i> Generate Missing Images
                                    </button>
                                </form>
                                <p class="small text-muted">Only generates badge images for challenges that have badge records but missing image files.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list"></i> Existing Badges
                    </div>
                    <div class="card-body">
                        <?php if (empty($existingBadges)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No badges have been created yet.
                            </div>
                        <?php else: ?>
                            <?php foreach ($badgesByType as $type => $badges): ?>
                                <h4 class="type-title"><i class="fas fa-folder"></i> <?php echo ucfirst($type); ?> Badges</h4>
                                <div class="row">
                                    <?php foreach ($badges as $badge): ?>
                                        <div class="col-md-6">
                                            <div class="badge-card">
                                                <img src="<?php echo GAME_IMG . 'badges/' . $badge['image_path']; ?>" 
                                                     alt="<?php echo htmlspecialchars($badge['name']); ?>" 
                                                     class="badge-image">
                                                <div class="badge-title"><?php echo htmlspecialchars($badge['name']); ?></div>
                                                <div class="badge-desc">
                                                    For: <?php echo htmlspecialchars($badge['challenge_name']); ?>
                                                </div>
                                                <div class="text-muted mt-2 small">
                                                    <?php echo htmlspecialchars($badge['image_path']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
