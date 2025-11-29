<?php

declare(strict_types=1);

session_start();
require_once 'db_config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

$lineId = filter_input(INPUT_POST, 'line_id', FILTER_VALIDATE_INT);
$assembly = filter_input(INPUT_POST, 'assembly_name');
$notes = filter_input(INPUT_POST, 'notes');

if (!$lineId || !$assembly || !$notes) {
    http_response_code(400);
    echo json_encode(['message' => 'All fields required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->connect();
    
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT UserID FROM Users WHERE Username = ?");
    $stmt->execute([$_SESSION['username']]);
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        $fullName = $_SESSION['full_name'] ?? $_SESSION['username'];
        $stmtInsert = $conn->prepare("INSERT INTO Users (Username, FullName, Role) VALUES (?, ?, 'Analyst')");
        $stmtInsert->execute([$_SESSION['username'], $fullName]);
        $userId = $conn->lastInsertId();
    }

    $stmtVer = $conn->prepare("SELECT MAX(CycleVersion) FROM TuningCycles WHERE LineID = ? AND Assembly = ?");
    $stmtVer->execute([$lineId, $assembly]);
    $lastVer = $stmtVer->fetchColumn();

    if ($lastVer === false || $lastVer === null) {
        $stmtFall = $conn->prepare("SELECT MAX(TuningCycleID) FROM Inspections WHERE LineID = ? AND Assembly = ?");
        $stmtFall->execute([$lineId, $assembly]);
        $lastVer = $stmtFall->fetchColumn() ?: 0;
    }

    $newVer = (int)$lastVer + 1;

    $stmtCycle = $conn->prepare("
        INSERT INTO TuningCycles (LineID, Assembly, CycleVersion, StartedByUserID, Notes) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtCycle->execute([$lineId, $assembly, $newVer, $userId, $notes]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Cycle {$newVer} started for {$assembly}."
    ]);

} catch (Throwable $e) {
    if (isset($conn)) $conn->rollBack();
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
}