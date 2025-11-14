<?php
// =============================================
// db_config.php (Optimized Version)
// =============================================

// Database connection configuration
$db_host = "192.168.12.207";         // or use internal IP if MySQL in same VM
$db_user = "db_admin";    // recommended: non-root user
$db_pass = "Ohm@2025";
// $db_host = "127.0.0.1";         // or use internal IP if MySQL in same VM
// $db_user = "root";    // recommended: non-root user
// $db_pass = "root";
$db_name = "aoi_dashboard";

// Enable persistent connection (prefix "p:")
$conn = @new mysqli('p:' . $db_host, $db_user, $db_pass, $db_name);

// Set proper charset
if (!$conn->connect_errno) {
    $conn->set_charset("utf8mb4");
} else {
    error_log("[" . date("Y-m-d H:i:s") . "] MySQL connection failed: " . $conn->connect_error . "\n", 3, "/var/log/php-fpm/db_error.log");
    http_response_code(500);
    die(json_encode(["error" => "Database connection failed."]));
}

// Optional: set connection timeout
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

// Optional function for safe query
function safe_query($query) {
    global $conn;
    $result = $conn->query($query);
    if (!$result) {
        error_log("[" . date("Y-m-d H:i:s") . "] Query failed: " . $conn->error . "\n", 3, "/var/log/php-fpm/db_error.log");
    }
    return $result;
}
?>