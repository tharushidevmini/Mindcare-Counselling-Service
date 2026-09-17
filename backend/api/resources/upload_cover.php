<?php
// FILE: backend/api/resources/upload_cover.php
// Accepts a single image file (from the drag-and-drop cover uploader on
// the counselor's Resource Manager page), validates it's really an image,
// stores it under backend/uploads/resources/, and returns the public URL
// to save as the resource's file_path — no external image hosting needed.
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['cover'])) {
    echo json_encode(['error' => 'No file received']); exit;
}

$file = $_FILES['cover'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload failed. Please try again.']); exit;
}

// 5MB cap — plenty for a cover image, small enough to keep the library fast.
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['error' => 'Image is too large (max 5MB).']); exit;
}

// Verify it's actually a real image (not just a renamed file) and figure
// out a safe extension from the real image type, ignoring the client-sent
// filename/MIME entirely.
$info = @getimagesize($file['tmp_name']);
if (!$info) {
    echo json_encode(['error' => 'That file is not a valid image.']); exit;
}
$allowed = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG  => 'png',
    IMAGETYPE_WEBP => 'webp',
    IMAGETYPE_GIF  => 'gif',
];
if (!isset($allowed[$info[2]])) {
    echo json_encode(['error' => 'Please use a JPG, PNG, WEBP, or GIF image.']); exit;
}
$ext = $allowed[$info[2]];

$uploadDir = __DIR__ . '/../../uploads/resources/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filename = 'cover_' . bin2hex(random_bytes(8)) . '.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['error' => 'Could not save the image. Please try again.']); exit;
}

// Absolute URL so it works the same whether it's shown on the counselor's
// own dashboard or the public library page.
$publicUrl = rtrim(
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'],
    '/'
) . '/mindcare_final/backend/uploads/resources/' . $filename;

echo json_encode(['success' => true, 'url' => $publicUrl]);
?>