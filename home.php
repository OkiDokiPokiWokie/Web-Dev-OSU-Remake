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

<h1 class="page-title">Select a Beatmap</h1>

<div class="beatmap-container">
    <?php if (empty($beatmaps)): ?>
        <div class="beatmap-card" style="justify-content: center; padding: 30px;">
            <p style="color: #888899; font-size: 1.1em; margin: 0;">No beatmaps available right now. Check back later!</p>
        </div>
    <?php else: ?>
        <?php foreach ($beatmaps as $song): ?>
            <div class="beatmap-card">

                <div class="beatmap-info">
                    <div class="beatmap-title"><?php echo htmlspecialchars($song['title']); ?></div>
                    <div class="beatmap-artist">by <?php echo htmlspecialchars($song['artist']); ?></div>
                </div>

                <div class="difficulties-list">
                    <?php foreach ($song['difficulties'] as $diff): ?>
                        <a href="/game.php?map=<?php echo urlencode($song['id']); ?>&difficulty=<?php echo urlencode($diff['name']); ?>" class="difficulty-btn">
                            <span>▶</span> <?php echo htmlspecialchars($diff['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
// 4. Include the shared footer
require_once 'includes/footer.php';
?>