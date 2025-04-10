<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Assembly Game</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            background-color: #2c2c2c;
            color: #fff;
        }
        #sidebar {
            width: 200px;
            background-color: #1e1e1e;
            padding: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-right: 1px solid #444;
        }
        #sidebar h3 {
            margin-bottom: 10px;
        }
        #sidebar .component {
            background-color: #444;
            color: #fff;
            border: none;
            padding: 10px;
            margin: 10px 0;
            cursor: grab;
            border-radius: 3px;
            text-align: center;
            width: 100%;
        }
        #sidebar .component:hover {
            background-color: #555;
        }
        #canvas-container {
            flex: 1;
            position: relative;
            background-color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #pc-case {
            width: 600px;
            height: 400px;
            background-image: url('pc-case.png'); /* Replace with an image of a PC case */
            background-size: cover;
            position: relative;
        }
        .slot {
            position: absolute;
            border: 2px dashed #fff;
            border-radius: 5px;
        }
        .feedback {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #444;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            display: none;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar">
        <h3>Components</h3>
        <img src="cpu.png" class="component" draggable="true" id="cpu" alt="CPU" style="width: 100%; margin-bottom: 10px;">
        <img src="gpu.png" class="component" draggable="true" id="gpu" alt="GPU" style="width: 100%; margin-bottom: 10px;">
        <img src="ram.png" class="component" draggable="true" id="ram" alt="RAM" style="width: 100%; margin-bottom: 10px;">
        <img src="motherboard.png" class="component" draggable="true" id="motherboard" alt="Motherboard" style="width: 100%; margin-bottom: 10px;">
        <img src="psu.png" class="component" draggable="true" id="psu" alt="Power Supply" style="width: 100%; margin-bottom: 10px;">
        <img src="storage.png" class="component" draggable="true" id="storage" alt="Storage" style="width: 100%; margin-bottom: 10px;">
        <img src="cooler.png" class="component" draggable="true" id="cooler" alt="Cooler" style="width: 100%; margin-bottom: 10px;">
        <img src="case-fan.png" class="component" draggable="true" id="case-fan" alt="Case Fan" style="width: 100%; margin-bottom: 10px;">
    </div>

    <!-- Canvas -->
    <div id="canvas-container">
        <div id="pc-case">
            <div class="slot" id="cpu-slot" style="top: 50px; left: 200px; width: 50px; height: 50px;"></div>
            <div class="slot" id="gpu-slot" style="top: 150px; left: 100px; width: 100px; height: 50px;"></div>
            <div class="slot" id="ram-slot" style="top: 100px; left: 300px; width: 50px; height: 150px;"></div>
            <div class="slot" id="motherboard-slot" style="top: 200px; left: 200px; width: 200px; height: 150px;"></div>
            <div class="slot" id="psu-slot" style="top: 350px; left: 50px; width: 150px; height: 50px;"></div>
            <div class="slot" id="storage-slot" style="top: 300px; left: 400px; width: 100px; height: 50px;"></div>
            <div class="slot" id="cooler-slot" style="top: 50px; left: 400px; width: 50px; height: 50px;"></div>
            <div class="slot" id="case-fan-slot" style="top: 250px; left: 500px; width: 50px; height: 50px;"></div>
        </div>
        <div class="feedback" id="feedback"></div>
    </div>

    <script>
        const components = document.querySelectorAll('.component');
        const slots = document.querySelectorAll('.slot');
        const feedback = document.getElementById('feedback');

        // Drag and Drop Events
        components.forEach(component => {
            component.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text', e.target.id);
            });
        });

        slots.forEach(slot => {
            slot.addEventListener('dragover', (e) => {
                e.preventDefault();
            });

            slot.addEventListener('drop', (e) => {
                e.preventDefault();
                const componentId = e.dataTransfer.getData('text');
                const component = document.getElementById(componentId);

                // Check if the component matches the slot
                if (slot.id === `${componentId}-slot`) {
                    slot.style.backgroundColor = '#0f0'; // Highlight correct placement
                    feedback.textContent = `${componentId.toUpperCase()} placed correctly!`;
                    feedback.style.display = 'block';
                    setTimeout(() => feedback.style.display = 'none', 2000);

                    // Move the component to the slot
                    slot.appendChild(component);
                    component.setAttribute('draggable', 'false'); // Disable dragging after placement
                } else {
                    feedback.textContent = `Incorrect placement for ${componentId.toUpperCase()}.`;
                    feedback.style.display = 'block';
                    setTimeout(() => feedback.style.display = 'none', 2000);
                }
            });
        });
    </script>
</body>
</html>