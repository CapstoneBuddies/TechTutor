<?php
// Define available challenges - can be expanded in the future
$challenges = [
    [
        "id" => 1,
        "name" => "Hello World",
        "description" => "Write code to print 'hello world'.",
        "starter_code" => "// Write your PHP code here\n// Your goal is to print 'hello world'\n\n// Example:\n// echo 'hello world';",
        "expected_output" => "hello world",
        "badge_name" => "Hello World Badge",
        "badge_image" => "assets/badges/hello_world.png"
    ],
    [
        "id" => 2,
        "name" => "Factorial Calculator",
        "description" => "Write a function to calculate the factorial of a number.",
        "starter_code" => "// Write your PHP code here\nfunction factorial(\$n) {\n    // Your code here\n}\n\n// Test the function\n\$n = 5;\necho factorial(\$n);",
        "expected_output" => "120",
        "badge_name" => "Factorial Master",
        "badge_image" => "assets/badges/factorial.png"
    ],
    [
        "id" => 3,
        "name" => "String Reversal",
        "description" => "Write a function to reverse a string.",
        "starter_code" => "// Write your PHP code here\nfunction reverseString(\$str) {\n    // Your code here\n}\n\n// Test the function\n\$str = 'hello';\necho reverseString(\$str);",
        "expected_output" => "olleh",
        "badge_name" => "String Wizard",
        "badge_image" => "assets/badges/string_reversal.png"
    ],
    [
        "id" => 4,
        "name" => "Palindrome Checker",
        "description" => "Create a function that checks if a string is a palindrome (reads the same backward as forward).",
        "starter_code" => "// Write a function to check if a string is a palindrome\nfunction isPalindrome(\$str) {\n    // Your code here\n}\n\n// Test with this word\n\$word = 'racecar';\necho isPalindrome(\$word) ? 'true' : 'false';",
        "expected_output" => "true",
        "badge_name" => "Palindrome Detective",
        "badge_image" => "assets/badges/palindrome.png"
    ],
    [
        "id" => 5,
        "name" => "FizzBuzz Challenge",
        "description" => "Write a program that prints numbers from 1 to 15. For multiples of 3, print 'Fizz' instead of the number. For multiples of 5, print 'Buzz'. For numbers that are multiples of both 3 and 5, print 'FizzBuzz'.",
        "starter_code" => "// Implement the FizzBuzz challenge for numbers 1-15\n\n// Your code here",
        "expected_output" => "1\n2\nFizz\n4\nBuzz\nFizz\n7\n8\nFizz\nBuzz\n11\nFizz\n13\n14\nFizzBuzz",
        "badge_name" => "FizzBuzz Champion",
        "badge_image" => "assets/badges/fizzbuzz.png"
    ],
    [
        "id" => 6,
        "name" => "Array Sum",
        "description" => "Write a function that calculates the sum of all elements in an array.",
        "starter_code" => "// Create a function to sum all elements in an array\nfunction arraySum(\$arr) {\n    // Your code here\n}\n\n// Test with this array\n\$numbers = [5, 10, 15, 20, 25];\necho arraySum(\$numbers);",
        "expected_output" => "75",
        "badge_name" => "Array Master",
        "badge_image" => "assets/badges/array_sum.png"
    ],
    [
        "id" => 7,
        "name" => "Prime Number Checker",
        "description" => "Create a function that checks if a number is prime.",
        "starter_code" => "// Write a function to check if a number is prime\nfunction isPrime(\$number) {\n    // Your code here\n}\n\n// Test with this number\n\$num = 29;\necho isPrime(\$num) ? 'true' : 'false';",
        "expected_output" => "true",
        "badge_name" => "Prime Detective",
        "badge_image" => "assets/badges/prime.png"
    ],
    [
        "id" => 8,
        "name" => "Find Maximum Value",
        "description" => "Write a function to find the maximum value in a nested array without using built-in max functions.",
        "starter_code" => "// Write a function to find the maximum value in a nested array\nfunction findMax(\$arr) {\n    // Your code here\n}\n\n// Test with this nested array\n\$nestedArray = [[3, 5], [8, 2], [1, [9, 4]]];\necho findMax(\$nestedArray);",
        "expected_output" => "9",
        "badge_name" => "Maximum Explorer",
        "badge_image" => "assets/badges/max_value.png"
    ],
    [
        "id" => 9,
        "name" => "Password Validator",
        "description" => "Create a function that validates if a password meets these criteria: at least 8 characters, contains at least one uppercase letter, one lowercase letter, one number, and one special character (!@#$%^&*).",
        "starter_code" => "// Create a function that validates passwords according to criteria\nfunction isValidPassword(\$password) {\n    // Your code here\n}\n\n// Test with this password\n\$pass = 'SecureP@ss1';\necho isValidPassword(\$pass) ? 'valid' : 'invalid';",
        "expected_output" => "valid",
        "badge_name" => "Security Expert",
        "badge_image" => "assets/badges/password.png"
    ],
    [
        "id" => 10, 
        "name" => "Caesar Cipher",
        "description" => "Implement a Caesar cipher encryption function that shifts each letter in a string by a given number of positions in the alphabet.",
        "starter_code" => "// Implement a Caesar cipher function\nfunction caesarCipher(\$text, \$shift) {\n    // Your code here\n}\n\n// Test with this text and shift value\n\$text = 'hello';\n\$shift = 3;\necho caesarCipher(\$text, \$shift);",
        "expected_output" => "khoor",
        "badge_name" => "Encryption Expert",
        "badge_image" => "assets/badges/cipher.png"
    ],
    [
        "id" => 11,
        "name" => "Anagram Checker",
        "description" => "Create a function that checks if two strings are anagrams of each other (contain the same characters in different order).",
        "starter_code" => "// Create a function to check if two strings are anagrams\nfunction areAnagrams(\$str1, \$str2) {\n    // Your code here\n}\n\n// Test with these strings\n\$string1 = 'listen';\n\$string2 = 'silent';\necho areAnagrams(\$string1, \$string2) ? 'true' : 'false';",
        "expected_output" => "true",
        "badge_name" => "Anagram Detective",
        "badge_image" => "assets/badges/anagram.png"
    ]
];
?> 