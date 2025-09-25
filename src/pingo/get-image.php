<?php
/**
 * Get image from filesystem cache
 * Endpoint untuk mengambil gambar yang disimpan di cache/pingo/img/
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Validate filename parameter
if (!isset($_GET['filename']) || empty($_GET['filename'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Filename parameter required']);
    exit;
}

$filename = basename($_GET['filename']); // Sanitize filename
$filepath = __DIR__ . '/../../cache/pingo/img/' . $filename;

// Check if file exists and is within allowed directory
if (!file_exists($filepath) || !is_file($filepath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Image not found']);
    exit;
}

// Verify file is within cache directory (security check)
$realPath = realpath($filepath);
$cacheDir = realpath(__DIR__ . '/../../cache/pingo/img');
if (strpos($realPath, $cacheDir) !== 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Get file info
$fileInfo = pathinfo($filepath);
$extension = strtolower($fileInfo['extension'] ?? 'jpg');

// Set appropriate content type
$contentTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'bmp' => 'image/bmp'
];

$contentType = $contentTypes[$extension] ?? 'image/jpeg';

// Set cache headers
header('Content-Type: ' . $contentType);
header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filepath)) . ' GMT');

// Output image
readfile($filepath);
exit;