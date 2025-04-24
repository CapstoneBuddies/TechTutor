<?php    
    include 'config.php';
    global $pdo;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design Dynamo - Gaming Academy</title>
    <!-- Favicons -->
    <link href="<?php echo BASE; ?>assets/img/stand_alone_logo.png" rel="icon">
    <link href="<?php echo BASE; ?>assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <link rel="stylesheet" href="<?php echo BASE; ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <style>
        /* General Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #2c2c2c;
            color: #fff;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* Header */
        .app-header {
            background-color: #1e1e1e;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #444;
        }
        .app-header .title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .app-actions button, .app-actions select {
            background-color: #444;
            color: #fff;
            border: none;
            padding: 8px 15px;
            margin: 0 5px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }
        .app-actions button:hover, .app-actions select:hover {
            background-color: #555;
        }

        /* Main Container */
        .main-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* Toolbar */
        .toolbar {
            background-color: #1e1e1e;
            width: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 0;
            border-right: 1px solid #444;
        }
        .tool-btn {
            background-color: #444;
            color: #fff;
            border: none;
            padding: 10px;
            margin: 5px 0;
            cursor: pointer;
            border-radius: 4px;
            width: 60px;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .tool-btn:hover {
            background-color: #555;
        }
        .tool-btn.active {
            background-color: #007bff;
        }
        .tool-btn img, .tool-btn svg {
            width: 24px;
            height: 24px;
        }
        .tooltip-text {
            visibility: hidden;
            width: 120px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 4px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 10px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .tool-btn:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* Properties Panel */
        .properties-panel {
            background-color: #1e1e1e;
            width: 250px;
            padding: 20px;
            border-left: 1px solid #444;
        }
        .properties-panel h3 {
            font-size: 16px;
            margin-bottom: 15px;
        }
        .property-group {
            margin-bottom: 20px;
        }
        .property-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .property-row label {
            flex: 1;
        }
        .property-row input, .property-row select {
            flex: 2;
            background-color: #333;
            color: #fff;
            border: 1px solid #555;
            padding: 5px;
            border-radius: 3px;
        }
        .color-preview {
            width: 20px;
            height: 20px;
            border: 1px solid #fff;
            display: inline-block;
            margin-left: 10px;
        }

        /* Canvas Area */
        .canvas-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #333;
            position: relative;
            overflow: hidden;
        }
        .canvas-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: auto;
        }
        canvas {
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }

        /* Status Bar */
        .status-bar {
            background-color: #1e1e1e;
            padding: 5px 10px;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #444;
        }

        /* Challenge Panel */
        .challenge-panel {
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            padding: 20px;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        .challenge-panel.active {
            transform: translateX(0);
        }
        .challenge-panel h2 {
            font-size: 20px;
            margin-bottom: 15px;
        }
        .challenge-card {
            background-color: #333;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .challenge-card:hover {
            transform: translateY(-3px);
            background-color: #444;
        }
        .challenge-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
        }
        .challenge-card .difficulty {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .difficulty.beginner {
            background-color: #28a745;
        }
        .difficulty.intermediate {
            background-color: #ffc107;
            color: #333;
        }
        .difficulty.advanced {
            background-color: #dc3545;
        }
        .challenge-card p {
            font-size: 14px;
            margin-bottom: 10px;
        }
        .criteria-list {
            font-size: 12px;
            list-style-type: none;
            padding-left: 0;
        }
        .criteria-list li {
            margin-bottom: 5px;
        }
        .criteria-list li:before {
            content: "•";
            margin-right: 5px;
            color: #007bff;
        }
        .close-panel {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
        }
        .challenge-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        .challenge-actions button {
            flex: 1;
            margin: 0 5px;
            padding: 8px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
        }
        .modal-container {
            background-color: #333;
            border-radius: 5px;
            width: 90%;
            max-width: 500px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .modal-header h2 {
            font-size: 18px;
            margin: 0;
        }
        .modal-close {
            background: none;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
        }
        .modal-body {
            margin-bottom: 20px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
        }
        .modal-footer button {
            padding: 8px 15px;
            margin-left: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #007bff;
            color: #fff;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: #fff;
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 4px;
            color: #fff;
            z-index: 1001;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transform: translateY(-100px);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
        }
        .notification.success {
            background-color: #28a745;
        }
        .notification.error {
            background-color: #dc3545;
        }
        .notification.info {
            background-color: #17a2b8;
        }
        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="app-header">
        <div class="title">
            <a href="./" style="color: #fff; text-decoration: none; margin-right: 15px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
                Back
            </a>
            UX Design Studio
        </div>
        <div class="app-actions">
            <button id="new-btn">New</button>
            <button id="save-btn">Save</button>
            <button id="challenges-btn">Challenges</button>
            <button id="submit-btn">Submit Design</button>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Toolbar -->
        <div class="toolbar">
            <button class="tool-btn active" id="brush-tool">
                <img src="<?php echo GAME_IMG; ?>icons/brush.svg" alt="Brush">
                <span class="tooltip-text">Brush Tool (B)</span>
            </button>
            <button class="tool-btn" id="eraser-tool">
                <img src="<?php echo GAME_IMG; ?>icons/eraser.svg" alt="Eraser">
                <span class="tooltip-text">Eraser (E)</span>
            </button>
            <button class="tool-btn" id="rect-tool">
                <img src="<?php echo GAME_IMG; ?>icons/rectangle.svg" alt="Rectangle">
                <span class="tooltip-text">Rectangle (R)</span>
            </button>
            <button class="tool-btn" id="circle-tool">
                <img src="<?php echo GAME_IMG; ?>icons/circle.svg" alt="Circle">
                <span class="tooltip-text">Circle (C)</span>
            </button>
            <button class="tool-btn" id="text-tool">
                <img src="<?php echo GAME_IMG; ?>icons/text.svg" alt="Text">
                <span class="tooltip-text">Text Tool (T)</span>
            </button>
            <button class="tool-btn" id="eyedropper-tool">
                <img src="<?php echo GAME_IMG; ?>icons/eyedropper.svg" alt="Color Picker">
                <span class="tooltip-text">Color Picker (I)</span>
            </button>
            <button class="tool-btn" id="layers-tool">
                <img src="<?php echo GAME_IMG; ?>icons/layers.svg" alt="Layers">
                <span class="tooltip-text">Layers (L)</span>
            </button>
        </div>

        <!-- Canvas Area -->
        <div class="canvas-area">
            <div class="canvas-container">
                <canvas id="design-canvas" width="800" height="600"></canvas>
            </div>
            <div class="status-bar">
                <div class="position">Position: <span id="position-display">0, 0</span></div>
                <div class="zoom">Zoom: <span id="zoom-display">100%</span></div>
                <div class="active-tool">Tool: <span id="tool-display">Brush</span></div>
            </div>

            <!-- Challenge Panel (Hidden by Default) -->
            <div class="challenge-panel" id="challenge-panel">
                <button class="close-panel">&times;</button>
                <h2>Design Challenges</h2>
                <div id="challenge-list">
                    <!-- Challenges will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Properties Panel -->
        <div class="properties-panel">
            <h3>Properties</h3>
            <div class="property-group">
                <h4>Brush</h4>
                <div class="property-row">
                    <label for="brush-size">Size:</label>
                    <input type="range" id="brush-size" min="1" max="50" value="5">
                    <span id="brush-size-value">5</span>
                </div>
                <div class="property-row">
                    <label for="brush-color">Color:</label>
                    <input type="color" id="brush-color" value="#000000">
                    <div class="color-preview" id="color-preview" style="background-color: #000000;"></div>
                </div>
                <div class="property-row">
                    <label for="brush-opacity">Opacity:</label>
                    <input type="range" id="brush-opacity" min="0" max="100" value="100">
                    <span id="opacity-value">100%</span>
                </div>
            </div>
            <div class="property-group">
                <h4>Shape</h4>
                <div class="property-row">
                    <label for="shape-fill">Fill:</label>
                    <input type="checkbox" id="shape-fill" checked>
                </div>
                <div class="property-row">
                    <label for="shape-stroke">Stroke:</label>
                    <input type="checkbox" id="shape-stroke" checked>
                </div>
                <div class="property-row">
                    <label for="stroke-width">Width:</label>
                    <input type="range" id="stroke-width" min="1" max="20" value="2">
                    <span id="stroke-width-value">2</span>
                </div>
            </div>
            <div class="property-group">
                <h4>Text</h4>
                <div class="property-row">
                    <label for="font-family">Font:</label>
                    <select id="font-family">
                        <option value="Arial">Arial</option>
                        <option value="Verdana">Verdana</option>
                        <option value="Times New Roman">Times New Roman</option>
                        <option value="Courier New">Courier New</option>
                        <option value="Georgia">Georgia</option>
                    </select>
                </div>
                <div class="property-row">
                    <label for="font-size">Size:</label>
                    <input type="number" id="font-size" min="8" max="72" value="16">
                </div>
                <div class="property-row">
                    <label for="font-bold">Bold:</label>
                    <input type="checkbox" id="font-bold">
                </div>
                <div class="property-row">
                    <label for="font-italic">Italic:</label>
                    <input type="checkbox" id="font-italic">
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal-overlay" id="submit-modal">
        <div class="modal-container">
            <div class="modal-header">
                <h2>Submit Design</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you ready to submit your design for the current challenge?</p>
                <p><strong>Challenge:</strong> <span id="modal-challenge-title"></span></p>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary modal-close">Cancel</button>
                <button class="btn-primary" id="confirm-submit">Submit</button>
            </div>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script src="<?php echo BASE; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        const canvas = document.getElementById('design-canvas');
        const ctx = canvas.getContext('2d');

        let painting = false;
        let tool = 'brush'; // Default tool
        let brushColor = document.getElementById('brush-color').value;
        let brushSize = document.getElementById('brush-size').value;
        let startX, startY;
        let originalImageData;

        // Start drawing
        canvas.addEventListener('mousedown', (e) => {
            painting = true;
            ctx.beginPath();
            ctx.moveTo(e.offsetX, e.offsetY);
            
            // For shapes, store the starting coordinates
            if (tool === 'rectangle' || tool === 'circle') {
                startX = e.offsetX;
                startY = e.offsetY;
            }
        });

        // Draw on the canvas
        canvas.addEventListener('mousemove', (e) => {
            document.getElementById('position-display').textContent = `${e.offsetX}, ${e.offsetY}`;
            
            // Draw while painting with brush
            if (painting && tool === 'brush') {
                ctx.lineTo(e.offsetX, e.offsetY);
                ctx.strokeStyle = brushColor;
                ctx.lineWidth = brushSize;
                ctx.lineCap = 'round';
                ctx.stroke();
            }
            
            // Eraser functionality
            if (painting && tool === 'eraser') {
                ctx.lineTo(e.offsetX, e.offsetY);
                ctx.strokeStyle = '#ffffff'; // White for eraser
                ctx.lineWidth = brushSize;
                ctx.lineCap = 'round';
                ctx.stroke();
            }
            
            // Live preview for rectangle
            if (painting && tool === 'rectangle') {
                // Create a live preview of the rectangle
                const width = e.offsetX - startX;
                const height = e.offsetY - startY;
                
                // Clear the canvas and redraw
                const imageData = originalImageData || ctx.getImageData(0, 0, canvas.width, canvas.height);
                ctx.putImageData(imageData, 0, 0);
                
                // Draw the new rectangle
                if (document.getElementById('shape-fill').checked) {
                    ctx.fillStyle = brushColor;
                    ctx.fillRect(startX, startY, width, height);
                }
                
                if (document.getElementById('shape-stroke').checked) {
                    ctx.strokeStyle = brushColor;
                    ctx.lineWidth = document.getElementById('stroke-width').value;
                    ctx.strokeRect(startX, startY, width, height);
                }
            }
            
            // Live preview for circle
            if (painting && tool === 'circle') {
                // Calculate radius based on distance
                const radius = Math.sqrt(Math.pow(e.offsetX - startX, 2) + Math.pow(e.offsetY - startY, 2));
                
                // Clear the canvas and redraw
                const imageData = originalImageData || ctx.getImageData(0, 0, canvas.width, canvas.height);
                ctx.putImageData(imageData, 0, 0);
                
                // Draw the new circle
                ctx.beginPath();
                ctx.arc(startX, startY, radius, 0, Math.PI * 2);
                
                if (document.getElementById('shape-fill').checked) {
                    ctx.fillStyle = brushColor;
                    ctx.fill();
                }
                
                if (document.getElementById('shape-stroke').checked) {
                    ctx.strokeStyle = brushColor;
                    ctx.lineWidth = document.getElementById('stroke-width').value;
                    ctx.stroke();
                }
                
                ctx.closePath();
            }
        });

        // Stop drawing
        canvas.addEventListener('mouseup', (e) => {
            painting = false;
            ctx.closePath();
            
            // For rectangle and circle, save the current state
            if (tool === 'rectangle' || tool === 'circle') {
                originalImageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            }
        });

        // Clear the canvas
        document.getElementById('new-btn').addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        });

        // Update brush color
        document.getElementById('brush-color').addEventListener('input', (e) => {
            brushColor = e.target.value;
        });

        // Update brush size
        document.getElementById('brush-size').addEventListener('input', (e) => {
            brushSize = e.target.value;
        });

        // Save the canvas as an image
        document.getElementById('save-btn').addEventListener('click', () => {
            const link = document.createElement('a');
            link.download = 'drawing.png';
            link.href = canvas.toDataURL();
            link.click();
        });

        // Tool Selection
        document.getElementById('brush-tool').addEventListener('click', () => {
            setActiveTool('brush', 'Brush');
        });

        document.getElementById('eraser-tool').addEventListener('click', () => {
            setActiveTool('eraser', 'Eraser');
        });

        document.getElementById('rect-tool').addEventListener('click', () => {
            setActiveTool('rectangle', 'Rectangle');
        });

        document.getElementById('circle-tool').addEventListener('click', () => {
            setActiveTool('circle', 'Circle');
        });

        document.getElementById('text-tool').addEventListener('click', () => {
            setActiveTool('text', 'Text');
        });

        document.getElementById('eyedropper-tool').addEventListener('click', () => {
            setActiveTool('eyedropper', 'Color Picker');
        });

        document.getElementById('layers-tool').addEventListener('click', () => {
            setActiveTool('layers', 'Layers');
        });

        // Helper function to set active tool
        function setActiveTool(toolName, displayName) {
            tool = toolName;
            
            // Update UI
            document.querySelectorAll('.tool-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(`${toolName}-tool`).classList.add('active');
            document.getElementById('tool-display').textContent = displayName;
        }

        // Implement the text tool
        let textInput = null;

        canvas.addEventListener('click', (e) => {
            if (tool === 'text') {
                if (!textInput) {
                    // Create text input element
                    textInput = document.createElement('input');
                    textInput.type = 'text';
                    textInput.style.position = 'absolute';
                    textInput.style.left = (canvas.offsetLeft + e.offsetX) + 'px';
                    textInput.style.top = (canvas.offsetTop + e.offsetY) + 'px';
                    textInput.style.zIndex = '1000';
                    textInput.style.backgroundColor = 'rgba(255, 255, 255, 0.8)';
                    textInput.style.border = '1px dashed #000';
                    document.querySelector('.canvas-area').appendChild(textInput);
                    textInput.focus();

                    // Handle text input
                    textInput.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter') {
                            const text = textInput.value;
                            const x = e.offsetX;
                            const y = e.offsetY;
                            
                            // Configure text style
                            let fontStyle = '';
                            if (document.getElementById('font-bold').checked) {
                                fontStyle += 'bold ';
                            }
                            if (document.getElementById('font-italic').checked) {
                                fontStyle += 'italic ';
                            }
                            
                            const fontSize = document.getElementById('font-size').value;
                            const fontFamily = document.getElementById('font-family').value;
                            
                            ctx.font = `${fontStyle}${fontSize}px ${fontFamily}`;
                            ctx.fillStyle = brushColor;
                            ctx.fillText(text, x, y);
                            
                            // Remove input element
                            textInput.remove();
                            textInput = null;
                        }
                    });
                }
            }
            
            // Eyedropper tool
            if (tool === 'eyedropper') {
                const pixel = ctx.getImageData(e.offsetX, e.offsetY, 1, 1).data;
                const rgbColor = `rgb(${pixel[0]}, ${pixel[1]}, ${pixel[2]})`;
                
                // Convert RGB to HEX for the color input
                const hexColor = rgbToHex(pixel[0], pixel[1], pixel[2]);
                document.getElementById('brush-color').value = hexColor;
                document.getElementById('color-preview').style.backgroundColor = hexColor;
                brushColor = hexColor;
                
                // Switch back to brush tool after picking color
                setActiveTool('brush', 'Brush');
            }
        });

        // RGB to Hex conversion helper
        function rgbToHex(r, g, b) {
            return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
        }

        // Update UI elements when adjusting properties
        document.getElementById('brush-size').addEventListener('input', (e) => {
            brushSize = e.target.value;
            document.getElementById('brush-size-value').textContent = e.target.value;
        });

        document.getElementById('brush-opacity').addEventListener('input', (e) => {
            const opacity = e.target.value;
            document.getElementById('opacity-value').textContent = `${opacity}%`;
            // Convert opacity percentage to alpha value (0-1)
            ctx.globalAlpha = opacity / 100;
        });

        document.getElementById('stroke-width').addEventListener('input', (e) => {
            document.getElementById('stroke-width-value').textContent = e.target.value;
        });

        // Handle challenge functionality
        document.getElementById('challenges-btn').addEventListener('click', () => {
            document.getElementById('challenge-panel').classList.add('active');
            loadChallenges();
        });

        document.querySelector('.close-panel').addEventListener('click', () => {
            document.getElementById('challenge-panel').classList.remove('active');
        });

        // Load challenges from the server
        function loadChallenges() {
            fetch('get-design-challenge')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        displayChallenges(data.challenges);
                    } else {
                        showNotification('Error loading challenges', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to connect to server', 'error');
                });
        }

        // Display challenges in the panel
        function displayChallenges(challenges) {
            const challengeList = document.getElementById('challenge-list');
            challengeList.innerHTML = '';

            challenges.forEach(challenge => {
                const card = document.createElement('div');
                card.className = 'challenge-card';
                card.dataset.id = challenge.id;

                const difficulty = document.createElement('div');
                difficulty.className = `difficulty ${challenge.difficulty}`;
                difficulty.textContent = challenge.difficulty.charAt(0).toUpperCase() + challenge.difficulty.slice(1);

                const title = document.createElement('h3');
                title.textContent = challenge.title;

                const description = document.createElement('p');
                description.textContent = challenge.description;

                const criteriaTitle = document.createElement('p');
                criteriaTitle.innerHTML = '<strong>Evaluation Criteria:</strong>';

                const criteriaList = document.createElement('ul');
                criteriaList.className = 'criteria-list';

                if (Array.isArray(challenge.criteria)) {
                    challenge.criteria.forEach(criterion => {
                        const item = document.createElement('li');
                        item.textContent = criterion;
                        criteriaList.appendChild(item);
                    });
                }

                const actions = document.createElement('div');
                actions.className = 'challenge-actions';
                
                const startBtn = document.createElement('button');
                startBtn.textContent = 'Start Challenge';
                startBtn.addEventListener('click', () => {
                    selectChallenge(challenge);
                });

                actions.appendChild(startBtn);
                card.appendChild(title);
                card.appendChild(difficulty);
                card.appendChild(description);
                card.appendChild(criteriaTitle);
                card.appendChild(criteriaList);
                card.appendChild(actions);

                challengeList.appendChild(card);
            });
        }

        // Selected challenge data
        let currentChallenge = null;

        // Handle challenge selection
        function selectChallenge(challenge) {
            currentChallenge = challenge;
            document.getElementById('challenge-panel').classList.remove('active');
            
            // Clear canvas for new challenge
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Show notification with challenge info
            showNotification(`Challenge started: ${challenge.title}`, 'info');
        }

        // Handle design submission
        document.getElementById('submit-btn').addEventListener('click', () => {
            if (!currentChallenge) {
                showNotification('Please select a challenge first', 'error');
                return;
            }
            
            const modal = document.getElementById('submit-modal');
            document.getElementById('modal-challenge-title').textContent = currentChallenge.title;
            modal.style.display = 'flex';
        });

        // Close modals when clicking close button
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.modal-overlay').forEach(modal => {
                    modal.style.display = 'none';
                });
            });
        });

        // Handle submission confirmation
        document.getElementById('confirm-submit').addEventListener('click', () => {
            const imageData = canvas.toDataURL();

            fetch('save-drawing', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `image=${encodeURIComponent(imageData)}&challenge_id=${currentChallenge.id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(data.message || 'Design submitted successfully!', 'success');
                    document.getElementById('submit-modal').style.display = 'none';
                } else {
                    showNotification(data.message || 'Error submitting design', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to connect to server', 'error');
            });
        });

        // Notification helper
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Prevent shortcuts when typing in text input
            if (textInput) return;
            
            switch(e.key.toLowerCase()) {
                case 'b': setActiveTool('brush', 'Brush'); break;
                case 'e': setActiveTool('eraser', 'Eraser'); break;
                case 'r': setActiveTool('rectangle', 'Rectangle'); break;
                case 'c': setActiveTool('circle', 'Circle'); break;
                case 't': setActiveTool('text', 'Text'); break;
                case 'i': setActiveTool('eyedropper', 'Color Picker'); break;
                case 'l': setActiveTool('layers', 'Layers'); break;
                case 's': 
                    if (e.ctrlKey) {
                        e.preventDefault();
                        document.getElementById('save-btn').click();
                    }
                    break;
            }
        });
    </script>
</body>
</html>