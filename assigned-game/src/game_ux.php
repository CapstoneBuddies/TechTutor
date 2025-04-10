<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photoshop-Like Paint Tool</title>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #2c2c2c;
            color: #fff;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* Top Toolbar */
        #top-toolbar {
            background-color: #1e1e1e;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #444;
        }
        #top-toolbar button, #top-toolbar input {
            background-color: #444;
            color: #fff;
            border: none;
            padding: 10px 15px;
            margin: 0 5px;
            cursor: pointer;
            border-radius: 3px;
            font-size: 14px;
        }
        #top-toolbar button:hover {
            background-color: #555;
        }
        #top-toolbar input[type="color"] {
            padding: 5px;
            border-radius: 3px;
            border: none;
        }

        /* Sidebar */
        #sidebar {
            background-color: #1e1e1e;
            width: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 0;
            border-right: 1px solid #444;
        }
        #sidebar button {
            background-color: #444;
            color: #fff;
            border: none;
            padding: 10px;
            margin: 10px 0;
            cursor: pointer;
            border-radius: 3px;
            width: 60px;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #sidebar button:hover {
            background-color: #555;
        }
        #sidebar button img {
            width: 24px;
            height: 24px;
        }

        /* Canvas Container */
        #canvas-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #333;
        }
        canvas {
            border: 1px solid #000;
            background-color: #fff;
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }
        .tooltip .tooltip-text {
            visibility: hidden;
            width: 120px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%; /* Position above the button */
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Top Toolbar -->
    <div id="top-toolbar">
        <div>
            <button id="new-file">New</button>
            <button id="save">Save</button>
            <button id="save-db">Save to Database</button>
        </div>
        <div>
            <label for="color">Color:</label>
            <input type="color" id="color" value="#000000">
            <label for="size">Brush Size:</label>
            <input type="number" id="size" value="5" min="1" max="50">
        </div>
    </div>

    <!-- Sidebar -->
    <div id="sidebar">
        <div class="tooltip">
            <button id="brush-tool"><img src="icons/brush.png" alt="Brush"></button>
            <span class="tooltip-text">Brush Tool</span>
        </div>
        <div class="tooltip">
            <button id="eraser-tool"><img src="icons/eraser.png" alt="Eraser"></button>
            <span class="tooltip-text">Eraser Tool</span>
        </div>
        <div class="tooltip">
            <button id="rect-tool"><img src="icons/rectangle.png" alt="Rectangle"></button>
            <span class="tooltip-text">Rectangle Tool</span>
        </div>
        <div class="tooltip">
            <button id="circle-tool"><img src="icons/circle.png" alt="Circle"></button>
            <span class="tooltip-text">Circle Tool</span>
        </div>
        
    </div>

    <!-- Canvas Container -->
    <div id="canvas-container" style="margin-left: 100px;">
        <canvas id="paintCanvas" width="800" height="600"></canvas>
    </div>

    <script>
        const canvas = document.getElementById('paintCanvas');
        const ctx = canvas.getContext('2d');

        let painting = false;
        let tool = 'brush'; // Default tool
        let brushColor = document.getElementById('color').value;
        let brushSize = document.getElementById('size').value;

        // Start drawing
        canvas.addEventListener('mousedown', (e) => {
            painting = true;
            ctx.beginPath();
            ctx.moveTo(e.offsetX, e.offsetY);
        });

        // Draw on the canvas
        canvas.addEventListener('mousemove', (e) => {
            if (painting && tool === 'brush') {
                ctx.lineTo(e.offsetX, e.offsetY);
                ctx.strokeStyle = brushColor;
                ctx.lineWidth = brushSize;
                ctx.lineCap = 'round';
                ctx.stroke();
            }
        });

        // Stop drawing
        canvas.addEventListener('mouseup', () => {
            painting = false;
            ctx.closePath();
        });

        // Clear the canvas
        document.getElementById('new-file').addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        });

        // Update brush color
        document.getElementById('color').addEventListener('input', (e) => {
            brushColor = e.target.value;
        });

        // Update brush size
        document.getElementById('size').addEventListener('input', (e) => {
            brushSize = e.target.value;
        });

        // Save the canvas as an image
        document.getElementById('save').addEventListener('click', () => {
            const link = document.createElement('a');
            link.download = 'drawing.png';
            link.href = canvas.toDataURL();
            link.click();
        });

        // Save the canvas to the database
        document.getElementById('save-db').addEventListener('click', () => {
            const imageData = canvas.toDataURL();

            fetch('save_drawing.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `image=${encodeURIComponent(imageData)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Drawing saved successfully!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });

        // Tool Selection
        document.getElementById('brush-tool').addEventListener('click', () => {
            tool = 'brush';
        });

        document.getElementById('eraser-tool').addEventListener('click', () => {
            tool = 'eraser';
            ctx.strokeStyle = '#fff'; // Eraser uses white color
        });

        // Add rectangle tool
        document.getElementById('rect-tool').addEventListener('click', () => {
            tool = 'rectangle';
            canvas.addEventListener('mousedown', (e) => {
                if (tool === 'rectangle') {
                    const startX = e.offsetX;
                    const startY = e.offsetY;
                    canvas.addEventListener('mouseup', (e) => {
                        const width = e.offsetX - startX;
                        const height = e.offsetY - startY;
                        ctx.fillStyle = brushColor;
                        ctx.fillRect(startX, startY, width, height);
                    }, { once: true });
                }
            });
        });

        // Add circle tool
        document.getElementById('circle-tool').addEventListener('click', () => {
            tool = 'circle';
            canvas.addEventListener('mousedown', (e) => {
                if (tool === 'circle') {
                    const startX = e.offsetX;
                    const startY = e.offsetY;
                    canvas.addEventListener('mouseup', (e) => {
                        const radius = Math.sqrt(Math.pow(e.offsetX - startX, 2) + Math.pow(e.offsetY - startY, 2));
                        ctx.beginPath();
                        ctx.arc(startX, startY, radius, 0, Math.PI * 2);
                        ctx.fillStyle = brushColor;
                        ctx.fill();
                        ctx.closePath();
                    }, { once: true });
                }
            });
        });
    </script>
</body>
</html>