<?php
require_once '../../includes/auth.php';
require_once '../../includes/json_helpers.php';

// 1. Strict Auth Check
require_login();
require_admin();

// Helper function to recursively delete a directory
function delete_dir($dirPath) {
    if (!is_dir($dirPath)) return;
    $files = array_diff(scandir($dirPath), array('.', '..'));
    foreach ($files as $file) {
        $path = "$dirPath/$file";
        is_dir($path) ? delete_dir($path) : unlink($path);
    }
    rmdir($dirPath);
}

// 2. Validate Input
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['osz_file']) || empty($_POST['folder_id'])) {
    die("Invalid request.");
}

$folder_id = strtolower(trim($_POST['folder_id']));
$uploaded_file = $_FILES['osz_file'];

// Validate folder name (lowercase letters, numbers, underscores only)
if (!preg_match('/^[a-z0-9_]+$/', $folder_id)) {
    die("Error: Folder ID can only contain lowercase letters, numbers, and underscores.");
}

if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
    die("Error during file upload.");
}

// 3. Validate against existing beatmaps
$beatmaps_file = '../../data/beatmaps.json';
$beatmaps = read_json($beatmaps_file);

foreach ($beatmaps as $b) {
    if ($b['id'] === $folder_id) {
        die("Error: A beatmap with this folder ID already exists. Please choose a unique name.");
    }
}

// 4. Setup Temp Directory
$temp_dir = '../../data/temp';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

$zip_path = "$temp_dir/{$folder_id}.zip";
$extract_path = "$temp_dir/{$folder_id}_extracted";

// Move uploaded .osz and rename to .zip
if (!move_uploaded_file($uploaded_file['tmp_name'], $zip_path)) {
    die("Error: Failed to move uploaded file.");
}

// 5. Extract the ZIP
$zip = new ZipArchive;
if ($zip->open($zip_path) === TRUE) {
    $zip->extractTo($extract_path);
    $zip->close();
} else {
    unlink($zip_path);
    die("Error: Failed to extract the .osz file.");
}

// 6. Process Extracted Files
$extracted_files = scandir($extract_path);
$osu_files = [];
$audio_file = null;
$bg_file = null;

// Find audio and background files to share across difficulties
foreach ($extracted_files as $file) {
    if ($file === '.' || $file === '..') continue;

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($ext === 'osu') {
        $osu_files[] = $file;
    } elseif (!$audio_file && in_array($ext, ['mp3', 'ogg'])) {
        $audio_file = $file;
    } elseif (!$bg_file && in_array($ext, ['jpg', 'jpeg', 'png'])) {
        $bg_file = $file;
    }
}

if (empty($osu_files)) {
    delete_dir($extract_path);
    unlink($zip_path);
    die("Error: No .osu files found in the archive.");
}

// Data to collect for beatmaps.json
$song_title = "Unknown Title";
$song_artist = "Unknown Artist";
$difficulties = [];

$final_beatmap_dir = "../../beatmaps/{$folder_id}";
if (!is_dir($final_beatmap_dir)) {
    mkdir($final_beatmap_dir, 0777, true);
}

// Parse each .osu file
foreach ($osu_files as $osu_file) {
    $contents = file_get_contents("$extract_path/$osu_file");
    $lines = explode("\n", $contents);

    $version = "Normal";
    $bpm = 0;
    $in_metadata = false;
    $in_timing = false;

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '//') === 0) continue;

        // Check section headers
        if (preg_match('/^\[(.*?)\]$/', $line, $matches)) {
            $section = $matches[1];
            $in_metadata = ($section === 'Metadata');
            $in_timing = ($section === 'TimingPoints');
            continue;
        }

        if ($in_metadata) {
            if (strpos($line, 'Title:') === 0) $song_title = trim(substr($line, 6));
            if (strpos($line, 'Artist:') === 0) $song_artist = trim(substr($line, 7));
            if (strpos($line, 'Version:') === 0) $version = trim(substr($line, 8));
        }

        if ($in_timing && $bpm === 0) {
            $parts = explode(',', $line);
            // Verify it has enough parts and uninherited is 1 (osu! format specifies this is index 6 or 7 depending on file version, checking index 6 generally covers standard format)
            if (count($parts) >= 7 && (int)$parts[6] === 1) {
                $beatLength = (float)$parts[1];
                if ($beatLength > 0) {
                    $bpm = round(60000 / $beatLength);
                }
            }
        }
    }

    // Sanitize difficulty name for folder creation (e.g. "Hard Rock" -> "hard_rock")
    $sanitized_diff = strtolower(preg_replace('/[^a-z0-9]+/', '_', $version));
    $sanitized_diff = trim($sanitized_diff, '_');

    $diff_folder = "$final_beatmap_dir/$sanitized_diff";
    if (!is_dir($diff_folder)) {
        mkdir($diff_folder, 0777, true);
    }

    // Move .osu file
    copy("$extract_path/$osu_file", "$diff_folder/beatmap.osu");

    // Move audio and bg (if they exist)
    if ($audio_file) {
        $ext = strtolower(pathinfo($audio_file, PATHINFO_EXTENSION));
        copy("$extract_path/$audio_file", "$diff_folder/audio.$ext");
    }
    if ($bg_file) {
        copy("$extract_path/$bg_file", "$diff_folder/bg.jpg"); // Force bg.jpg as per PRD
    }

    // Add to difficulty array
    $difficulties[] = [
        "name" => $version,
        "folder" => "beatmaps/{$folder_id}/{$sanitized_diff}",
        "bpm" => $bpm
    ];
}

// 7. Update beatmaps.json
$new_beatmap = [
    "id" => $folder_id,
    "title" => $song_title,
    "artist" => $song_artist,
    "difficulties" => $difficulties
];

$beatmaps[] = $new_beatmap;
write_json($beatmaps_file, $beatmaps);

// 8. Cleanup Temp Files
delete_dir($extract_path);
unlink($zip_path);

// 9. Redirect back to admin panel
header("Location: /admin.php?upload=success");
exit();
?>