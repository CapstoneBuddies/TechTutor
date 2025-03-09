<?php require '../backends/config.php';?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trailhead Inspired</title>
    <style>
        body {
    font-family: sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
    color: #333;
}

header {
    background-color: #fff;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.header-content {
    display: flex;
    justify-content: space-between; /* If you want something in the header later */
    align-items: center;
}

main {
    padding: 20px;
}

.main-container {
    display: flex;
    gap: 20px;
}

.user-card {
    width: 250px;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.user-info {
    margin-bottom: 20px; /* Space below user info */
}

.progress {
    margin-top: 10px;  /* Space above progress details */
}

.content {
    flex: 1;
}

section {
    margin-bottom: 20px;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

h2 {
    margin-bottom: 10px;
}

.module-group {
    display: flex;
    gap: 20px;
}

.module {
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 8px;
    flex: 1; /* Make modules take equal width */
}

.module img {
    width: 50px;
    height: 50px;
    margin-bottom: 10px;
}

.module-details {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
}

.favorites {
    text-align: center;
}

.trail {
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 8px;
}

.trail-progress {
    display: flex;
    align-items: center;
    margin-top: 10px;
}

.progress-bar {
    width: 100px;
    height: 10px;
    background-color: #ddd;
    border-radius: 5px;
    margin-right: 10px;
}

.progress-value {
    height: 100%;
    background-color: #0074d9;
    border-radius: 5px;
}

button {
    background-color: #0074d9;
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

footer {
    text-align: center;
    padding: 10px;
    background-color: #333;
    color: #fff;
}
    </style>
</head>
<body>
    <header>
        <div class="header-content">
        </div>
    </header>

    <main>
        <div class="main-container">
            <aside class="user-card">
                <div class="user-info">
                    <p>Nice to see you, John Centh!</p>
                    <div class="progress">
                        <p>You have 2,525 points</p>
                        <p><?php echo SMTP_PASSWORD; ?></p>
                    </div>
                </div>
            </aside>
            <section class="content">
                <section class="explore">
                    <h2>Explore Agentforce</h2>
                    <div class="module-group">
                        <div class="module">
                            <img src="placeholder.png" alt="Module Icon">
                            <h3>Agentforce Key Components: Quick Look</h3>
                            <p>Learn how AI agents use LLMs and context to assist customers and human...</p>
                            <div class="module-details">
                                <p>+100 Points</p>
                                <p>~5 mins</p>
                            </div>
                        </div>
                        <div class="module">
                            <img src="placeholder.png" alt="Module Icon">
                            <h3>Agentforce for Service: Quick Look</h3>
                            <p>Help customers with next-gen AI agents that offer autonomous, natural...</p>
                            <div class="module-details">
                                <p>+100 Points</p>
                                <p>~5 mins</p>
                            </div>
                        </div>
                        <div class="module">
                            <img src="placeholder.png" alt="Module Icon">
                            <h3>Agentforce for Developers</h3>
                            <p>Use generative AI features like Dev Assistant to improve developer productivity.</p>
                            <div class="module-details">
                                <p>+300 Points</p>
                                <p>~15 mins</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="favorites">
                    <h2>Favorites</h2>
                    <p>There's nothing here yet</p>
                    <p>Add badges or trails to your favorites by clicking the star icon.</p>
                </section>

                <section class="jump-back-in">
                    <h2>Jump Back In</h2>
                    <div class="trail">
                        <h3>Protect Your Salesforce Data</h3>
                        <p>Learn how you and your users can work together to keep your data safe.</p>
                        <div class="trail-progress">
                            <p>+7,200 Points</p>
                            <div class="progress-bar">
                                <div class="progress-value" style="width: 13%;"></div>
                            </div>
                            <button>Continue</button>
                        </div>
                    </div>
                </section>
            </section>
        </div>
    </main>

    <footer>
    </footer>
</body>
</html>