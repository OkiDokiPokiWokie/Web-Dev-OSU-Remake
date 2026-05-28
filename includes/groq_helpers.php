<?php
/**
 * Helper function to send requests to the Groq API.
 * Uses the Llama 3 8B model as required by the PRD.
 */
function call_groq($user_prompt, $system_prompt) {
    // Retrieve the API key from Replit Secrets
    $api_key = getenv('GROQ_API_KEY');

    // If the key is missing, immediately return the safe fallback
    if (!$api_key) {
        return "AI analysis unavailable right now.";
    }

    $url = "https://api.groq.com/openai/v1/chat/completions";

    // Build the payload exactly as specified in the PRD
    $data = [
        "model" => "llama-3.1-8b-instant",
        "messages" => [
            [
                "role" => "system",
                "content" => $system_prompt
            ],
            [
                "role" => "user",
                "content" => $user_prompt
            ]
        ],
        "max_tokens" => 300,
        "temperature" => 0.7
    ];

    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $api_key,
        "Content-Type: application/json"
    ]);

    // Set a strict timeout (e.g., 8 seconds) so the page doesn't hang if Groq is slow
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);

    // Execute the request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // If cURL failed completely, or the API returned an error code (4xx or 5xx)
    if ($response === false || $http_code >= 400) {
        return "AI analysis unavailable right now.";
    }

    // Decode the response
    $decoded = json_decode($response, true);

    // Safely extract the text from the response payload
    if (isset($decoded['choices'][0]['message']['content'])) {
        return trim($decoded['choices'][0]['message']['content']);
    }

    // Ultimate fallback if the JSON shape was unexpected
    return "AI analysis unavailable right now.";
}
?>