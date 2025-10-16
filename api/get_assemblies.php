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
    // Langkah 1: Dapatkan assembly yang sedang berjalan (terakhir kali tercatat)
    $current_assembly = null;
    $stmt_current = $conn->prepare("SELECT Assembly FROM Inspections WHERE LineID = ? ORDER BY EndTime DESC LIMIT 1");
    $stmt_current->bind_param("i", $line_id);
    $stmt_current->execute();
    $result_current = $stmt_current->get_result();
    if ($row_current = $result_current->fetch_assoc()) {
        $current_assembly = $row_current['Assembly'];
    }
    $stmt_current->close();

    // Langkah 2: Dapatkan semua nama assembly unik yang pernah tercatat
    $stmt_all = $conn->prepare("SELECT DISTINCT Assembly FROM Inspections WHERE LineID = ? AND Assembly IS NOT NULL AND Assembly != '' ORDER BY Assembly ASC");
    $stmt_all->bind_param("i", $line_id);
    $stmt_all->execute();
    $result_all = $stmt_all->get_result();

    $all_assemblies = [];
    while ($row_all = $result_all->fetch_assoc()) {
        $all_assemblies[] = $row_all['Assembly'];
    }
    $stmt_all->close();

    // Kirim response dalam format terstruktur
    echo json_encode([
        'current_assembly' => $current_assembly,
        'all_assemblies' => $all_assemblies
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Get Assemblies Error: " . $e->getMessage());
    echo json_encode(['error' => 'Database query failed.']);
} finally {
    $conn->close();
}
