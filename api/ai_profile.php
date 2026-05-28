<?php
// Include helpers
require_once '../includes/auth.php';
require_once '../includes/json_helpers.php';
require_once '../includes/groq_helpers.php';

// 1. Receive and decode the JSON POST body
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// 2. Validate that we received the expected data
if (!isset($data['username']) || !isset($data['scores'])) {
    header('Content-Type: application/json');
    echo json_encode(["summary" => "AI analysis unavailable right now."]);
    exit();
}

$username = $data['username'];
$scores = $data['scores'];

// 3. Build the System Prompt as a scout
$system_prompt = "You are an esports scout writing a brief, analytical report on a rhythm game player's overall performance. Review their score history and provide 2-4 sentences summarizing their strengths, weaknesses, or patterns. Be encouraging but honest, and use specific numbers from their history. Do not use generic phrases without backing them up.";

// 4. Build the User Prompt dynamically based on their history
$user_prompt = "Player: {$username}\n";

if (empty($scores)) {
    $user_prompt .= "This player has no recorded scores yet. Encourage them to play their first map!";
} else {
    $user_prompt .= "Score History:\n";
    foreach ($scores as $map_key => $stats) {
        $score_fmt = number_format($stats['score']);
        $acc = $stats['accuracy'];
        $user_prompt .= "- Map: {$map_key} | Score: {$score_fmt} | Accuracy: {$acc}%\n";
    }
    $user_prompt .= "\nWrite a scout report on their performance based on these records.";
}

// 5. Call Groq using the helper function
$analysis = call_groq($user_prompt, $system_prompt);

// 6. Return the response back as JSON
header('Content-Type: application/json');
echo json_encode(["summary" => $analysis]);
exit();
?>