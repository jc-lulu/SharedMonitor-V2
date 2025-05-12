<?php
// Set appropriate headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Include database connection
require_once 'db_connection.php';

// Check if all required parameters are present
if (!isset($_POST['shareId']) || !isset($_POST['contentUrl'])) {
    http_response_code(400);
    echo "ERROR: Missing required parameters";
    exit;
}

$shareId = $_POST['shareId'];
$contentUrl = $_POST['contentUrl'];

// Validate the share ID (simple alphanumeric check)
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $shareId)) {
    http_response_code(400);
    echo "ERROR: Invalid share ID format";
    exit;
}

// Escape strings for SQL safety
$shareId = escape_string($conn, $shareId);
$contentUrl = escape_string($conn, $contentUrl);

// Insert or update the content URL for this share ID
$sql = "INSERT INTO screen_shares (share_id, content_url) 
        VALUES ('$shareId', '$contentUrl') 
        ON DUPLICATE KEY UPDATE content_url = '$contentUrl', last_update = CURRENT_TIMESTAMP";

if (mysqli_query($conn, $sql)) {
    echo "OK";
} else {
    http_response_code(500);
    echo "ERROR: Database operation failed";
}

mysqli_close($conn);
?>