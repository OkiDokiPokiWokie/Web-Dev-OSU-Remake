<?php
// Include helpers
require_once '../../includes/auth.php';
require_once '../../includes/json_helpers.php';

// 1. Strict Auth Check
require_login();
require_admin();

// 2. Validate Input
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['map_id']) || empty($_POST['new_title'])) {
    die("Invalid request.");
}

$map_id = trim($_POST['map_id']);
$new_title = trim($_POST['new_title']);

// 3. Update beatmaps.json
$beatmaps_file = '../../data/beatmaps.json';
$beatmaps = read_json($beatmaps_file);
$is_updated = false;

// Iterate through the beatmaps by reference (&$b) so we can modify the array directly
foreach ($beatmaps as &$b) {
    if ($b['id'] === $map_id) {
        $b['title'] = $new_title;
        $is_updated = true;
        break; // Stop looping once we find and update the map
    }
}
unset($b); // Break the reference to avoid accidental bugs later

// 4. Save changes if a match was found
if ($is_updated) {
    write_json($beatmaps_file, $beatmaps);
}

// 5. Redirect back to the admin panel
header("Location: /admin.php?rename=success");
exit();
?>