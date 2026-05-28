<?php
// Include helper functions and protect the page
require_once 'includes/auth.php';
require_once 'includes/json_helpers.php';
require_once 'includes/groq_helpers.php'; // 1. Added Groq helper
require_login();

// 1. Determine which user profile to show
$target_username = $_GET['user'] ?? current_user();
$users = read_json('data/users.json');

$target_user = null;
foreach ($users as $user) {
    if ($user['username'] === $target_username) {
        $target_user = $user;
        break;
    }
}

// 2. Include header
require_once 'includes/header.php';

// 3. Handle User Not Found
if (!$target_user) {
    echo "<div class='profile-container' style='text-align:center;'><h2>User not found.</h2></div>";
    require_once 'includes/footer.php';
    exit();
}

// 4. Load scores and beatmap titles for the table
$scores = read_json("data/scores/{$target_username}.json");
$beatmaps = read_json('data/beatmaps.json');

// Create a quick lookup array for beatmap titles (id => title)
$beatmap_titles = [];
foreach ($beatmaps as $b) {
    $beatmap_titles[$b['id']] = $b['title'];
}

$is_own_profile = ($target_username === current_user());

// 5. SERVER-SIDE AI GENERATION: Pre-calculate metrics to keep the AI short and accurate
$system_prompt = "You are a casual, fun rhythm game companion analyzer. Provide a quick, bite-sized breakdown of the player's performance based on their stats. Do NOT write paragraphs, intros, or an essay. Output ONLY 3 to 4 short, punchy bullet points highlighting their best score, their lowest accuracy map, and their general average accuracy. Keep it casual, extremely brief, and game-focused.";

$user_prompt = "Player: {$target_username}\n";

if (empty($scores)) {
    $user_prompt .= "This player has no recorded scores yet. Ask them to click some circles first!";
} else {
    // Let's compute quick stats to feed the AI exactly what it needs
    $total_plays = count($scores);
    $total_acc = 0;
    $best_map = "";
    $best_score = -1;
    $worst_map = "";
    $worst_acc = 101;

    foreach ($scores as $key => $data) {
        list($song_id, $difficulty) = explode(':', $key);
        $title = $beatmap_titles[$song_id] ?? $song_id;
        $full_title = "{$title} [{$difficulty}]";

        $total_acc += $data['accuracy'];

        if ($data['score'] > $best_score) {
            $best_score = $data['score'];
            $best_map = $full_title;
        }
        if ($data['accuracy'] < $worst_acc) {
            $worst_acc = $data['accuracy'];
            $worst_map = $full_title;
        }
    }

    $avg_acc = round($total_acc / $total_plays, 1);

    $user_prompt .= "Stats Summary:\n";
    $user_prompt .= "- Total Maps Played: {$total_plays}\n";
    $user_prompt .= "- Average Accuracy: {$avg_acc}%\n";
    $user_prompt .= "- Best Performance: {$best_map} (Score: " . number_format($best_score) . ")\n";
    $user_prompt .= "- Hardest Time / Lowest Accuracy: {$worst_map} (Accuracy: {$worst_acc}%)\n";
}

// Execute the call while the page loads
$ai_summary = call_groq($user_prompt, $system_prompt);
?>

<style>
    .profile-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
        color: #ffffff;
    }

    .profile-header {
        background: rgba(25, 25, 35, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 30px;
        display: flex;
        align-items: center;
        gap: 30px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        margin-bottom: 30px;
    }

    .pfp-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }

    .pfp-image {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #ff66aa;
        box-shadow: 0 0 15px rgba(255, 102, 170, 0.3);
    }

    .upload-btn {
        background: #2a2a35;
        color: #ffffff;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.85em;
        border: 1px solid #3a3a45;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .upload-btn:hover {
        background: #ff66aa;
        border-color: #ff66aa;
    }

    .profile-info h1 {
        margin: 0 0 10px 0;
        font-size: 2.5em;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .admin-badge {
        background: #ff4d4d;
        color: white;
        font-size: 0.35em;
        padding: 4px 10px;
        border-radius: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        vertical-align: middle;
    }

    .profile-info p {
        margin: 5px 0;
        color: #888899;
        font-size: 1.1em;
    }

    /* AI Summary CSS */
    .ai-summary-box {
        background: rgba(25, 25, 35, 0.95);
        border: 1px solid #3a3a45;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
    }

    .ai-summary-box h3 {
        margin-top: 0;
        margin-bottom: 15px;
        color: #ffffff;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ai-summary-box p {
        color: #bbbbcc;
        line-height: 1.6;
        margin: 0;
        /* Critical: preserves the AI's bullet points line breaks cleanly */
        white-space: pre-line; 
    }

    .scores-section h2 {
        color: #ff66aa;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(255, 102, 170, 0.3);
        padding-bottom: 10px;
    }

    .scores-table {
        width: 100%;
        border-collapse: collapse;
        background: rgba(25, 25, 35, 0.95);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
    }

    .scores-table th, .scores-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .scores-table th {
        background: rgba(0, 0, 0, 0.2);
        color: #ff66aa;
        font-weight: 600;
    }

    .scores-table tr:hover {
        background: rgba(255, 255, 255, 0.02);
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

    .empty-scores {
        background: rgba(25, 25, 35, 0.95);
        padding: 30px;
        border-radius: 12px;
        text-align: center;
        color: #888899;
        font-style: italic;
    }
</style>

<div class="profile-container">

    <div class="profile-header">
        <div class="pfp-wrapper">
            <img src="/<?php echo htmlspecialchars($target_user['pfp']); ?>" alt="Profile Picture" class="pfp-image" id="pfp-img">

            <?php if ($is_own_profile): ?>
                <input type="file" id="pfp-input" accept="image/jpeg, image/png, image/gif, image/webp" style="display: none;" onchange="uploadPfp(this)">
                <button class="upload-btn" onclick="document.getElementById('pfp-input').click()">Upload Picture</button>
            <?php endif; ?>
        </div>

        <div class="profile-info">
            <h1>
                <?php echo htmlspecialchars($target_user['username']); ?>
                <?php if ($target_user['role'] === 'admin'): ?>
                    <span class="admin-badge">Admin</span>
                <?php endif; ?>
            </h1>
            <p>Joined: <?php echo htmlspecialchars($target_user['created_at']); ?></p>
        </div>
    </div>

    <div class="ai-summary-box">
        <h3><span style="color: #ff66aa;">✦</span> AI Playstyle Breakdown</h3>
        <p><?php echo htmlspecialchars($ai_summary); ?></p>
    </div>

    <div class="scores-section">
        <h2>Top Scores</h2>

        <?php if (empty($scores)): ?>
            <div class="empty-scores">
                No scores yet — go play something!
            </div>
        <?php else: ?>
            <table class="scores-table">
                <thead>
                    <tr>
                        <th>Song</th>
                        <th>Difficulty</th>
                        <th>Score</th>
                        <th>Accuracy</th>
                        <th>Rank</th>
                        <th>Date Set</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scores as $key => $data): 
                        // Split the key "song_id:Difficulty"
                        list($song_id, $difficulty) = explode(':', $key);
                        $title = $beatmap_titles[$song_id] ?? $song_id; // Fallback to ID if title not found
                        $rank = get_rank($data['accuracy']);
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($title); ?></td>
                            <td><?php echo htmlspecialchars($difficulty); ?></td>
                            <td><?php echo number_format($data['score']); ?></td>
                            <td><?php echo number_format($data['accuracy'], 1); ?>%</td>
                            <td class="rank-cell rank-<?php echo $rank; ?>"><?php echo $rank; ?></td>
                            <td><?php echo date("M j, Y", strtotime($data['submitted_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
// Handle PFP Upload without page reload
function uploadPfp(input) {
    if (!input.files || input.files.length === 0) return;

    const file = input.files[0];
    const formData = new FormData();
    formData.append('pfp', file);

    // Disable button to prevent spam
    const btn = document.querySelector('.upload-btn');
    const originalText = btn.innerText;
    btn.innerText = 'Uploading...';
    btn.disabled = true;

    fetch('/api/upload_pfp.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Append a timestamp to the URL to bypass browser image caching
            document.getElementById('pfp-img').src = '/' + data.path + '?t=' + new Date().getTime();
        } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('A network error occurred while uploading.');
    })
    .finally(() => {
        // Reset button
        btn.innerText = originalText;
        btn.disabled = false;
        input.value = ''; // Clear the file input
    });
}
</script>

<?php
require_once 'includes/footer.php';
?>