<?php
// Set appropriate headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Check if URL is provided
if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    echo "ERROR: Missing URL parameter";
    exit;
}

$url = $_GET['url'];

// Basic URL validation
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo "ERROR: Invalid URL format";
    exit;
}

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

// Execute cURL session
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Close cURL session
curl_close($ch);

// Set the same content type as the original resource
if ($content_type) {
    header("Content-Type: $content_type");
}

// Modify the HTML to fix relative URLs
if (strpos($content_type, 'text/html') !== false) {
    // Get the base URL from the original URL
    $parsed_url = parse_url($url);
    $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];

    // Replace relative URLs with absolute URLs
    $body = preg_replace('/(href|src)=(["\'])\/([^"\']+)(["\'])/i', '$1=$2' . $base_url . '/$3$4', $body);

    // Add base tag to HTML head if not present
    if (strpos($body, '<base') === false) {
        $body = str_replace('<head>', '<head><base href="' . $base_url . '/">', $body);
    }

    // Route all links through the proxy
    $body = preg_replace('/(href|src)=(["\'])(https?:\/\/[^"\']+)(["\'])/i', '$1=$2proxy.php?url=$3$4', $body);
}

// Output the final content
echo $body;
?>