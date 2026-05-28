<?php
// Include helpers
require_once '../includes/auth.php';
require_once '../includes/groq_helpers.php';

// 1. Check if the user is logged in
// We return JSON here instead of a redirect because this is an API endpoint called by fetch()
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

// 2. Receive and decode the JSON POST body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// 3. Validate that we received the expected data
$required_fields = ['map_title', 'difficulty', 'score', 'accuracy', 'misses', 'total_circles', 'max_combo', 'is_new_personal_best'];
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        // If data is missing, fail gracefully so the frontend still renders properly
        echo json_encode(["analysis" => "AI analysis unavailable right now."]);
        exit();
    }
}

// Extract variables for easy templating
$map_title = $data['map_title'];
$difficulty = $data['difficulty'];
$score = number_format($data['score']); // Format for better AI readability
$accuracy = $data['accuracy'];
$misses = $data['misses'];
$total_circles = $data['total_circles'];
$max_combo = $data['max_combo'];
$is_new_pb = $data['is_new_personal_best'];
$prev_score = $data['previous_best_score'] ?? null;
$prev_acc = $data['previous_best_accuracy'] ?? null;

// 4. Build the System Prompt exactly as requested in the PRD
$system_prompt = "You are a rhythm game coach giving brief, specific feedback to a player after they finish a beatmap. Keep your response to 2-4 sentences. Be encouraging but honest. Be specific to the numbers provided. Do not use generic phrases like \"great job\" without backing them up with specifics.";

// 5. Build the User Prompt dynamically based on the stats
$user_prompt = "The player just finished \"{$map_title}\" on {$difficulty} difficulty.\n";
$user_prompt .= "Score: {$score} | Accuracy: {$accuracy}% | Misses: {$misses} out of {$total_circles} circles | Max combo: {$max_combo}\n";

// Handle the logic for whether they have played this before
if ($prev_score === null) {
    $user_prompt .= "This is their first time completing this map.\n";
} else {
    $prev_score_fmt = number_format($prev_score);
    $pb_text = $is_new_pb ? "IS a new personal best" : "is NOT a new personal best";
    $user_prompt .= "This {$pb_text}. Previous best was {$prev_score_fmt} score at {$prev_acc}% accuracy.\n";
}

$user_prompt .= "Give them specific feedback on this run.";

// 6. Call Groq using the helper function
$analysis = call_groq($user_prompt, $system_prompt);

// 7. Return the response back to JavaScript
header('Content-Type: application/json');
echo json_encode(["analysis" => $analysis]);
exit();
?>