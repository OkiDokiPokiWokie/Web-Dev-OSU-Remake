<?php
// Include helpers and protect page
require_once 'includes/auth.php';
require_once 'includes/json_helpers.php';

// 1. Strict Auth Check: Must be logged in AND an admin
require_login();
require_admin();

$current_admin = current_user();

// 2. Load Beatmaps
$beatmaps = read_json('data/beatmaps.json');
$beatmap_titles = [];
foreach ($beatmaps as $b) {
    $beatmap_titles[$b['id']] = $b['title'];
}

// 3. Load Users
$users = read_json('data/users.json');

// 4. Load & Sort Recent Scores (Top 50)
$score_files = glob('data/scores/*.json');
$all_recent_scores = [];

foreach ($score_files as $file) {
    $username = basename($file, '.json');
    $user_scores = read_json($file);

    foreach ($user_scores as $key => $data) {
        list($song_id, $difficulty) = explode(':', $key);
        $title = $beatmap_titles[$song_id] ?? $song_id; 

        $all_recent_scores[] = [
            'username' => $username,
            'song_id' => $song_id,
            'title' => $title,
            'difficulty' => $difficulty,
            'score' => $data['score'],
            'accuracy' => $data['accuracy'],
            'submitted_at' => $data['submitted_at'],
            'score_key' => $key
        ];
    }
}

// Sort by date submitted (descending - newest first)
usort($all_recent_scores, function($a, $b) {
    return strtotime($b['submitted_at']) <=> strtotime($a['submitted_at']);
});

// Take only the 50 most recent scores
$top_50_scores = array_slice($all_recent_scores, 0, 50);

// Include Header
require_once 'includes/header.php';
?>

<style>
    .admin-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
        color: #ffffff;
    }

    h1.admin-title {
        color: #ff66aa;
        text-align: center;
        margin-bottom: 40px;
        font-size: 2.5em;
    }

    .admin-section {
        background: rgba(25, 25, 35, 0.95);
        border: 1px solid #3a3a45;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 40px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
    }

    .admin-section h2 {
        margin-top: 0;
        border-bottom: 2px solid #ff66aa;
        padding-bottom: 10px;
        color: #ffffff;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .admin-table th, .admin-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .admin-table th {
        background: rgba(0, 0, 0, 0.3);
        color: #888899;
        font-size: 0.9em;
        text-transform: uppercase;
    }

    .admin-table tr:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    .btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        font-size: 0.85em;
        text-decoration: none;
        display: inline-block;
    }

    .btn-danger { background: #ff4d4d; color: white; }
    .btn-warning { background: #ffcc11; color: black; }
    .btn-success { background: #32cd32; color: white; }
    .btn-primary { background: #ff66aa; color: white; }
    .btn:disabled { background: #555; cursor: not-allowed; color: #888; }

    .upload-form {
        display: flex;
        gap: 15px;
        margin-top: 20px;
        align-items: center;
        background: #121216;
        padding: 15px;
        border-radius: 6px;
        border: 1px dashed #3a3a45;
    }

    .upload-form input[type="text"], .upload-form input[type="file"] {
        padding: 8px;
        background: #1a1a24;
        border: 1px solid #3a3a45;
        color: white;
        border-radius: 4px;
    }

    /* NEW RULE: This forces the text box to expand and fill the remaining space */
    .upload-form input[type="text"] {
        flex: 1;
    }
</style>

<div class="admin-container">
    <h1 class="admin-title">Admin Control Panel</h1>

    <div class="admin-section">
        <h2>Beatmap Management</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Folder ID</th>
                    <th>Title</th>
                    <th>Artist</th>
                    <th>Difficulties</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($beatmaps as $song): ?>
                <tr>
                    <td style="color: #888899;"><?php echo htmlspecialchars($song['id']); ?></td>
                    <td style="font-weight: bold;"><?php echo htmlspecialchars($song['title']); ?></td>
                    <td><?php echo htmlspecialchars($song['artist']); ?></td>
                    <td><?php echo count($song['difficulties']); ?></td>
                    <td style="display: flex; gap: 8px;">
                        <button onclick="renameBeatmap('<?php echo htmlspecialchars($song['id']); ?>', '<?php echo addslashes($song['title']); ?>')" class="btn btn-warning">Rename</button>

                        <form action="/api/admin/delete_beatmap.php" method="POST" onsubmit="return confirm('Delete beatmap: <?php echo addslashes($song['title']); ?>? This cannot be undone.');">
                            <input type="hidden" name="map_id" value="<?php echo htmlspecialchars($song['id']); ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($beatmaps)): ?>
                <tr><td colspan="5" style="text-align:center; color:#888;">No beatmaps uploaded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <form class="upload-form" action="/api/admin/upload_beatmap.php" method="POST" enctype="multipart/form-data">
            <strong>Upload New Map:</strong>
            <input type="text" name="folder_id" placeholder="folder_name (no spaces, no capitals, no special characters)" required pattern="[a-z0-9_]+">
            <input type="file" name="osz_file" accept=".osz" required>
            <button type="submit" class="btn btn-primary">Upload & Process</button>
        </form>
    </div>

    <div class="admin-section">
        <h2>User Management</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Join Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): 
                    $is_self = ($u['username'] === $current_admin);
                ?>
                <tr>
                    <td><a href="/profile.php?user=<?php echo urlencode($u['username']); ?>" style="color: white; font-weight: bold;"><?php echo htmlspecialchars($u['username']); ?></a></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td style="color: <?php echo $u['role'] === 'admin' ? '#ff66aa' : '#888899'; ?>; font-weight: bold; text-transform: uppercase;">
                        <?php echo htmlspecialchars($u['role']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($u['created_at']); ?></td>
                    <td style="display: flex; gap: 8px;">
                        <form action="/api/admin/manage_users.php" method="POST">
                            <input type="hidden" name="target_user" value="<?php echo htmlspecialchars($u['username']); ?>">
                            <?php if ($u['role'] === 'user'): ?>
                                <input type="hidden" name="action" value="promote">
                                <button type="submit" class="btn btn-success" <?php if($is_self) echo 'disabled'; ?>>Promote to Admin</button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="demote">
                                <button type="submit" class="btn btn-warning" <?php if($is_self) echo 'disabled'; ?>>Demote to User</button>
                            <?php endif; ?>
                        </form>

                        <form action="/api/admin/manage_users.php" method="POST" onsubmit="return confirm('Permanently delete user: <?php echo addslashes($u['username']); ?>?');">
                            <input type="hidden" name="target_user" value="<?php echo htmlspecialchars($u['username']); ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger" <?php if($is_self) echo 'disabled'; ?>>Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-section">
        <h2>Recent Scores (Top 50)</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Player</th>
                    <th>Map</th>
                    <th>Difficulty</th>
                    <th>Score</th>
                    <th>Accuracy</th>
                    <th>Rank</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_50_scores as $s): ?>
                <tr>
                    <td style="color: #888899; font-size: 0.9em;"><?php echo date("M j, Y H:i", strtotime($s['submitted_at'])); ?></td>
                    <td><a href="/profile.php?user=<?php echo urlencode($s['username']); ?>" style="color: white;"><?php echo htmlspecialchars($s['username']); ?></a></td>
                    <td><?php echo htmlspecialchars($s['title']); ?></td>
                    <td><?php echo htmlspecialchars($s['difficulty']); ?></td>
                    <td style="font-weight: bold;"><?php echo number_format($s['score']); ?></td>
                    <td><?php echo number_format($s['accuracy'], 1); ?>%</td>
                    <td style="font-weight: bold;"><?php echo get_rank($s['accuracy']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($top_50_scores)): ?>
                <tr><td colspan="7" style="text-align:center; color:#888;">No scores recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Handles dynamic prompting for the rename beatmap endpoint
    function renameBeatmap(mapId, currentTitle) {
        const newTitle = prompt(`Enter new title for beatmap (${mapId}):`, currentTitle);

        if (newTitle !== null && newTitle.trim() !== "" && newTitle !== currentTitle) {
            // Create a hidden form to submit the POST request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/api/admin/rename_beatmap.php';

            const mapInput = document.createElement('input');
            mapInput.type = 'hidden';
            mapInput.name = 'map_id';
            mapInput.value = mapId;

            const titleInput = document.createElement('input');
            titleInput.type = 'hidden';
            titleInput.name = 'new_title';
            titleInput.value = newTitle;

            form.appendChild(mapInput);
            form.appendChild(titleInput);
            document.body.appendChild(form);

            form.submit();
        }
    }
</script>

<?php
require_once 'includes/footer.php';
?>