<?php
// Set appropriate headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Check if all required parameters are present
if (!isset($_POST['shareId']) || !isset($_POST['imageData'])) {
    http_response_code(400);
    echo "ERROR: Missing required parameters";
    exit;
}

$shareId = $_POST['shareId'];
$imageData = $_POST['imageData'];

// Validate the share ID (simple alphanumeric check)
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $shareId)) {
    http_response_code(400);
    echo "ERROR: Invalid share ID format";
    exit;
}

// Create directory to store frames if it doesn't exist
$frameDir = 'frames';
if (!file_exists($frameDir)) {
    mkdir($frameDir, 0755, true);
}

// Create a file name based on the share ID
$filePath = $frameDir . '/' . $shareId . '.txt';

// Only update the timestamp every second instead of every frame to reduce disk I/O
$timeFilePath = $frameDir . '/' . $shareId . '_time.txt';
$currentTime = time();
$updateTimestamp = true;

// If the timestamp file exists, check if we need to update it
if (file_exists($timeFilePath)) {
    $lastUpdateTime = (int) file_get_contents($timeFilePath);
    // Only update the timestamp if more than 1 second has passed
    if ($currentTime - $lastUpdateTime < 1) {
        $updateTimestamp = false;
    }
}

// Save the image data to file - use FILE_BINARY flag for better performance with binary data
file_put_contents($filePath, $imageData, LOCK_EX);

// Update the timestamp file to track active sessions (but only once per second)
if ($updateTimestamp) {
    file_put_contents($timeFilePath, $currentTime, LOCK_EX);
}

// Return success with minimal processing
echo "OK";
?>