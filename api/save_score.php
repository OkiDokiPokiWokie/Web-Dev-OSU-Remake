<?php
// 1. Include auth and json helpers (adjust path since we are inside /api)
require_once '../includes/auth.php';
require_once '../includes/json_helpers.php';

// 2. Set the header to return JSON
header('Content-Type: application/json');

// 3. Ensure the user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit();
}

// 4. Retrieve and decode the JSON POST body
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!$data) {
    echo json_encode(['error' => 'Invalid JSON payload.']);
    exit();
}

$map_id = $data['map'] ?? '';
$difficulty_name = $data['difficulty'] ?? '';
$score = isset($data['score']) ? (int)$data['score'] : 0;
$accuracy = isset($data['accuracy']) ? (float)$data['accuracy'] : 0.0;

// 5. Validate that the map and difficulty actually exist in beatmaps.json
$beatmaps = read_json('../data/beatmaps.json');
$is_valid_map = false;

foreach ($beatmaps as $song) {
    if ($song['id'] === $map_id) {
        foreach ($song['difficulties'] as $diff) {
            if ($diff['name'] === $difficulty_name) {
                $is_valid_map = true;
                break 2;
            }
        }
    }
}

if (!$is_valid_map) {
    echo json_encode(['error' => 'Invalid map or difficulty. Score rejected.']);
    exit();
}

// 6. Safely construct the score key
$score_key = "{$map_id}:{$difficulty_name}";

// 7. Open the user's specific score file
$username = current_user();
$score_file_path = "../data/scores/{$username}.json";
$user_scores = read_json($score_file_path);

// 8. Score Logic: Determine if we should save this score
$should_save = false;

if (!isset($user_scores[$score_key])) {
    // First time playing this map+difficulty
    $should_save = true;
} else {
    // Only overwrite if the new score is strictly higher than the stored one
    $existing_score = $user_scores[$score_key]['score'];
    if ($score > $existing_score) {
        $should_save = true;
    }
}

// 9. Save and respond
if ($should_save) {
    $user_scores[$score_key] = [
        'score' => $score,
        'accuracy' => $accuracy,
        // ISO 8601 timestamp format as requested by PRD
        'submitted_at' => date('c') 
    ];

    write_json($score_file_path, $user_scores);
    echo json_encode(['result' => 'saved']);
} else {
    // Score was equal to or lower than their PB
    echo json_encode(['result' => 'no_change']);
}