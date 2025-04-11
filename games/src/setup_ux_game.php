<?php
include 'config.php';
global $pdo;

// Create design_challenges table
$sql = "CREATE TABLE IF NOT EXISTS design_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    difficulty VARCHAR(50) NOT NULL,
    example_image VARCHAR(255),
    criteria TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $pdo->exec($sql);
    echo "Design challenges table created successfully<br>";
} catch (PDOException $e) {
    echo "Error creating design challenges table: " . $e->getMessage() . "<br>";
}

// Create design_submissions table
$sql = "CREATE TABLE IF NOT EXISTS design_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    challenge_id INT,
    image_path VARCHAR(255) NOT NULL,
    feedback TEXT,
    score INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (challenge_id) REFERENCES design_challenges(id)
)";

try {
    $pdo->exec($sql);
    echo "Design submissions table created successfully<br>";
} catch (PDOException $e) {
    echo "Error creating design submissions table: " . $e->getMessage() . "<br>";
}

// Insert sample challenges
$challenges = [
    [
        'title' => 'Basic Logo Design',
        'description' => 'Create a simple logo using basic shapes. The logo should be clean, memorable, and use no more than three colors.',
        'difficulty' => 'beginner',
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
        'difficulty' => 'intermediate',
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
        'difficulty' => 'beginner',
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
        'difficulty' => 'intermediate',
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
        'difficulty' => 'advanced',
        'criteria' => json_encode([
            'Information hierarchy',
            'Typography',
            'Visual style',
            'Print-ready design',
            'Brand representation'
        ])
    ]
];

$stmt = $pdo->prepare("INSERT INTO design_challenges (title, description, difficulty, criteria) VALUES (?, ?, ?, ?)");

foreach ($challenges as $challenge) {
    try {
        $stmt->execute([
            $challenge['title'],
            $challenge['description'],
            $challenge['difficulty'],
            $challenge['criteria']
        ]);
        echo "Challenge '{$challenge['title']}' added successfully<br>";
    } catch (PDOException $e) {
        echo "Error adding challenge: " . $e->getMessage() . "<br>";
    }
}

echo "Setup completed!";
?> 