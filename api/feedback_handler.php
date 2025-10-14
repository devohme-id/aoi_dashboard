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
        getFeedbackQueue($conn);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}

function getFeedbackQueue($conn)
{
  $searchInput = isset($_GET['search-input']) ? $conn->real_escape_string($_GET['search-input']) : '';

  $query = "
    SELECT
        d.DefectID,
        d.MachineDefectCode,
        d.ComponentRef,
        d.PartNumber,
        d.ImageFileName,
        i.InspectionID,
        i.EndTime,
        i.FinalResult,
        i.Assembly,
        i.LotCode,
        i.LineID,
        pl.LineName,
        op.FullName AS OperatorName
    FROM Defects d
    JOIN Inspections i ON d.InspectionID = i.InspectionID
    LEFT JOIN Users op ON i.OperatorUserID = op.UserID
    LEFT JOIN ProductionLines pl ON i.LineID = pl.LineID
    WHERE d.DefectID NOT IN (
        SELECT DISTINCT DefectID FROM FeedbackLog
    )
";

  if (!empty($searchInput)) {
    $query .= " AND i.Assembly LIKE '%$searchInput%'";
  }

  $query .= " ORDER BY i.EndTime DESC, d.DefectID DESC;";

  $result = $conn->query($query);
  if (!$result) throw new Exception("Query failed: " . $conn->error);

  $data = [];
  while ($row = $result->fetch_assoc()) {
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
    $data[] = $row;
  }

  echo json_encode($data);
}


function handleFeedbackSubmission($conn)
{
    $data = json_decode(file_get_contents('php://input'), true);

    // PERUBAHAN: Validasi sekarang menggunakan defect_id
    if (!isset($data['defect_id']) || !is_numeric($data['defect_id']) || !isset($data['decision']) || empty($data['decision'])) {
        throw new Exception("Invalid input: Defect ID and decision are required.");
    }

    $defectId = (int)$data['defect_id'];
    $analystId = (int)$data['analyst_user_id'];
    $decision = $data['decision'];
    $notes = $data['notes'] ?? null;

    // PERUBAHAN: INSERT sekarang ke kolom DefectID
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