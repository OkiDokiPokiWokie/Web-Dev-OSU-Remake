<?php
// Include helpers
require_once '../../includes/auth.php';
require_once '../../includes/json_helpers.php';

// 1. Strict Auth Check
require_login();
require_admin();

// 2. Validate Input
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['target_user']) || empty($_POST['action'])) {
    die("Invalid request.");
}

$target_user = trim($_POST['target_user']);
$action = trim($_POST['action']);
$current_admin = current_user();

// 3. Security Check: An admin cannot modify or delete their own account
if ($target_user === $current_admin) {
    die("Error: You cannot modify or delete your own account.");
}

// 4. Load users.json
$users_file = '../../data/users.json';
$users = read_json($users_file);
$user_found = false;
$updated_users = [];

// 5. Process the action
foreach ($users as $u) {
    if ($u['username'] === $target_user) {
        $user_found = true;

        if ($action === 'promote') {
            $u['role'] = 'admin';
            $updated_users[] = $u;
        } elseif ($action === 'demote') {
            $u['role'] = 'user';
            $updated_users[] = $u;
        } elseif ($action === 'delete') {
            // By NOT adding them to $updated_users, they are removed from the array

            // Delete their personal score file
            $score_file = "../../data/scores/{$target_user}.json";
            if (file_exists($score_file)) {
                unlink($score_file);
            }

            // Delete their uploaded profile picture if it exists
            $pfp_file = "../../uploads/pfp/{$target_user}.png";
            if (file_exists($pfp_file)) {
                unlink($pfp_file);
            }
        }
    } else {
        // Keep all other users exactly the same
        $updated_users[] = $u;
    }
}

// 6. Save changes if the user was actually found
if ($user_found) {
    write_json($users_file, $updated_users);
}

// 7. Redirect back to the admin panel
header("Location: /admin.php?users=success");
exit();
?>