<?php
// Set appropriate headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: text/plain');

// Include database connection
require_once 'db_connection.php';

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

// Escape string for SQL safety
$shareId = escape_string($conn, $shareId);

// Query the database for the content URL
$sql = "SELECT content_url, last_update FROM screen_shares WHERE share_id = '$shareId'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo "NO_FRAME";
    exit;
}

$row = mysqli_fetch_assoc($result);
$contentUrl = $row['content_url'];
$lastUpdateTime = strtotime($row['last_update']);
$currentTime = time();

// If no update in the last 30 seconds, consider the session inactive
if ($currentTime - $lastUpdateTime > 30) {
    echo "ERROR: Session inactive";
    exit;
}

// Return the content URL
echo $contentUrl;

mysqli_close($conn);
?>