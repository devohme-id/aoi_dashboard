<?php
// api/get_users.php
require_once 'db_config.php';
header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->connect();

    $response = ['analysts' => [], 'operators' => []];

    // Analysts
    $stmt = $conn->query("SELECT UserID, FullName FROM Users WHERE Role = 'Analyst' ORDER BY FullName ASC");
    $response['analysts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Operators
    $stmt = $conn->query("SELECT UserID, FullName FROM Users WHERE Role = 'Operator' ORDER BY FullName ASC");
    $response['operators'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch users']);
}
?>