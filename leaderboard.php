<?php
//forgot to commit this part of the setup, this is commit: #09-View-Leaderboard

// Include helpers and protect page
require_once 'includes/auth.php';
require_once 'includes/json_helpers.php';
require_login();

// 1. Load beatmap data to look up real song titles and build the filter dropdown
$beatmaps = read_json('data/beatmaps.json');
$beatmap_titles = [];
foreach ($beatmaps as $b) {
    $beatmap_titles[$b['id']] = $b['title'];
}

// 2. Scan all score files in the data/scores/ directory
$all_scores = [];
$score_files = glob('data/scores/*.json');

foreach ($score_files as $file) {
    // The filename is the username (e.g., gage.json -> gage)
    $username = basename($file, '.json');
    $user_scores = read_json($file);

    foreach ($user_scores as $key => $data) {
        // Split the key "song_id:Difficulty"
        list($song_id, $difficulty) = explode(':', $key);
        $title = $beatmap_titles[$song_id] ?? $song_id; // Fallback to ID if map was deleted

        $all_scores[] = [
            'username' => $username,
            'song_id' => $song_id,
            'title' => $title,
            'difficulty' => $difficulty,
            'score' => $data['score'],
            'accuracy' => $data['accuracy'],
            'submitted_at' => $data['submitted_at']
        ];
    }
}

// 3. Handle optional map filtering
$selected_map = $_GET['map_filter'] ?? '';
if ($selected_map !== '') {
    $all_scores = array_filter($all_scores, function($s) use ($selected_map) {
        return $s['song_id'] === $selected_map;
    });
}

// 4. Sort the combined list by score descending (highest first)
usort($all_scores, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

// 5. Include header
require_once 'includes/header.php';
?>

<style>
    .leaderboard-container {
        max-width: 1050px;
        margin: 40px auto;
        padding: 0 20px;
        color: #ffffff;
    }

    .leaderboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(255, 102, 170, 0.3);
        padding-bottom: 15px;
    }

    .leaderboard-header h1 {
        color: #ff66aa;
        margin: 0;
        font-size: 2.2em;
    }

    .filter-form select {
        background: #1a1a24;
        color: #ffffff;
        border: 1px solid #3a3a45;
        padding: 10px 15px;
        border-radius: 6px;
        font-size: 1em;
        outline: none;
        cursor: pointer;
    }

    .filter-form select:focus {
        border-color: #ff66aa;
    }

    .scores-table {
        width: 100%;
        border-collapse: collapse;
        background: rgba(25, 25, 35, 0.95);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
    }

    .scores-table th, .scores-table td {
        padding: 16px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .scores-table th {
        background: rgba(0, 0, 0, 0.3);
        color: #888899;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85em;
        letter-spacing: 1px;
    }

    .scores-table tr:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    /* Podium Highlighting for Top 3 */
    .pos-1 { color: #ffd700; font-weight: bold; font-size: 1.2em; } /* Gold */
    .pos-2 { color: #c0c0c0; font-weight: bold; font-size: 1.1em; } /* Silver */
    .pos-3 { color: #cd7f32; font-weight: bold; font-size: 1.05em; } /* Bronze */

    .player-link {
        color: #ffffff;
        text-decoration: none;
        font-weight: bold;
        transition: color 0.2s ease;
    }

    .player-link:hover {
        color: #ff66aa;
    }

    .rank-cell {
        font-weight: bold;
        font-size: 1.2em;
    }

    .rank-SS { color: #silver; text-shadow: 0 0 5px #ffffff; }
    .rank-S { color: #ffd700; }
    .rank-A { color: #32cd32; }
    .rank-B { color: #1e90ff; }
    .rank-C { color: #ff8c00; }
    .rank-D { color: #ff4d4d; }

    .empty-state {
        text-align: center;
        padding: 50px;
        color: #888899;
        font-style: italic;
        background: rgba(25, 25, 35, 0.95);
        border-radius: 8px;
    }
</style>

<div class="leaderboard-container">
    <div class="leaderboard-header">
        <h1>Global Leaderboard</h1>

        <form class="filter-form" method="GET" action="leaderboard.php">
            <select name="map_filter" onchange="this.form.submit()">
                <option value="">-- All Maps --</option>
                <?php foreach ($beatmaps as $b): ?>
                    <option value="<?php echo htmlspecialchars($b['id']); ?>" <?php if ($selected_map === $b['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($b['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (empty($all_scores)): ?>
        <div class="empty-state">
            No scores have been set yet. Be the first!
        </div>
    <?php else: ?>
        <table class="scores-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Player</th>
                    <th>Map</th>
                    <th>Difficulty</th>
                    <th>Score</th>
                    <th>Accuracy</th>
                    <th>Rank</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $position = 1;
                foreach ($all_scores as $entry): 
                    $rank = get_rank($entry['accuracy']);
                    // Assign special classes to the top 3 positions
                    $pos_class = $position <= 3 ? "pos-{$position}" : "";
                ?>
                    <tr>
                        <td class="<?php echo $pos_class; ?>">#<?php echo $position; ?></td>
                        <td>
                            <a href="/profile.php?user=<?php echo urlencode($entry['username']); ?>" class="player-link">
                                <?php echo htmlspecialchars($entry['username']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($entry['title']); ?></td>
                        <td><?php echo htmlspecialchars($entry['difficulty']); ?></td>
                        <td style="font-weight: bold;"><?php echo number_format($entry['score']); ?></td>
                        <td><?php echo number_format($entry['accuracy'], 1); ?>%</td>
                        <td class="rank-cell rank-<?php echo $rank; ?>"><?php echo $rank; ?></td>
                        <td style="color: #888899; font-size: 0.9em;">
                            <?php echo date("M j, Y", strtotime($entry['submitted_at'])); ?>
                        </td>
                    </tr>
                <?php 
                $position++;
                endforeach; 
                ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>