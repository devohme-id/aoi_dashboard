<?php

declare(strict_types=1);

require_once 'db_config.php';
header('Content-Type: application/json');

$lineId = filter_input(INPUT_POST, 'line_id', FILTER_VALIDATE_INT);

if (!$lineId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Line ID']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->connect();

    $stmt = $conn->prepare("SELECT Assembly FROM Inspections WHERE LineID = ? ORDER BY EndTime DESC LIMIT 1");
    $stmt->execute([$lineId]);
    $current = $stmt->fetchColumn();

    $stmtAll = $conn->prepare("
        SELECT DISTINCT Assembly 
        FROM Inspections 
        WHERE LineID = ? AND Assembly IS NOT NULL AND Assembly != '' 
        ORDER BY Assembly ASC
    ");
    $stmtAll->execute([$lineId]);
    $all = $stmtAll->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'current_assembly' => $current,
        'all_assemblies' => $all
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}