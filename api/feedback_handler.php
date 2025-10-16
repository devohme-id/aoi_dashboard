<?php
// File: api/feedback_handler.php

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once 'db_config.php';

define('CRITICAL_DEFECTS', [
    'SHORT SOLDER',
    'POOR SOLDER',
    'BALL SOLDER',
    'NO SOLDER',
    'WRONG POLARITY',
    'WRONG COMPONENT'
]);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handleFeedbackSubmission($conn);
    } else {
        getFeedbackData($conn);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}

function getFeedbackData($conn)
{
    // 1. Ambil semua line produksi yang ada
    $lines_query = "SELECT LineID, LineName FROM ProductionLines ORDER BY LineID ASC";
    $lines_result = $conn->query($lines_query);
    if (!$lines_result) throw new Exception("Query failed [lines]: " . $conn->error);
    $all_lines = [];
    while ($row = $lines_result->fetch_assoc()) {
        $all_lines[] = $row;
    }

    // 2. Ambil antrean verifikasi (defect yang belum diverifikasi)
    // *** PERBAIKAN: Logika query diperjelas untuk relevansi data ***
    $queue_query = "
        SELECT 
            d.DefectID, d.MachineDefectCode, d.ComponentRef, d.PartNumber, d.ImageFileName,
            i.InspectionID, i.EndTime, i.FinalResult, i.Assembly, i.LotCode, i.LineID,
            pl.LineName,
            op.FullName AS OperatorName
        FROM Defects d
        JOIN Inspections i ON d.InspectionID = i.InspectionID
        LEFT JOIN FeedbackLog fl ON d.DefectID = fl.DefectID
        LEFT JOIN Users op ON i.OperatorUserID = op.UserID
        LEFT JOIN ProductionLines pl ON i.LineID = pl.LineID
        WHERE 
            i.FinalResult IN ('Defective', 'False Fail', 'Unreviewed')
            AND fl.FeedbackID IS NULL
        ORDER BY i.EndTime DESC, d.DefectID ASC
        LIMIT 300;
    ";
    $queue_result = $conn->query($queue_query);
    if (!$queue_result) throw new Exception("Query failed [queue]: " . $conn->error);

    $queue_data = [];
    while ($row = $queue_result->fetch_assoc()) {
        $image_url = null;
        if (!empty($row['ImageFileName'])) {
            $path_parts = explode('\\', $row['ImageFileName']);
            if (count($path_parts) >= 2) {
                $date_folder = $path_parts[0];
                $actual_filename = end($path_parts);
                $image_url = "api/get_image.php?line=" . $row['LineID'] . "&date=" . urlencode($date_folder) . "&file=" . urlencode($actual_filename);
            }
        }
        $row['image_url'] = $image_url;
        $row['is_critical'] = in_array(strtoupper($row['MachineDefectCode']), CRITICAL_DEFECTS);
        $queue_data[] = $row;
    }

    // 3. Kembalikan data dalam format objek yang terstruktur
    echo json_encode([
        'all_lines' => $all_lines,
        'verification_queue' => $queue_data
    ]);
}


function handleFeedbackSubmission($conn)
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['defect_id']) || !is_numeric($data['defect_id']) || !isset($data['decision']) || empty($data['decision'])) {
        throw new Exception("Invalid input: Defect ID and decision are required.");
    }

    $defectId = (int)$data['defect_id'];
    $analystId = (int)$data['analyst_user_id'];
    $decision = $data['decision'];
    $notes = $data['notes'] ?? null;

    $stmt = $conn->prepare(
        "INSERT INTO FeedbackLog (DefectID, AnalystUserID, AnalystDecision, AnalystNotes) VALUES (?, ?, ?, ?)"
    );
    if (!$stmt) throw new Exception("Prepare statement failed: " . $conn->error);

    $stmt->bind_param("iiss", $defectId, $analystId, $decision, $notes);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully.']);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $stmt->close();
}