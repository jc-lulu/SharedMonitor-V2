<?php
// Database connection settings
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password is empty
$dbname = "cedric_dbs";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Function to escape strings for SQL
function escape_string($conn, $string)
{
    return mysqli_real_escape_string($conn, $string);
}

// Create required tables if they don't exist
$sql = "CREATE TABLE IF NOT EXISTS screen_shares (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    share_id VARCHAR(255) NOT NULL,
    content_url TEXT NOT NULL,
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (share_id)
)";

if (!mysqli_query($conn, $sql)) {
    die("Error creating table: " . mysqli_error($conn));
}
?>