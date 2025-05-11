<?php
// Set appropriate headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: text/plain');

// Check if share ID is provided
if (!isset($_GET['id'])) {
    echo "ERROR: No sharing ID provided";
    exit;
}

$shareId = $_GET['id'];

// Validate the share ID (simple alphanumeric check)
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $shareId)) {
    echo "ERROR: Invalid share ID format";
    exit;
}

$frameDir = 'frames';
$filePath = $frameDir . '/' . $shareId . '.txt';
$timePath = $frameDir . '/' . $shareId . '_time.txt';

// Check if frame file exists
if (!file_exists($filePath)) {
    echo "NO_FRAME";
    exit;
}

// Check if the session is still active (last update within 30 seconds)
if (file_exists($timePath)) {
    $lastUpdateTime = (int) file_get_contents($timePath);
    $currentTime = time();

    // If no update in the last 30 seconds, consider the session inactive
    if ($currentTime - $lastUpdateTime > 30) {
        echo "ERROR: Session inactive";
        exit;
    }
}

// Read and return the image data
$imageData = file_get_contents($filePath);
echo $imageData;
?>