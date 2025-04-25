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

// The close-modal button has been removed, so we're removing this event listener
// document.getElementById('close-modal').addEventListener('click', function () {
//     document.getElementById('badge-modal').style.display = 'none';
// });

// Add functionality to close the modal when clicking outside of it or pressing Escape
document.addEventListener('click', function(event) {
    const modal = document.getElementById('badge-modal');
    const modalContent = document.querySelector('.badge-modal-content');
    if (modal && event.target === modal) {
        modal.style.display = 'none';
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('badge-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
});

document.getElementById('go-to-next-challenge').addEventListener('click', function () {
    if(nextChallengeId) {
    	window.location.href = `?challenge=${nextChallengeId}`;
    } else {
        window.location.href = './';
    }
});

document.getElementById('close-level-modal').addEventListener('click', function() {
    document.getElementById('level-up-modal').style.display = 'none';
});

// Load Monaco Editor - with guard to prevent duplicate initialization
if (typeof window.monacoAlreadyInitialized === 'undefined') {
    window.monacoAlreadyInitialized = true;
    require.config({ paths: { 'vs': BASE + 'assets/vendor/monaco-editor/min/vs' } });
    require(['vs/editor/editor.main'], function () {
        // Language starters
        const languageStarters = {
            php: `<?php \n\n ${(currentChallenge.starter_code)} \n\n ?>` || `<?php
    // Write your PHP code here
    
?>`,
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
}

// Run Code Button
document.getElementById('run-code').addEventListener('click', function () {
    const code = window.editor.getValue();
    const outputElement = document.getElementById('output');
    const language = document.getElementById('language-select').value;
    
    outputElement.textContent = "Running code...";

    fetch('execute', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `code=${encodeURIComponent(code)}&challenge_id=${currentChallenge.challenge_id}&language=${language}`
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
                document.getElementById('badge-image').src = GAME_IMG + 'badges/' + (currentChallenge.badge_image || "badges/goodjob.png");
                
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
            php: "<?php \n// Solution\necho 'hello world';\n ?>",
            javascript: "// Solution\nconsole.log('hello world');",
            cpp: "#include <iostream>\n\nint main() {\n    // Solution\n    std::cout << \"hello world\";\n    return 0;\n}",
            java: "public class Main {\n    public static void main(String[] args) {\n        // Solution\n        System.out.println(\"hello world\");\n    }\n}",
            python: "# Solution\nprint(\"hello world\")",
            csharp: "using System;\n\nclass Program {\n    static void Main() {\n        // Solution\n        Console.WriteLine(\"hello world\");\n    }\n}",
            ruby: "# Solution\nputs \"hello world\""
        },
        // Challenge 2: Factorial
        2: {
            php: "<?php\n// Solution\nfunction factorial($n) {\n    if ($n <= 1) return 1;\n    return $n * factorial($n - 1);\n}\n\n$n = 5;\necho factorial($n);\n?>",
            javascript: "// Solution\nfunction factorial(n) {\n    if (n <= 1) return 1;\n    return n * factorial(n - 1);\n}\n\nconst n = 5;\nconsole.log(factorial(n));",
            cpp: "#include <iostream>\n\nint factorial(int n) {\n    if (n <= 1) return 1;\n    return n * factorial(n - 1);\n}\n\nint main() {\n    int n = 5;\n    std::cout << factorial(n);\n    return 0;\n}",
            java: "public class Main {\n    public static void main(String[] args) {\n        int n = 5;\n        System.out.println(factorial(n));\n    }\n    \n    public static int factorial(int n) {\n        if (n <= 1) return 1;\n        return n * factorial(n - 1);\n    }\n}",
            python: "# Solution\ndef factorial(n):\n    if n <= 1:\n        return 1\n    return n * factorial(n - 1)\n\nn = 5\nprint(factorial(n))",
            csharp: "using System;\n\nclass Program {\n    static void Main() {\n        int n = 5;\n        Console.WriteLine(Factorial(n));\n    }\n    \n    static int Factorial(int n) {\n        if (n <= 1) return 1;\n        return n * Factorial(n - 1);\n    }\n}",
            ruby: "# Solution\ndef factorial(n)\n    return 1 if n <= 1\n    n * factorial(n - 1)\nend\n\nn = 5\nputs factorial(n)"
        },
        // Challenge 3: String Reversal
        3: {
            php: "<?php\n// Solution\nfunction reverseString($str) {\n    return strrev($str);\n}\n\n$str = 'hello';\necho reverseString($str);\n?>",
            javascript: "// Solution\nfunction reverseString(str) {\n    return str.split('').reverse().join('');\n}\n\nconst str = 'hello';\nconsole.log(reverseString(str));",
            cpp: "#include <iostream>\n#include <string>\n#include <algorithm>\n\nint main() {\n    std::string str = \"hello\";\n    std::reverse(str.begin(), str.end());\n    std::cout << str;\n    return 0;\n}",
            java: "public class Main {\n    public static void main(String[] args) {\n        String str = \"hello\";\n        StringBuilder sb = new StringBuilder(str);\n        System.out.println(sb.reverse().toString());\n    }\n}",
            python: "# Solution\ndef reverse_string(s):\n    return s[::-1]\n\nstr = \"hello\"\nprint(reverse_string(str))",
            csharp: "using System;\n\nclass Program {\n    static void Main() {\n        string str = \"hello\";\n        char[] charArray = str.ToCharArray();\n        Array.Reverse(charArray);\n        Console.WriteLine(new string(charArray));\n    }\n}",
            ruby: "# Solution\ndef reverse_string(str)\n    str.reverse\nend\n\nstr = \"hello\"\nputs reverse_string(str)"
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