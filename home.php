<?php
// Include our helper functions (auth.php automatically calls session_start())
require_once 'includes/auth.php';
require_once 'includes/json_helpers.php';

// 1. Protect the page - redirect to index.php if not logged in
require_login();

// 2. Read the beatmaps data
$beatmaps = read_json('data/beatmaps.json');

// 3. Include the shared header (which contains the global navigation bar)
require_once 'includes/header.php';
?>

<style>
    .home-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 0 20px;
        color: #ffffff;
    }

    .home-container h1 {
        color: #ff66aa;
        text-align: center;
        margin-bottom: 30px;
    }

    .beatmap-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .beatmap-card {
        background: rgba(25, 25, 35, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .beatmap-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.6);
        border-color: rgba(255, 102, 170, 0.3);
    }

    .beatmap-card h2 {
        margin: 0 0 5px 0;
        font-size: 1.5em;
        color: #ffffff;
    }

    .beatmap-card h3 {
        margin: 0 0 20px 0;
        font-size: 1em;
        color: #888899;
        font-weight: 400;
    }

    .difficulties {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .play-btn {
        background: #2a2a35;
        color: #ffffff;
        text-decoration: none;
        padding: 10px 16px;
        border-radius: 6px;
        font-size: 0.9em;
        border: 1px solid #3a3a45;
        transition: all 0.2s ease;
    }

    .play-btn:hover {
        background: #ff66aa;
        border-color: #ff66aa;
        color: white;
    }
</style>

<div class="home-container">
    <h1>Select a Beatmap</h1>

    <?php if (empty($beatmaps)): ?>
        <div class="beatmap-card" style="text-align: center;">
            <p>No beatmaps available right now. Check back later!</p>
        </div>
    <?php else: ?>
        <div class="beatmap-list">
            <?php foreach ($beatmaps as $song): ?>
                <div class="beatmap-card">
                    <h2><?php echo htmlspecialchars($song['title']); ?></h2>
                    <h3>by <?php echo htmlspecialchars($song['artist']); ?></h3>

                    <div class="difficulties">
                        <?php foreach ($song['difficulties'] as $diff): ?>
                            <a href="/game.php?map=<?php echo urlencode($song['id']); ?>&difficulty=<?php echo urlencode($diff['name']); ?>" class="play-btn">
                                Play <?php echo htmlspecialchars($diff['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// 4. Include the shared footer
require_once 'includes/footer.php';
?>