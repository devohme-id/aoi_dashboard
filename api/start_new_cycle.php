<?php
// api/start_new_cycle.php
require_once 'db_config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

// Validasi input
$line_id = filter_input(INPUT_POST, 'line_id', FILTER_VALIDATE_INT);
$assembly_name = filter_input(INPUT_POST, 'assembly_name');
$notes = filter_input(INPUT_POST, 'notes');
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$line_id || !$assembly_name || !$notes || !$user_id) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid input. All fields are required.']);
    exit;
}

try {
    $conn->begin_transaction();

    // 1. Cari versi siklus terakhir untuk kombinasi Line dan Assembly
    $stmt = $conn->prepare("SELECT MAX(CycleVersion) as max_version FROM TuningCycles WHERE LineID = ? AND Assembly = ?");
    $stmt->bind_param("is", $line_id, $assembly_name);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $last_version = $result['max_version'] ?? 0;

    // Jika tidak ada, kita bisa juga cek dari tabel Inspections sebagai fallback
    if ($last_version == 0) {
        $stmt_fallback = $conn->prepare("SELECT MAX(TuningCycleID) as max_version FROM Inspections WHERE LineID = ? AND Assembly = ?");
        $stmt_fallback->bind_param("is", $line_id, $assembly_name);
        $stmt_fallback->execute();
        $result_fallback = $stmt_fallback->get_result()->fetch_assoc();
        $last_version = $result_fallback['max_version'] ?? 0;
    }

    $new_version = $last_version + 1;

    // 2. Masukkan log siklus baru
    $stmt_insert = $conn->prepare("INSERT INTO TuningCycles (LineID, Assembly, CycleVersion, StartedByUserID, Notes) VALUES (?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("isiss", $line_id, $assembly_name, $new_version, $user_id, $notes);

    if ($stmt_insert->execute()) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => "Success! New tuning cycle (Version {$new_version}) for {$assembly_name} on Line {$line_id} has been started."
        ]);
    } else {
        throw new Exception("Failed to insert new cycle log.");
    }
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    error_log("Start Cycle Error: " . $e->getMessage());
    echo json_encode(['message' => 'Database error occurred: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_fallback)) $stmt_fallback->close();
    if (isset($stmt_insert)) $stmt_insert->close();
    $conn->close();
}
