<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design Dynamo - Gaming Academy</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .canvas-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 20px 0;
        }
        canvas {
            border: 1px solid #ddd;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
            padding: 10px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tool-btn {
            padding: 8px 12px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tool-btn:hover, .tool-btn.active {
            background-color: #007bff;
            color: white;
            border-color: #0056b3;
        }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .modal-header h3 {
            margin: 0;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .challenge-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fff;
            transition: transform 0.2s;
        }
        .challenge-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .challenge-card h3 {
            margin-top: 0;
        }
        .difficulty {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            margin-bottom: 10px;
        }
        .beginner {
            background-color: #d4edda;
            color: #155724;
        }
        .intermediate {
            background-color: #fff3cd;
            color: #856404;
        }
        .advanced {
            background-color: #f8d7da;
            color: #721c24;
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            border-radius: 5px;
            color: white;
            z-index: 2000;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            display: none;
        }
        .info {
            background-color: #17a2b8;
        }
        .error {
            background-color: #dc3545;
        }
        .success {
            background-color: #28a745;
        }
        .challenge-actions {
            margin-top: 10px;
        }
        .challenges-list {
            max-height: 60vh;
            overflow-y: auto;
        }
        .completed-badge {
            background-color: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            display: inline-block;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Design Dynamo</h1>
        <p>Create beautiful UI designs for challenges to earn XP and badges!</p>
        
        <div class="canvas-container">
            <div class="toolbar">
                <button id="pencil-tool" class="tool-btn active">Pencil</button>
                <button id="eraser-tool" class="tool-btn">Eraser</button>
                <button id="line-tool" class="tool-btn">Line</button>
                <button id="rectangle-tool" class="tool-btn">Rectangle</button>
                <button id="circle-tool" class="tool-btn">Circle</button>
                <button id="text-tool" class="tool-btn">Text</button>
                <button id="color-picker" class="tool-btn">Color</button>
                <input type="color" id="color-selector" value="#000000">
                <button id="clear-canvas" class="tool-btn">Clear</button>
                <button id="challenges-btn" class="tool-btn">Challenges</button>
                <button id="submit-btn" class="btn-primary">Submit Design</button>
            </div>
            
            <div>Current Challenge: <span id="current-challenge">None Selected</span></div>
            
            <canvas id="drawing-canvas" width="800" height="600"></canvas>
        </div>
    </div>
    
    <!-- Challenges Modal -->
    <div id="challenges-modal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Design Challenges</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="challenges-list">
                    <!-- Challenges will be loaded dynamically -->
                    <div class="challenge-card">
                        <h3>Sample Design Challenge</h3>
                        <div class="difficulty beginner">beginner</div>
                        <div>XP Value: 100</div>
                        <div class="challenge-actions">
                            <button class="btn-select-challenge">Select</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Submit Modal -->
    <div id="submit-modal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Submit Your Design</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you ready to submit your design for the challenge?</p>
                <div class="button-group">
                    <button id="confirm-submit" class="btn-primary">Yes, Submit</button>
                    <button class="modal-close">No, Keep Working</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notification -->
    <div id="notification" class="notification"></div>
    
    <script>
        // Global variables
        let canvas = document.getElementById('drawing-canvas');
        let ctx = canvas.getContext('2d');
        let painting = false;
        let tool = 'pencil'; // Default tool
        let brushColor = '#000000'; // Default color
        let startX, startY;
        let originalImageData = null;
        let challenges = <?php echo json_encode($challengesData['challenges']); ?>;
        let currentChallenge = null;
        
        // Initialize canvas
        function initCanvas() {
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Set default styles
            ctx.lineWidth = 5;
            ctx.lineCap = 'round';
            ctx.strokeStyle = brushColor;
        }
        
        initCanvas();
        
        // Color picker
        document.getElementById('color-selector').addEventListener('input', (e) => {
            brushColor = e.target.value;
            ctx.strokeStyle = brushColor;
            ctx.fillStyle = brushColor;
        });
        
        // Tool selection
        document.getElementById('pencil-tool').addEventListener('click', () => setActiveTool('pencil', 'Pencil Tool'));
        document.getElementById('eraser-tool').addEventListener('click', () => setActiveTool('eraser', 'Eraser Tool'));
        document.getElementById('line-tool').addEventListener('click', () => setActiveTool('line', 'Line Tool'));
        document.getElementById('rectangle-tool').addEventListener('click', () => setActiveTool('rectangle', 'Rectangle Tool'));
        document.getElementById('circle-tool').addEventListener('click', () => setActiveTool('circle', 'Circle Tool'));
        document.getElementById('text-tool').addEventListener('click', () => setActiveTool('text', 'Text Tool'));
        
        // Clear canvas
        document.getElementById('clear-canvas').addEventListener('click', () => {
            if (confirm('Are you sure you want to clear the canvas?')) {
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.strokeStyle = brushColor;
            }
        });
        
        // Show challenges modal
        document.getElementById('challenges-btn').addEventListener('click', () => {
            document.getElementById('challenges-modal').style.display = 'flex';
            loadChallenges();
        });
        
        // Close modals
        document.querySelectorAll('.modal-close').forEach(closeBtn => {
            closeBtn.addEventListener('click', () => {
                document.querySelectorAll('.modal-overlay').forEach(modal => {
                    modal.style.display = 'none';
                });
            });
        });
        
        // Helper function to show notifications
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
        
        // Handle submission confirmation
        document.getElementById('confirm-submit').addEventListener('click', () => {
            document.getElementById('submit-modal').style.display = 'none';
            showNotification('Design submitted successfully!', 'success');
        });
        
        // Handle design submission
        document.getElementById('submit-btn').addEventListener('click', () => {
            if (!currentChallenge) {
                showNotification('Please select a challenge first', 'error');
                return;
            }
            
            document.getElementById('submit-modal').style.display = 'flex';
        });
        
        // Function to load challenges
        function loadChallenges() {
            const challengesList = document.querySelector('.challenges-list');
            if (challengesList) {
                challengesList.innerHTML = '';
                
                challenges.forEach(challenge => {
                    const card = document.createElement('div');
                    card.className = 'challenge-card';
                    card.dataset.id = challenge.challenge_id;
                    
                    // Determine difficulty class
                    let difficultyClass = 'beginner';
                    if (challenge.difficulty === 'intermediate') {
                        difficultyClass = 'intermediate';
                    } else if (challenge.difficulty === 'advanced') {
                        difficultyClass = 'advanced';
                    }
                    
                    card.innerHTML = `
                        <h3>${challenge.title}</h3>
                        <div class="difficulty ${difficultyClass}">${challenge.difficulty}</div>
                        <div>XP Value: ${challenge.xp_value || 100}</div>
                        <div class="challenge-actions">
                            <button class="btn-select-challenge">Select</button>
                        </div>
                    `;
                    
                    challengesList.appendChild(card);
                    
                    // Add click event to select button
                    card.querySelector('.btn-select-challenge').addEventListener('click', () => {
                        currentChallenge = challenge;
                        document.getElementById('current-challenge').textContent = challenge.title;
                        document.getElementById('challenges-modal').style.display = 'none';
                        showNotification(`Challenge "${challenge.title}" selected!`, 'info');
                    });
                });
            }
        }
        
        // Helper function to set active tool
        function setActiveTool(toolName, displayName) {
            tool = toolName;
            
            // Update UI
            document.querySelectorAll('.tool-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(`${toolName}-tool`).classList.add('active');
        }
        
        // Initialize on document load
        document.addEventListener('DOMContentLoaded', () => {
            loadChallenges();
        });
    </script>
</body>
</html>