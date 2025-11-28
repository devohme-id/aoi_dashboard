<?php
// api/start_new_cycle.php
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

// Input Data
$line_id = filter_input(INPUT_POST, 'line_id', FILTER_VALIDATE_INT);
$assembly = filter_input(INPUT_POST, 'assembly_name'); // FILTER_SANITIZE_STRING handled by default/PDO
$notes = filter_input(INPUT_POST, 'notes');

if (!$line_id || !$assembly || !$notes) {
    http_response_code(400);
    echo json_encode(['message' => 'All fields required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->connect();
    
    $conn->beginTransaction();

    // 1. Find or Create User
    $stmt = $conn->prepare("SELECT UserID FROM Users WHERE Username = ?");
    $stmt->execute([$_SESSION['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userId = $user['UserID'];
    } else {
        $fullName = $_SESSION['full_name'] ?? $_SESSION['username'];
        $stmtInsert = $conn->prepare("INSERT INTO Users (Username, FullName, Role) VALUES (?, ?, 'Analyst')");
        $stmtInsert->execute([$_SESSION['username'], $fullName]);
        $userId = $conn->lastInsertId();
    }

    // 2. Get Last Version
    // Cek di TuningCycles
    $stmtVer = $conn->prepare("SELECT MAX(CycleVersion) as max_v FROM TuningCycles WHERE LineID = ? AND Assembly = ?");
    $stmtVer->execute([$line_id, $assembly]);
    $last_ver = $stmtVer->fetchColumn();

    if (!$last_ver) {
        // Fallback cek di Inspections
        $stmtFall = $conn->prepare("SELECT MAX(TuningCycleID) as max_v FROM Inspections WHERE LineID = ? AND Assembly = ?");
        $stmtFall->execute([$line_id, $assembly]);
        $last_ver = $stmtFall->fetchColumn() ?: 0;
    }

    $new_ver = $last_ver + 1;

    // 3. Insert New Cycle
    $stmtCycle = $conn->prepare("
        INSERT INTO TuningCycles (LineID, Assembly, CycleVersion, StartedByUserID, Notes) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtCycle->execute([$line_id, $assembly, $new_ver, $userId, $notes]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Cycle {$new_ver} started for {$assembly}."
    ]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
}
?>