<?php
// Include helpers
require_once '../../includes/auth.php';
require_once '../../includes/json_helpers.php';

// 1. Strict Auth Check
require_login();
require_admin();

// 2. Validate Input
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['map_id'])) {
    die("Invalid request.");
}

// Use basename to prevent directory traversal attacks (e.g. submitting "../../" as an ID)
$map_id = basename($_POST['map_id']);

// 3. Remove the entry from beatmaps.json
$beatmaps_file = '../../data/beatmaps.json';
$beatmaps = read_json($beatmaps_file);
$updated_beatmaps = [];
$map_found = false;

foreach ($beatmaps as $b) {
    if ($b['id'] === $map_id) {
        $map_found = true; // We found the map to delete, so we skip adding it to the new array
    } else {
        $updated_beatmaps[] = $b;
    }
}

// Only write back if a change was actually made
if ($map_found) {
    write_json($beatmaps_file, $updated_beatmaps);
}

// 4. Delete the entire beatmap folder from disk
$beatmap_dir = "../../beatmaps/{$map_id}";

// Recursive directory deletion helper function
function delete_dir($dirPath) {
    if (!is_dir($dirPath)) return;

    // Get all files and folders inside, excluding . and ..
    $files = array_diff(scandir($dirPath), array('.', '..'));

    foreach ($files as $file) {
        $path = "$dirPath/$file";
        // If it's a directory, recursively call this function, otherwise just delete the file
        is_dir($path) ? delete_dir($path) : unlink($path);
    }
    // Finally, remove the empty directory
    rmdir($dirPath);
}

// Execute the folder deletion
if (is_dir($beatmap_dir)) {
    delete_dir($beatmap_dir);
}

// 5. Redirect back to the admin panel
header("Location: /admin.php?delete=success");
exit();
?>