<?php
// File: api/feedback_handler.php
session_start();
header('Content-Type: application/json');

require_once 'db_config.php';

$critical_defects = [
    'SHORT SOLDER', 'POOR SOLDER', 'BALL SOLDER',
    'NO SOLDER', 'WRONG POLARITY', 'WRONG COMPONENT'
];

try {
    $database = new Database();
    $conn = $database->connect();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handleFeedbackSubmission($conn);
    } else {
        getFeedbackData($conn, $critical_defects);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
}

function getFeedbackData(PDO $conn, array $critical_defects) {
    // 1. Get Lines
    $stmt = $conn->query("SELECT LineID, LineName FROM ProductionLines ORDER BY LineID ASC");
    $all_lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get Queue
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
    
    $stmt = $conn->query($queue_query);
    $queue_data = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $image_url = null;
        if (!empty($row['ImageFileName'])) {
            $path_parts = preg_split('/[\\\\\\/]/', $row['ImageFileName']); // Handle \ or /
            if (count($path_parts) >= 2) {
                $date_folder = $path_parts[0];
                $actual_filename = end($path_parts);
                $image_url = sprintf(
                    "api/get_image.php?line=%d&date=%s&file=%s",
                    $row['LineID'],
                    urlencode($date_folder),
                    urlencode($actual_filename)
                );
            }
        }
        $row['image_url'] = $image_url;
        $row['is_critical'] = in_array(strtoupper($row['MachineDefectCode'] ?? ''), $critical_defects);
        $queue_data[] = $row;
    }

    echo json_encode([
        'all_lines' => $all_lines,
        'verification_queue' => $queue_data
    ]);
}

function handleFeedbackSubmission(PDO $conn) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['defect_id'], $data['decision'])) {
        throw new Exception("Invalid input parameters.");
    }
    
    $defectId = (int)$data['defect_id'];
    $decision = $data['decision'];
    $notes = $data['notes'] ?? null;

    if (!isset($_SESSION['username'])) {
        http_response_code(401);
        throw new Exception("Session expired. Please login.");
    }

    $username = $_SESSION['username'];
    $fullName = $_SESSION['full_name'] ?? $username;

    // --- TRANSACTION START ---
    $conn->beginTransaction();

    try {
        // 1. Find or Create Analyst
        $stmtCheck = $conn->prepare("SELECT UserID FROM Users WHERE Username = ? LIMIT 1");
        $stmtCheck->execute([$username]);
        $user = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $analystId = $user['UserID'];
        } else {
            $stmtCreate = $conn->prepare("INSERT INTO Users (Username, FullName, Role) VALUES (?, ?, 'Analyst')");
            $stmtCreate->execute([$username, $fullName]);
            $analystId = $conn->lastInsertId();
        }

        // 2. Insert Feedback
        $stmtLog = $conn->prepare("INSERT INTO FeedbackLog (DefectID, AnalystUserID, AnalystDecision, AnalystNotes) VALUES (?, ?, ?, ?)");
        $stmtLog->execute([$defectId, $analystId, $decision, $notes]);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Feedback saved.']);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}
?>