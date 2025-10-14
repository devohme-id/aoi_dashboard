<?php
// File: api/get_users.php
require_once 'db_config.php';
header('Content-Type: application/json');

try {
    $response = [
        'analysts' => [],
        'operators' => []
    ];

    // Get Analysts
    $analyst_query = "SELECT UserID, FullName FROM Users WHERE Role = 'Analyst' ORDER BY FullName ASC";
    $result = $conn->query($analyst_query);
    while ($row = $result->fetch_assoc()) {
        $response['analysts'][] = $row;
    }

    // Get Operators
    $operator_query = "SELECT UserID, FullName FROM Users WHERE Role = 'Operator' ORDER BY FullName ASC";
    $result = $conn->query($operator_query);
    while ($row = $result->fetch_assoc()) {
        $response['operators'][] = $row;
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not fetch users.']);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
