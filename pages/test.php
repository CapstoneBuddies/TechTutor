<?php
    include '../backends/main.php';

    global $conn;

    // Get JSON
    $stmt = $conn->prepare("SELECT diagnostics FROM class WHERE class_id = 1");
    $stmt->bind_result($json);
    $stmt->execute();
    $stmt->fetch();
    $stmt->close(); // Close the statement after fetching

    $decoded = json_decode($json);
    $questions = $decoded->questions; // Access the questions property as an object

    echo "<h2>Questions:</h2>";
    foreach ($questions as $questionId => $questionData) {
        foreach ($questionData as $questionText => $options) {
            echo "<h3>Question ID: " . htmlspecialchars($questionId) . "</h3>";
            echo "<p><strong>" . htmlspecialchars($questionText) . "</strong></p>";
            echo "<ol type='a'>";
            foreach ($options as $optionLetter => $optionText) {
                echo "<li>" . htmlspecialchars($optionLetter) . ") " . htmlspecialchars($optionText) . "</li>";
            }
            echo "</ol>";
        }
        echo "<br>";
    }

    // If you also want to see the answers and proficiency criteria:
    echo "<h2>Answers:</h2>";
    $answers = $decoded->answers;
    foreach($answers as $questionNumber => $answer){
        echo "<b>".$questionNumber.": ".$answer."<b>";
        echo "<br/>";
    }
    echo "<br>";

    echo "<h2>Proficiency Criteria:</h2>";
    print_r($decoded->proficiency_criteria);
?>