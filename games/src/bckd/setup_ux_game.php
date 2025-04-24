<?php
include 'config.php';
global $pdo;

// Start or resume session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the required tables exist
try {
    $tables = [
        'game_challenges',
        'challenge_details',
        'design_challenge_categories',
        'game_user_progress',
        'game_badges',
        'game_user_badges'
    ];
    
    $missingTables = [];
    foreach ($tables as $table) {
        $checkStmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($checkStmt->rowCount() === 0) {
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        echo "Required tables are missing: " . implode(', ', $missingTables) . "<br>";
        echo "Please run db_suggested.sql first to create the necessary database structure.<br>";
        exit;
    }
} catch (PDOException $e) {
    log_error("Error checking for required tables: " . $e->getMessage());
    echo "Error checking for required tables: " . $e->getMessage() . "<br>";
    exit;
}

// Check if design categories exist, create if not
try {
    $categoryStmt = $pdo->query("SELECT COUNT(*) FROM design_challenge_categories");
    $categoryCount = $categoryStmt->fetchColumn();
    
    if ($categoryCount === '0') {
        $categories = [
            ['name' => 'Logo Design', 'description' => 'Design logos for brands and products'],
            ['name' => 'Web Design', 'description' => 'Create web interfaces and elements'],
            ['name' => 'Mobile Design', 'description' => 'Design for mobile applications and interfaces'],
            ['name' => 'Print Design', 'description' => 'Create designs for physical printing'],
            ['name' => 'Social Media', 'description' => 'Design content for social media platforms']
        ];
        
        $catStmt = $pdo->prepare("INSERT INTO design_challenge_categories (name, description) VALUES (:name, :description)");
        foreach ($categories as $category) {
            $catStmt->execute([
                ':name' => $category['name'],
                ':description' => $category['description']
            ]);
        }
        echo "Design categories created successfully<br>";
    }
} catch (PDOException $e) {
    log_error("Error setting up design categories: " . $e->getMessage());
    echo "Error setting up design categories: " . $e->getMessage() . "<br>";
}

// Insert sample challenges
$challenges = [
    [
        'title' => 'Basic Logo Design',
        'description' => 'Create a simple logo using basic shapes. The logo should be clean, memorable, and use no more than three colors.',
        'difficulty' => 'Easy',
        'category' => 'Logo Design',
        'xp_value' => 100,
        'criteria' => json_encode([
            'Simplicity',
            'Use of shapes',
            'Color harmony',
            'Originality'
        ])
    ],
    [
        'title' => 'Website Header',
        'description' => 'Design a website header for a travel blog. Include a logo area, navigation menu, and a compelling visual that represents adventure.',
        'difficulty' => 'Medium',
        'category' => 'Web Design',
        'xp_value' => 150,
        'criteria' => json_encode([
            'Layout balance',
            'Visual hierarchy',
            'Typography',
            'Color scheme',
            'Usability'
        ])
    ],
    [
        'title' => 'App Icon',
        'description' => 'Create an app icon for a fitness tracking application. The icon should be simple, recognizable, and convey the purpose of the app.',
        'difficulty' => 'Easy',
        'category' => 'Mobile Design',
        'xp_value' => 100,
        'criteria' => json_encode([
            'Recognizability',
            'Simplicity',
            'Color usage',
            'Concept communication'
        ])
    ],
    [
        'title' => 'Social Media Post',
        'description' => 'Design a social media post for a new coffee shop. The post should include a captivating image, the shop name, and a short tagline.',
        'difficulty' => 'Medium',
        'category' => 'Social Media',
        'xp_value' => 150,
        'criteria' => json_encode([
            'Visual appeal',
            'Typography',
            'Brand consistency',
            'Call to action effectiveness'
        ])
    ],
    [
        'title' => 'Business Card',
        'description' => 'Create a business card for a photography studio. The card should showcase creativity while remaining professional and including all necessary contact information.',
        'difficulty' => 'Hard',
        'category' => 'Print Design',
        'xp_value' => 200,
        'criteria' => json_encode([
            'Information hierarchy',
            'Typography',
            'Visual style',
            'Print-ready design',
            'Brand representation'
        ])
    ]
];

// Add the challenges to the database
foreach ($challenges as $challenge) {
    try {
        // First check if this challenge already exists to avoid duplicates
        $checkStmt = $pdo->prepare("
            SELECT c.challenge_id 
            FROM game_challenges c
            JOIN challenge_details cd ON c.challenge_id = cd.challenge_id
            WHERE c.challenge_name = :title AND c.challenge_type = 'UI'
            LIMIT 1
        ");
        $checkStmt->execute([':title' => $challenge['title']]);
        $existingChallenge = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingChallenge) {
            // Begin transaction to ensure both tables are updated
            $pdo->beginTransaction();
            
            // 1. First insert into game_challenges
            $challengeStmt = $pdo->prepare("
                INSERT INTO game_challenges (challenge_name, challenge_type, difficulty, xp_value, status)
                VALUES (:name, 'UI', :difficulty, :xp_value, 'active')
            ");
            $challengeStmt->execute([
                ':name' => $challenge['title'],
                ':difficulty' => $challenge['difficulty'],
                ':xp_value' => $challenge['xp_value']
            ]);
            
            $challengeId = $pdo->lastInsertId();
            
            // 2. Look up the category ID
            $categoryStmt = $pdo->prepare("
                SELECT category_id FROM design_challenge_categories
                WHERE name = :category_name
                LIMIT 1
            ");
            $categoryStmt->execute([':category_name' => $challenge['category']]);
            $categoryRow = $categoryStmt->fetch(PDO::FETCH_ASSOC);
            $categoryId = $categoryRow ? $categoryRow['category_id'] : null;
            
            // 3. Insert into challenge_details
            $detailsStmt = $pdo->prepare("
                INSERT INTO challenge_details 
                (challenge_id, description, additional_data, category_id) 
                VALUES (:challenge_id, :description, :additional_data, :category_id)
            ");
            $detailsStmt->execute([
                ':challenge_id' => $challengeId,
                ':description' => $challenge['description'],
                ':additional_data' => $challenge['criteria'],
                ':category_id' => $categoryId
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            echo "Challenge '{$challenge['title']}' added successfully<br>";
        } else {
            echo "Challenge '{$challenge['title']}' already exists, skipping<br>";
        }
    } catch (PDOException $e) {
        // Roll back transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        log_error("Error adding challenge: " . $e->getMessage());
        echo "Error adding challenge '{$challenge['title']}': " . $e->getMessage() . "<br>";
    }
}

// Check if design badges exist, create if not
try {
    $badgeStmt = $pdo->prepare("
        SELECT COUNT(*) FROM game_badges 
        WHERE badge_type = 'UI'
    ");
    $badgeStmt->execute();
    $badgeCount = $badgeStmt->fetchColumn();
    
    if ($badgeCount < 4) {
        $badges = [
            [
                'name' => 'UI Novice',
                'description' => 'Completed your first UI design challenge',
                'image_path' => GAME_IMG.'badges/ui_novice.png',
                'badge_type' => 'UI',
                'requirements' => json_encode(['challenges_completed' => 1])
            ],
            [
                'name' => 'UI Designer',
                'description' => 'Completed 3 UI design challenges',
                'image_path' => GAME_IMG.'badges/ui_designer.png',
                'badge_type' => 'UI',
                'requirements' => json_encode(['challenges_completed' => 3])
            ],
            [
                'name' => 'UI Expert',
                'description' => 'Completed 5 UI design challenges',
                'image_path' => GAME_IMG.'badges/ui_expert.png',
                'badge_type' => 'UI',
                'requirements' => json_encode(['challenges_completed' => 5])
            ],
            [
                'name' => 'UI Master',
                'description' => 'Completed 10 UI design challenges',
                'image_path' => GAME_IMG.'badges/ui_master.png',
                'badge_type' => 'UI',
                'requirements' => json_encode(['challenges_completed' => 10])
            ]
        ];
        
        $badgeInsertStmt = $pdo->prepare("
            INSERT INTO game_badges (name, description, image_path, badge_type, requirements)
            VALUES (:name, :description, :image_path, :badge_type, :requirements)
        ");
        
        foreach ($badges as $badge) {
            $badgeInsertStmt->execute([
                ':name' => $badge['name'],
                ':description' => $badge['description'],
                ':image_path' => $badge['image_path'],
                ':badge_type' => $badge['badge_type'],
                ':requirements' => $badge['requirements']
            ]);
        }
        
        echo "UI design badges created successfully<br>";
    } else {
        echo "UI design badges already exist<br>";
    }
} catch (PDOException $e) {
    log_error("Error setting up UI badges: " . $e->getMessage());
    echo "Error setting up UI badges: " . $e->getMessage() . "<br>";
}

echo "Setup completed!";
?>