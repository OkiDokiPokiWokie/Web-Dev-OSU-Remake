<?php
// Include helpers and protect page
require_once 'includes/auth.php';
require_once 'includes/json_helpers.php';
require_login(); // Redirects to index.php if not logged in

// 1. Get URL parameters
$map_id = $_GET['map'] ?? '';
$difficulty_name = $_GET['difficulty'] ?? '';

// 2. Validate Map and Difficulty
$beatmaps = read_json('data/beatmaps.json');
$valid_map = false;
$folder_path = '';
$map_title = '';

foreach ($beatmaps as $song) {
    if ($song['id'] === $map_id) {
        foreach ($song['difficulties'] as $diff) {
            if ($diff['name'] === $difficulty_name) {
                $valid_map = true;
                $folder_path = $diff['folder'];
                $map_title = $song['title'];
                break 2;
            }
        }
    }
}

if (!$valid_map) {
    header("Location: /home.php");
    exit();
}

// 3. Get Previous Personal Best
$username = current_user();
$scores = read_json("data/scores/{$username}.json");
$score_key = "{$map_id}:{$difficulty_name}";
$pb_score = null;
$pb_accuracy = null;

if (isset($scores[$score_key])) {
    $pb_score = $scores[$score_key]['score'];
    $pb_accuracy = $scores[$score_key]['accuracy'];
}

// 4. Include the header
require_once 'includes/header.php';
?>

<style>
    /* Remove any body/page scroll while playing so the canvas feels fullscreen */
    body {
        overflow: hidden;
    }

    .game-container {
        display: flex;
        justify-content: center;
        align-items: center;
        /* Fill the full viewport minus the nav bar height */
        width: 100vw;
        height: calc(100vh - 60px);
        background: #000;
        margin: 0;
        padding: 0;
    }

    #osuCanvas {
        /* Lock the physical element to 16:9 */
        aspect-ratio: 16 / 9;
        width: 100%;
        height: 100%;
        object-fit: contain;

        border: 2px solid #3a3a45;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
        cursor: crosshair;

        /* Prevent the drag-to-select issue when clicking fast */
        user-select: none;
        -webkit-user-drag: none;
    }
</style>

<div class="game-container">
    <canvas id="osuCanvas" width="1920" height="1080" 
            style="background: linear-gradient(rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.75)), url('/<?php echo htmlspecialchars($folder_path); ?>/bg.jpg') no-repeat center center; background-size: cover;">
    </canvas>
</div>

<script>
    // Pass PHP variables into JS safely
    const beatmapFolder = <?php echo json_encode($folder_path); ?>;
    const mapTitle      = <?php echo json_encode($map_title); ?>;
    const difficultyName = <?php echo json_encode($difficulty_name); ?>;
    const pbScore       = <?php echo json_encode($pb_score); ?>;
    const pbAccuracy    = <?php echo json_encode($pb_accuracy); ?>;
</script>

<script type="module">
    import { GameEngine } from '/assets/js/game.js';

    document.addEventListener("DOMContentLoaded", () => {
        const game = new GameEngine(
            'osuCanvas',
            beatmapFolder,
            mapTitle,
            difficultyName,
            pbScore,
            pbAccuracy
        );
        game.init();
    });
</script>

<?php require_once 'includes/footer.php'; ?>