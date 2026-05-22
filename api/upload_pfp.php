<?php
// 1. Include auth and json helpers
require_once '../includes/auth.php';
require_once '../includes/json_helpers.php';

// 2. Set the header to return JSON
header('Content-Type: application/json');

// 3. Ensure the user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit();
}

// 4. File Upload Validation
if (!isset($_FILES['pfp']) || $_FILES['pfp']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error occurred.']);
    exit();
}

$file = $_FILES['pfp'];

// Validate file size (under 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB limit.']);
    exit();
}

// Validate MIME type
$allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$file_mime = mime_content_type($file['tmp_name']);

if (!in_array($file_mime, $allowed_mimes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.']);
    exit();
}

// 5. Image Processing with GD Library
// Create the source image resource based on MIME type
switch ($file_mime) {
    case 'image/jpeg':
        $source_img = imagecreatefromjpeg($file['tmp_name']);
        break;
    case 'image/png':
        $source_img = imagecreatefrompng($file['tmp_name']);
        break;
    case 'image/gif':
        $source_img = imagecreatefromgif($file['tmp_name']);
        break;
    case 'image/webp':
        $source_img = imagecreatefromwebp($file['tmp_name']);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Unsupported image format.']);
        exit();
}

if (!$source_img) {
    echo json_encode(['success' => false, 'error' => 'Failed to read image data.']);
    exit();
}

// Get original dimensions
$orig_width = imagesx($source_img);
$orig_height = imagesy($source_img);

// Create a new 512x512 true color canvas
$target_size = 512;
$canvas = imagecreatetruecolor($target_size, $target_size);

// Handle transparency for PNG/GIF/WEBP
imagealphablending($canvas, false);
imagesavealpha($canvas, true);
$transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
imagefilledrectangle($canvas, 0, 0, $target_size, $target_size, $transparent);

// Calculate cropping to center the image if it's not a perfect square
$crop_size = min($orig_width, $orig_height);
$crop_x = ($orig_width - $crop_size) / 2;
$crop_y = ($orig_height - $crop_size) / 2;

// Resize and copy the image onto the 512x512 canvas
imagecopyresampled(
    $canvas, $source_img,
    0, 0, $crop_x, $crop_y,
    $target_size, $target_size,
    $crop_size, $crop_size
);

// 6. Save the image
$username = current_user();
$save_path = "uploads/pfp/{$username}.png"; // Always save as PNG
$absolute_path = "../" . $save_path;

// Ensure the directory exists (just in case)
if (!is_dir('../uploads/pfp')) {
    mkdir('../uploads/pfp', 0777, true);
}

// Save as PNG (overwrites if it already exists)
imagepng($canvas, $absolute_path);

// Free up memory
imagedestroy($source_img);
imagedestroy($canvas);

// 7. Update users.json
$users = read_json('../data/users.json');
foreach ($users as &$user) {
    if ($user['username'] === $username) {
        $user['pfp'] = $save_path;
        break;
    }
}
write_json('../data/users.json', $users);

// 8. Return success response to the frontend
echo json_encode(['success' => true, 'path' => $save_path]);