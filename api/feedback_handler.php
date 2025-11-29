<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json');

require_once 'db_config.php';

$criticalDefects = [
    'SHORT SOLDER', 'POOR SOLDER', 'BALL SOLDER',
    'NO SOLDER', 'WRONG POLARITY', 'WRONG COMPONENT'
];

try {
    $database = new Database();
    $conn = $database->connect();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handleFeedbackSubmission($conn);
    } else {
        getFeedbackData($conn, $criticalDefects);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
}

function getFeedbackData(PDO $conn, array $criticalDefects): void
{
    $allLines = $conn->query("SELECT LineID, LineName FROM ProductionLines ORDER BY LineID ASC")
        ->fetchAll(PDO::FETCH_ASSOC);

    $queueQuery = "
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
        LIMIT 300
    ";

    $queueData = [];
    $stmt = $conn->query($queueQuery);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $imageUrl = null;
        if (!empty($row['ImageFileName'])) {
            $parts = preg_split('/[\\\\\\/]/', $row['ImageFileName']);
            if (count($parts) >= 2) {
                $dateFolder = $parts[0];
                $filename = end($parts);
                $imageUrl = sprintf(
                    "api/get_image.php?line=%d&date=%s&file=%s",
                    $row['LineID'],
                    urlencode($dateFolder),
                    urlencode($filename)
                );
            }
        }
        $row['image_url'] = $imageUrl;
        $row['is_critical'] = in_array(strtoupper($row['MachineDefectCode'] ?? ''), $criticalDefects, true);
        $queueData[] = $row;
    }

    echo json_encode([
        'all_lines' => $allLines,
        'verification_queue' => $queueData
    ]);
}

function handleFeedbackSubmission(PDO $conn): void
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['defect_id'], $data['decision'])) {
        throw new Exception("Invalid input parameters.");
    }

    if (!isset($_SESSION['username'])) {
        http_response_code(401);
        throw new Exception("Session expired. Please login.");
    }

    $defectId = (int) $data['defect_id'];
    $decision = $data['decision'];
    $notes = $data['notes'] ?? null;
    $username = $_SESSION['username'];
    $fullName = $_SESSION['full_name'] ?? $username;

    $conn->beginTransaction();

    try {
        $stmtCheck = $conn->prepare("SELECT UserID FROM Users WHERE Username = ? LIMIT 1");
        $stmtCheck->execute([$username]);
        $analystId = $stmtCheck->fetchColumn();

        if (!$analystId) {
            $stmtCreate = $conn->prepare("INSERT INTO Users (Username, FullName, Role) VALUES (?, ?, 'Analyst')");
            $stmtCreate->execute([$username, $fullName]);
            $analystId = $conn->lastInsertId();
        }

        $stmtLog = $conn->prepare("INSERT INTO FeedbackLog (DefectID, AnalystUserID, AnalystDecision, AnalystNotes) VALUES (?, ?, ?, ?)");
        $stmtLog->execute([$defectId, $analystId, $decision, $notes]);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Feedback saved.']);
    } catch (Throwable $e) {
        $conn->rollBack();
        throw $e;
    }
}