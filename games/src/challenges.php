<?php
// Define available challenges - can be expanded in the future
$challenges = [
    [
        "id" => 1,
        "name" => "Hello World",
        "description" => "Write code to print 'hello world'.",
        "starter_code" => "// Write your PHP code here\n// Your goal is to print 'hello world'\n\n// Example:\n// echo 'hello world';",
        "expected_output" => "hello world",
        "badge_name" => "Hello World",
        "badge_image" => "programming/hello_world.png",
        "xp_value" => 10, // Easiest challenge
        "difficulty" => "Beginner"
    ],
    [
        "id" => 2,
        "name" => "Factorial Calculator",
        "description" => "Write a function to calculate the factorial of a number.",
        "starter_code" => "// Write your PHP code here\nfunction factorial(\$n) {\n    // Your code here\n}\n\n// Test the function\n\$n = 5;\necho factorial(\$n);",
        "expected_output" => "120",
        "badge_name" => "Factorial Master",
        "badge_image" => "programming/factorial.png",
        "xp_value" => 20,
        "difficulty" => "Beginner"
    ],
    [
        "id" => 3,
        "name" => "String Reversal",
        "description" => "Write a function to reverse a string.",
        "starter_code" => "// Write your PHP code here\nfunction reverseString(\$str) {\n    // Your code here\n}\n\n// Test the function\n\$str = 'hello';\necho reverseString(\$str);",
        "expected_output" => "olleh",
        "badge_name" => "String Wizard",
        "badge_image" => "programming/string_reversal.png",
        "xp_value" => 15,
        "difficulty" => "Beginner"
    ],
    [
        "id" => 4,
        "name" => "Palindrome Checker",
        "description" => "Create a function that checks if a string is a palindrome (reads the same backward as forward).",
        "starter_code" => "// Write a function to check if a string is a palindrome\nfunction isPalindrome(\$str) {\n    // Your code here\n}\n\n// Test with this word\n\$word = 'racecar';\necho isPalindrome(\$word) ? 'true' : 'false';",
        "expected_output" => "true",
        "badge_name" => "Palindrome Detective",
        "badge_image" => "programming/palindrome.png",
        "xp_value" => 25,
        "difficulty" => "Intermediate"
    ],
    [
        "id" => 5,
        "name" => "FizzBuzz Challenge",
        "description" => "Write a program that prints numbers from 1 to 15. For multiples of 3, print 'Fizz' instead of the number. For multiples of 5, print 'Buzz'. For numbers that are multiples of both 3 and 5, print 'FizzBuzz'.",
        "starter_code" => "// Implement the FizzBuzz challenge for numbers 1-15\n\n// Your code here",
        "expected_output" => "1\n2\nFizz\n4\nBuzz\nFizz\n7\n8\nFizz\nBuzz\n11\nFizz\n13\n14\nFizzBuzz",
        "badge_name" => "FizzBuzz Champion",
        "badge_image" => "programming/fizzbuzz.png",
        "xp_value" => 30,
        "difficulty" => "Intermediate"
    ],
    [
        "id" => 6,
        "name" => "Array Sum",
        "description" => "Write a function that calculates the sum of all elements in an array.",
        "starter_code" => "// Create a function to sum all elements in an array\nfunction arraySum(\$arr) {\n    // Your code here\n}\n\n// Test with this array\n\$numbers = [5, 10, 15, 20, 25];\necho arraySum(\$numbers);",
        "expected_output" => "75",
        "badge_name" => "Array Master",
        "badge_image" => "programming/array_sum.png",
        "xp_value" => 20,
        "difficulty" => "Beginner"
    ],
    [
        "id" => 7,
        "name" => "Prime Number Checker",
        "description" => "Create a function that checks if a number is prime.",
        "starter_code" => "// Write a function to check if a number is prime\nfunction isPrime(\$number) {\n    // Your code here\n}\n\n// Test with this number\n\$num = 29;\necho isPrime(\$num) ? 'true' : 'false';",
        "expected_output" => "true",
        "badge_name" => "Prime Detective",
        "badge_image" => "programming/prime.png",
        "xp_value" => 35,
        "difficulty" => "Intermediate"
    ],
    [
        "id" => 8,
        "name" => "Find Maximum Value",
        "description" => "Write a function to find the maximum value in a nested array without using built-in max functions.",
        "starter_code" => "// Write a function to find the maximum value in a nested array\nfunction findMax(\$arr) {\n    // Your code here\n}\n\n// Test with this nested array\n\$nestedArray = [[3, 5], [8, 2], [1, [9, 4]]];\necho findMax(\$nestedArray);",
        "expected_output" => "9",
        "badge_name" => "Maximum Explorer",
        "badge_image" => "programming/max_value.png",
        "xp_value" => 40,
        "difficulty" => "Advanced"
    ],
    [
        "id" => 9,
        "name" => "Password Validator",
        "description" => "Create a function that validates if a password meets these criteria: at least 8 characters, contains at least one uppercase letter, one lowercase letter, one number, and one special character (!@#$%^&*).",
        "starter_code" => "// Create a function that validates passwords according to criteria\nfunction isValidPassword(\$password) {\n    // Your code here\n}\n\n// Test with this password\n\$pass = 'SecureP@ss1';\necho isValidPassword(\$pass) ? 'valid' : 'invalid';",
        "expected_output" => "valid",
        "badge_name" => "Security Expert",
        "badge_image" => "programming/password.png",
        "xp_value" => 45,
        "difficulty" => "Advanced"
    ],
    [
        "id" => 10, 
        "name" => "Caesar Cipher",
        "description" => "Implement a Caesar cipher encryption function that shifts each letter in a string by a given number of positions in the alphabet.",
        "starter_code" => "// Implement a Caesar cipher function\nfunction caesarCipher(\$text, \$shift) {\n    // Your code here\n}\n\n// Test with this text and shift value\n\$text = 'hello';\n\$shift = 3;\necho caesarCipher(\$text, \$shift);",
        "expected_output" => "khoor",
        "badge_name" => "Encryption Expert",
        "badge_image" => "programming/cipher.png",
        "xp_value" => 50,
        "difficulty" => "Advanced"
    ],
    [
        "id" => 11,
        "name" => "Anagram Checker",
        "description" => "Create a function that checks if two strings are anagrams of each other (contain the same characters in different order).",
        "starter_code" => "// Create a function to check if two strings are anagrams\nfunction areAnagrams(\$str1, \$str2) {\n    // Your code here\n}\n\n// Test with these strings\n\$string1 = 'listen';\n\$string2 = 'silent';\necho areAnagrams(\$string1, \$string2) ? 'true' : 'false';",
        "expected_output" => "true",
        "badge_name" => "Anagram Detective",
        "badge_image" => "programming/anagram.png",
        "xp_value" => 40,
        "difficulty" => "Advanced"
    ]
];

/**
 * Get challenge details by ID
 * 
 * @param int $challengeId The ID of the challenge to retrieve
 * @return array|null The challenge details or null if not found
 */
function getChallengeById($challengeId) {
    global $challenges;
    
    foreach ($challenges as $challenge) {
        if ($challenge['id'] == $challengeId) {
            return $challenge;
        }
    }
    
    return null;
}
/**
 * Define the network levels and solutions
 */
function getNetworkLevel($level) {
    $levels = [
        1 => [
            'title' => 'Basic Network Setup',
            'description' => 'Connect the client computers to the router to establish a basic home network.',
            'devices' => [
                'router1' => ['type' => 'router', 'x' => 400, 'y' => 200, 'ip' => '192.168.1.1'],
                'pc1' => ['type' => 'computer', 'x' => 200, 'y' => 100, 'ip' => '192.168.1.2'],
                'pc2' => ['type' => 'computer', 'x' => 200, 'y' => 300, 'ip' => '192.168.1.3'],
                'laptop1' => ['type' => 'laptop', 'x' => 600, 'y' => 100, 'ip' => '192.168.1.4'],
                'laptop2' => ['type' => 'laptop', 'x' => 600, 'y' => 300, 'ip' => '192.168.1.5']
            ],
            'solution' => [
                ['source' => 'pc1', 'target' => 'router1'],
                ['source' => 'pc2', 'target' => 'router1'],
                ['source' => 'laptop1', 'target' => 'router1'],
                ['source' => 'laptop2', 'target' => 'router1']
            ],
            'points' => 100
        ],
        2 => [
            'title' => 'Internet Connection',
            'description' => 'Connect the router to the modem to establish an internet connection for your network.',
            'devices' => [
                'modem' => ['type' => 'modem', 'x' => 400, 'y' => 100, 'ip' => '203.0.113.1'],
                'router1' => ['type' => 'router', 'x' => 400, 'y' => 250, 'ip' => '192.168.1.1'],
                'pc1' => ['type' => 'computer', 'x' => 200, 'y' => 300, 'ip' => '192.168.1.2'],
                'pc2' => ['type' => 'computer', 'x' => 600, 'y' => 300, 'ip' => '192.168.1.3'],
                'server' => ['type' => 'server', 'x' => 400, 'y' => 400, 'ip' => '192.168.1.4']
            ],
            'solution' => [
                ['source' => 'router1', 'target' => 'modem'],
                ['source' => 'pc1', 'target' => 'router1'],
                ['source' => 'pc2', 'target' => 'router1'],
                ['source' => 'server', 'target' => 'router1']
            ],
            'points' => 150
        ],
        3 => [
            'title' => 'Office Network with Switch',
            'description' => 'Create a small office network using a switch to connect multiple computers to a router.',
            'devices' => [
                'router1' => ['type' => 'router', 'x' => 400, 'y' => 100, 'ip' => '192.168.1.1'],
                'switch1' => ['type' => 'switch', 'x' => 400, 'y' => 250, 'ip' => ''],
                'pc1' => ['type' => 'computer', 'x' => 200, 'y' => 350, 'ip' => '192.168.1.2'],
                'pc2' => ['type' => 'computer', 'x' => 350, 'y' => 350, 'ip' => '192.168.1.3'],
                'pc3' => ['type' => 'computer', 'x' => 450, 'y' => 350, 'ip' => '192.168.1.4'],
                'pc4' => ['type' => 'computer', 'x' => 600, 'y' => 350, 'ip' => '192.168.1.5'],
                'printer' => ['type' => 'printer', 'x' => 600, 'y' => 200, 'ip' => '192.168.1.6']
            ],
            'solution' => [
                ['source' => 'switch1', 'target' => 'router1'],
                ['source' => 'pc1', 'target' => 'switch1'],
                ['source' => 'pc2', 'target' => 'switch1'],
                ['source' => 'pc3', 'target' => 'switch1'],
                ['source' => 'pc4', 'target' => 'switch1'],
                ['source' => 'printer', 'target' => 'switch1']
            ],
            'points' => 200
        ]
    ];
    
    return $levels[$level] ?? $levels[1];
}

/**
 * Check if the network solution is correct
 */
function checkNetworkSolution($level, $connections) {
    $levelData = getNetworkLevel($level);
    $solution = $levelData['solution'];
    
    // Check if all required connections are present
    foreach ($solution as $requiredConnection) {
        $found = false;
        foreach ($connections as $userConnection) {
            if (
                ($userConnection['source'] === $requiredConnection['source'] && 
                 $userConnection['target'] === $requiredConnection['target']) || 
                ($userConnection['source'] === $requiredConnection['target'] && 
                 $userConnection['target'] === $requiredConnection['source'])
            ) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return [
                'success' => false,
                'message' => "Missing connection between {$requiredConnection['source']} and {$requiredConnection['target']}"
            ];
        }
    }
    
    // Check if there are any extra connections that shouldn't be there
    foreach ($connections as $userConnection) {
        $valid = false;
        foreach ($solution as $requiredConnection) {
            if (
                ($userConnection['source'] === $requiredConnection['source'] && 
                 $userConnection['target'] === $requiredConnection['target']) || 
                ($userConnection['source'] === $requiredConnection['target'] && 
                 $userConnection['target'] === $requiredConnection['source'])
            ) {
                $valid = true;
                break;
            }
        }
        
        if (!$valid) {
            return [
                'success' => false,
                'message' => "Invalid connection between {$userConnection['source']} and {$userConnection['target']}"
            ];
        }
    }
    
    // All connections are correct
    return [
        'success' => true,
        'points' => $levelData['points'],
        'message' => 'Network configured correctly!'
    ];
}
?> 