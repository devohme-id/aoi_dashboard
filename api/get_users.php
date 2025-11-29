<?php

declare(strict_types=1);

require_once 'db_config.php';
header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->connect();

    $response = ['analysts' => [], 'operators' => []];

    $stmt = $conn->query("SELECT UserID, FullName FROM Users WHERE Role = 'Analyst' ORDER BY FullName ASC");
    $response['analysts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("SELECT UserID, FullName FROM Users WHERE Role = 'Operator' ORDER BY FullName ASC");
    $response['operators'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch users']);
}