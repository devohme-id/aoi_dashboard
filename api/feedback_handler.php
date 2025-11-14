<?php
// File: api/feedback_handler.php

session_start(); // Diperlukan untuk membaca session

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
    // ... (Fungsi getFeedbackData Anda tidak berubah, biarkan apa adanya) ...
    $lines_query = "SELECT LineID, LineName FROM ProductionLines ORDER BY LineID ASC";
    $lines_result = $conn->query($lines_query);
    if (!$lines_result) throw new Exception("Query failed [lines]: " . $conn->error);
    $all_lines = [];
    while ($row = $lines_result->fetch_assoc()) {
        $all_lines[] = $row;
    }

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

    echo json_encode([
        'all_lines' => $all_lines,
        'verification_queue' => $queue_data
    ]);
}


/**
 * Mengurus submisi feedback dengan logika 'Find or Create User'
 */
function handleFeedbackSubmission($conn)
{
    $data = json_decode(file_get_contents('php://input'), true);

    // 1. Validasi Input dari Client
    if (!isset($data['defect_id']) || !is_numeric($data['defect_id']) || !isset($data['decision']) || empty($data['decision'])) {
        throw new Exception("Invalid input: Defect ID and decision are required.");
    }
    $defectId = (int)$data['defect_id'];
    $decision = $data['decision'];
    $notes = $data['notes'] ?? null;

    // 2. Validasi Session (Sumber Terpercaya)
    if (!isset($_SESSION['username']) || !isset($_SESSION['full_name'])) {
        throw new Exception("User session incomplete (username/fullname missing). Please log in again.");
    }
    $analystUsername = $_SESSION['username'];
    $analystFullName = $_SESSION['full_name']; // Ini dari Langkah 1 (auth.php)


    // --- ▼▼▼ INI SOLUSI BARU ANDA ▼▼▼ ---

    $localAnalystId = null;

    // 3. Cek apakah user SUDAH ADA di tabel 'Users' lokal berdasarkan USERNAME
    $stmt_check = $conn->prepare("SELECT UserID FROM Users WHERE Username = ?");
    if (!$stmt_check) throw new Exception("Prepare failed [check]: " . $conn->error);
    $stmt_check->bind_param("s", $analystUsername);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        // --- User DITEMUKAN ---
        $row = $result->fetch_assoc();
        $localAnalystId = (int)$row['UserID'];
    } else {
        // --- User TIDAK DITEMUKAN, BUAT BARU ---

        // PENTING: Sesuaikan query ini dengan struktur tabel 'Users' Anda.
        // Kolom UserID harus AUTO_INCREMENT.
        $stmt_create = $conn->prepare(
            "INSERT INTO Users (Username, FullName, Password, Role)
             VALUES (?, ?, 'password_default_sync', 'Analyst')"
        );
        if (!$stmt_create) throw new Exception("Prepare failed [create]: " . $conn->error);

        // Bind Username dan FullName dari session
        $stmt_create->bind_param("ss", $analystUsername, $analystFullName);

        if (!$stmt_create->execute()) {
            throw new Exception("Execute failed [create]: " . $stmt_create->error);
        }

        // Ambil ID baru yang di-generate oleh AUTO_INCREMENT
        $localAnalystId = (int)$conn->insert_id;
        $stmt_create->close();
    }
    $stmt_check->close();

    // 4. Pastikan kita punya ID lokal yang valid
    if ($localAnalystId === null || $localAnalystId === 0) {
        throw new Exception("Failed to find or create a valid local analyst ID.");
    }

    // --- ▲▲▲ SOLUSI SELESAI ▲▲▲ ---


    // 5. Lanjutkan INSERT ke FeedbackLog menggunakan ID LOKAL
    $stmt_log = $conn->prepare(
        "INSERT INTO FeedbackLog (DefectID, AnalystUserID, AnalystDecision, AnalystNotes) VALUES (?, ?, ?, ?)"
    );
    if (!$stmt_log) throw new Exception("Prepare statement failed [log]: " . $conn->error);

    // Gunakan $localAnalystId di sini
    $stmt_log->bind_param("iiss", $defectId, $localAnalystId, $decision, $notes);

    if ($stmt_log->execute()) {
        echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully.']);
    } else {
        throw new Exception("Execute failed [log]: " . $stmt_log->error);
    }
    $stmt_log->close();
}