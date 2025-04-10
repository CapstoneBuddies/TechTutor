// filepath: /php-game-project/php-game-project/assets/js/scripts.js
document.addEventListener('DOMContentLoaded', function() {
    const startButton = document.getElementById('start-game');
    const choiceButtons = document.querySelectorAll('.choice-button');
    const gameStatus = document.getElementById('game-status');

    startButton.addEventListener('click', function() {
        gameStatus.textContent = "Game Started! Make your choice:";
        choiceButtons.forEach(button => {
            button.style.display = 'inline-block';
        });
    });

    choiceButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userChoice = this.dataset.choice;
            gameStatus.textContent = `You chose: ${userChoice}. Let's see what happens next!`;
            // Add game logic here based on user choice
        });
    });
});