<?php
// Include site header
require_once 'includes/header.php';
?>

<style>
    .checkpoints-container {
        max-width: 1500px; /* Massively widened to span more of the screen */
        width: 95%; /* Ensures it scales nicely and takes up horizontal space */
        margin: 40px auto;
        padding: 0 20px;
        color: #ffffff;
    }

    .checkpoints-header {
        margin-bottom: 40px;
        border-bottom: 1px solid rgba(255, 102, 170, 0.3);
        padding-bottom: 20px;
    }

    .checkpoints-header h1 {
        color: #ff66aa;
        margin: 0 0 10px 0;
        font-size: 2.5em;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .checkpoints-header p {
        color: #888899;
        margin: 0;
        font-size: 1.1em;
    }

    /* Grid Layout for Checkpoint Cards */
    .checkpoints-grid {
        display: grid;
        /* Forced to exactly 2 columns side-by-side */
        grid-template-columns: repeat(2, 1fr);
        gap: 40px; /* Increased gap to match the wider screen spread */
    }

    @media (max-width: 900px) {
        .checkpoints-grid {
            /* Drops to 1 column on smaller screens so it doesn't break */
            grid-template-columns: 1fr; 
        }
    }

    /* Card Styling matching site style */
    .checkpoint-card {
        background: rgba(25, 25, 35, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 30px; 
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .checkpoint-card:hover {
        transform: translateY(-4px);
        border-color: rgba(255, 102, 170, 0.4);
        box-shadow: 0 12px 24px rgba(255, 102, 170, 0.1);
    }

    .card-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
    }

    .checkpoint-badge {
        color: #ff66aa;
        font-weight: 700;
        background: rgba(255, 102, 170, 0.1);
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.85em;
        letter-spacing: 0.5px;
    }

    .checkpoint-date {
        font-size: 0.9em;
        color: #888899;
    }

    .checkpoint-name {
        margin: 0 0 15px 0;
        font-size: 1.35em;
        color: #ffffff;
        font-weight: 600;
    }

    /* Quote-style block for reflections */
    .checkpoint-reflection {
        color: #bbbbcc;
        line-height: 1.6;
        font-size: 1.05em; /* Bumped font size slightly for readability on wider cards */
        margin: 0 0 25px 0; 
        flex-grow: 1;
        font-style: italic;
        background: rgba(0, 0, 0, 0.2);
        padding: 20px; 
        border-radius: 8px;
        border-left: 3px solid #ff66aa;
    }

    /* GitHub Link Button Styling */
    .commit-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: #2a2a35;
        color: #ffffff;
        padding: 10px 16px;
        border-radius: 6px;
        font-size: 0.9em;
        text-decoration: none;
        border: 1px solid #3a3a45;
        width: fit-content;
        transition: all 0.2s ease;
        font-weight: 600;
    }

    .commit-link:hover {
        background: #ff66aa;
        border-color: #ff66aa;
        color: #ffffff;
    }
</style>

<div class="checkpoints-container">
    <div class="checkpoints-header">
        <h1><span>✦</span> Project Milestones & Checkpoints</h1>
        <p>A developmental timeline logging the creation and completion steps of our rhythm game web platform.</p>
    </div>

    <div class="checkpoints-grid">

        <div class="checkpoint-card">
            <div>
                <div class="card-meta">
                    <span class="checkpoint-badge">CHECKPOINT #01</span>
                    <span class="checkpoint-date">May 20, 2026</span>
                </div>
                <h3 class="checkpoint-name">Project Setup</h3>
                <p class="checkpoint-reflection">Set up the file structure and page routing.</p>
            </div>
            <a href="https://github.com/OkiDokiPokiWokie/Web-Dev-OSU-Remake/commit/30981744cf134c98e979f029b2515192a3ee4b9d" target="_blank" class="commit-link">View Commit Logs</a>
        </div>

        <div class="checkpoint-card">
            <div>
                <div class="card-meta">
                    <span class="checkpoint-badge">CHECKPOINT #02</span>
                    <span class="checkpoint-date">May 21, 2026</span>
                </div>
                <h3 class="checkpoint-name">Navigation & Routing</h3>
                <p class="checkpoint-reflection">Made the login page, tested the account creation and account login.</p>
            </div>
            <a href="https://github.com/OkiDokiPokiWokie/Web-Dev-OSU-Remake/commit/296df8b0ec2783a55c4be4d2d0d18be6f40504e0" target="_blank" class="commit-link">View Commit Logs</a>
        </div>

        <div class="checkpoint-card">
            <div>
                <div class="card-meta">
                    <span class="checkpoint-badge">CHECKPOINT #03</span>
                    <span class="checkpoint-date">May 22, 2026</span>
                </div>
                <h3 class="checkpoint-name">Home Page Mechanics</h3>
                <p class="checkpoint-reflection">Set up the home page, tested the nav bar showed "admin panel" link if user role="admin".</p>
            </div>
            <a href="https://github.com/OkiDokiPokiWokie/Web-Dev-OSU-Remake/commit/cf151a96e5d63f48d09143fa3631c0c40a76a1da" target="_blank" class="commit-link">View Commit Logs</a>
        </div>

        <div class="checkpoint-card">
            <div>
                <div class="card-meta">
                    <span class="checkpoint-badge">CHECKPOINT #04</span>
                    <span class="checkpoint-date">May 22, 2026</span>
                </div>
                <h3 class="checkpoint-name">User Profiles</h3>
                <p class="checkpoint-reflection">Tested profile.php visibility, and uploading a player profile image.</p>
            </div>
            <a href="https://github.com/OkiDokiPokiWokie/Web-Dev-OSU-Remake/commit/21ff65357b3f2db57f79a47fd57a435feb23eaba" target="_blank" class="commit-link">View Commit Logs</a>
        </div>

        <div class="checkpoint-card">
            <div>
                <div class="card-meta">
                    <span class="checkpoint-badge">CHECKPOINT #05</span>
                    <span class="checkpoint-date">May 22, 2026</span>
                </div>
                <h3 class="checkpoint-name">Game Engine Core</h3>
                <p class="checkpoint-reflection">Added the file reader and audio handler, will be able to test at checkpoint #7.</p>
            </div>
            <a href="https://github.com/OkiDokiPokiWokie/Web-Dev-OSU-Remake/commit/e10b004e808609612f95ad94119662735bc5b54e" target="_blank" class="commit-link">View Commit Logs</a>
        </div>

        <div class="checkpoint-card">
            <div>
                <div class="card-meta">
                    <span class="checkpoint-badge">CHECKPOINT #06</span>
                    <span class="checkpoint-date">May 22, 2026</span>
                </div>
                <h3 class="checkpoint-name">Game Loop Initialization</h3>
                <p class="checkpoint-reflection">Made the game loop Javascript, will test at Checkpoint #7.</p>
            </div>
            <a href="https://github.com/OkiDokiPokiWokie/Web-Dev-OSU-Remake/commit/8205810db75d62f493bdecf6ff67ac27b71d0c1f" target="_blank" class="commit-link">View Commit Logs</a>
        </div>

        <div class="checkpoint-card">
            <div>
                <div class="card-meta">
                    <span class="checkpoint-badge">CHECKPOINT #07</span>
                    <span class="checkpoint-date">May 25, 2026</span>
                </div>
                <h3 class="checkpoint-name">Play Game View</h3>
                <p class="checkpoint-reflection">Fully tested checkpoints 5 and 6. Had to rework the main game loop, the visuals page, and some styling to get it to work properly.</p>
            </div>
            <a href="https://github.com/OkiDokiPokiWokie/Web-Dev-OSU-Remake/commit/6e80bb68c72f36aee7a3c9021750aac1d1e72414" target="_blank" class="commit-link">View Commit Logs</a>
        </div>

        <div class="checkpoint-card">
            <div>
                <div class="card-meta">
                    <span class="checkpoint-badge">CHECKPOINT #08</span>
                    <span class="checkpoint-date">May 25, 2026</span>
                </div>
                <h3 class="checkpoint-name">Data Storage Serialization</h3>
                <p class="checkpoint-reflection">Saves data after each play to appropriate JSON data file.</p>
            </div>
            <a href="https://github.com/OkiDokiPokiWokie/Web-Dev-OSU-Remake/commit/187a2b35e7128954322b985d4740addbf0b85168" target="_blank" class="commit-link">View Commit Logs</a>
        </div>

        <div class="checkpoint-card">
            <div>
                <div class="card-meta">
                    <span class="checkpoint-badge">CHECKPOINT #09</span>
                    <span class="checkpoint-date">May 25, 2026</span>
                </div>
                <h3 class="checkpoint-name">Leaderboard Systems</h3>
                <p class="checkpoint-reflection">Made leaderboard with filter types as well as an overall comprehensive view.</p>
            </div>
            <a href="https://github.com/OkiDokiPokiWokie/Web-Dev-OSU-Remake/commit/dd29df8da3bf9a297931e7a423523002514a7648" target="_blank" class="commit-link">View Commit Logs</a>
        </div>

        <div class="checkpoint-card">
            <div>
                <div class="card-meta">
                    <span class="checkpoint-badge">CHECKPOINT #10</span>
                    <span class="checkpoint-date">May 25, 2026</span>
                </div>
                <h3 class="checkpoint-name">Admin Dashboard Wireframe</h3>
                <p class="checkpoint-reflection">Initial admin page set up, no working parts, just the visuals layout and interface buttons.</p>
            </div>
            <a href="https://github.com/OkiDokiPokiWokie/Web-Dev-OSU-Remake/commit/87c4b1666124d81c9c1c2bf2df4a3265af1f4fc3" target="_blank" class="commit-link">View Commit Logs</a>
        </div>

        <div class="checkpoint-card">
            <div>
                <div class="card-meta">
                    <span class="checkpoint-badge">CHECKPOINT #11</span>
                    <span class="checkpoint-date">May 27, 2026</span>
                </div>
                <h3 class="checkpoint-name">Admin Core Implementations</h3>
                <p class="checkpoint-reflection">Made the admin page features, fixed gameplay feature bug where the background image wouldn't show. Fixed bug with file uploading and file size limit errors.</p>
            </div>
            <a href="https://github.com/OkiDokiPokiWokie/Web-Dev-OSU-Remake/commit/f9286dd8754f0fb92e8a9607dab1e2df75128c81" target="_blank" class="commit-link">View Commit Logs</a>
        </div>

        <div class="checkpoint-card">
            <div>
                <div class="card-meta">
                    <span class="checkpoint-badge">CHECKPOINT #12</span>
                    <span class="checkpoint-date">May 28, 2026</span>
                </div>
                <h3 class="checkpoint-name">AI System Core</h3>
                <p class="checkpoint-reflection">Added in the AI overviews, refined the response metrics generation, and thoroughly tested script reflections.</p>
            </div>
            <a href="https://github.com/OkiDokiPokiWokie/Web-Dev-OSU-Remake/commit/8309baf1ead810e1a32fc5eaab61ecdb9ba0e05b" target="_blank" class="commit-link">View Commit Logs</a>
        </div>

    </div>
</div>

<?php
// Include site footer
require_once 'includes/footer.php';
?>