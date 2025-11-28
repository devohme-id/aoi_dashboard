<?php
// api/get_assemblies.php
require_once 'db_config.php';
header('Content-Type: application/json');

$line_id = filter_input(INPUT_POST, 'line_id', FILTER_VALIDATE_INT);

if (!$line_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Line ID']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->connect();

    // 1. Current Assembly
    $stmt = $conn->prepare("SELECT Assembly FROM Inspections WHERE LineID = ? ORDER BY EndTime DESC LIMIT 1");
    $stmt->execute([$line_id]);
    $current = $stmt->fetchColumn();

    // 2. All Assemblies List
    $stmtAll = $conn->prepare("
        SELECT DISTINCT Assembly 
        FROM Inspections 
        WHERE LineID = ? AND Assembly IS NOT NULL AND Assembly != '' 
        ORDER BY Assembly ASC
    ");
    $stmtAll->execute([$line_id]);
    $all = $stmtAll->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'current_assembly' => $current,
        'all_assemblies' => $all
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>