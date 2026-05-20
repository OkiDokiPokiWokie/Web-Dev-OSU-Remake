<?php
/**
 * JSON and Rank Helper Functions
 * Project: osu! Web Clone
 */

/**
 * Safely reads a JSON file and decodes it into a PHP associative array.
 * Returns an empty array [] if the file doesn't exist or is empty/empty object.
 *
 * @param string $filepath Path to the JSON file
 * @return array
 */
function read_json($filepath) {
    // If the file does not exist, return an empty array []
    if (!file_exists($filepath)) {
        return [];
    }

    $content = file_get_contents($filepath);

    // Check if the content is empty or contains an empty object string '{}'
    if (trim($content) === '' || trim($content) === '{}') {
        return [];
    }

    $data = json_decode($content, true);

    // Ensure we return an array, otherwise fallback to []
    return is_array($data) ? $data : [];
}

/**
 * Encodes a PHP array/object into pretty-printed JSON and writes it to a file.
 * Overwrites whatever was previously in the file.
 *
 * @param string $filepath Path to the JSON file
 * @param mixed $data Data to be written
 * @return bool True on success, false on failure
 */
function write_json($filepath, $data) {
    // Encode with JSON_PRETTY_PRINT as specified by the PRD rules
    $json_string = json_encode($data, JSON_PRETTY_PRINT);

    // Write to disk, completely overwriting the file
    return file_put_contents($filepath, $json_string) !== false;
}

/**
 * Calculates a player's rank letter based on their accuracy percentage.
 *
 * @param float $accuracy Accuracy value from 0.0 to 100.0
 * @return string The derived rank (SS, S, A, B, C, or D)
 */
function get_rank($accuracy) {
    // Cast to float to ensure correct numerical comparisons
    $acc = (float)$accuracy;

    if ($acc === 100.0) {
        return "SS";
    } elseif ($acc >= 95.0) {
        return "S";
    } elseif ($acc >= 90.0) {
        return "A";
    } elseif ($acc >= 80.0) {
        return "B";
    } elseif ($acc >= 70.0) {
        return "C";
    } else {
        return "D";
    }
}