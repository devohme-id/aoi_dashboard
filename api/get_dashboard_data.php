<?php
// File: api/get_dashboard_data.php

// =========================================================================
// SAKLAR DEBUG: Ubah menjadi 'true' untuk melihat error PHP secara detail
// =========================================================================
$debug_mode = false;

// Atur pelaporan error berdasarkan mode debug
if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json');
}

// =========================================================================
// BLOK EKSEKUSI UTAMA DENGAN PENANGANAN ERROR LENGKAP
// =========================================================================
try {
    require_once __DIR__ . '/db_config.php';

    if (!$conn || $conn->connect_error) {
        throw new Exception("Database Connection Failed: " . ($conn->connect_error ?? "Unknown error"));
    }

    $data = getDashboardData($conn);

    if ($debug_mode) {
        header('Content-Type: text/html');
        echo "<pre><strong>--- DEBUG MODE OUTPUT ---</strong>\n\n" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo json_encode($data);
    }
} catch (Exception $e) {
    http_response_code(500);
    $error_response = ['error' => 'API Error: ' . $e->getMessage()];
    if (!$debug_mode) {
        echo json_encode($error_response);
    } else {
        echo "<div style='font-family:sans-serif; padding:15px; border:2px solid red; background-color:#fff0f0;'>";
        echo "<strong>FATAL ERROR:</strong><br><pre>" . print_r($error_response, true) . "</pre>";
        echo "</div>";
    }
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}

function getDashboardData($conn)
{
    // Daftar Defect Kritis
    define('CRITICAL_DEFECTS', [
        'SHORT SOLDER',
        'POOR SOLDER',
        'BALL SOLDER',
        'NO SOLDER',
        'WRONG POLARITY',
        'WRONG COMPONENT'
    ]);

    $response = ['lines' => []];

    // Query 1: Ambil detail panel terakhir
    $panel_query = "
        WITH LatestPanel AS (
            SELECT i.*, d.ComponentRef, d.PartNumber, d.ReworkDefectCode, d.MachineDefectCode, d.ImageFileName,
                   ROW_NUMBER() OVER(PARTITION BY i.LineID ORDER BY i.EndTime DESC) as rn
            FROM Inspections i
            LEFT JOIN Defects d ON i.InspectionID = d.InspectionID
        )
        SELECT * FROM LatestPanel WHERE rn = 1;";
    $panel_result = $conn->query($panel_query);
    if (!$panel_result) throw new Exception("Query Error [panel]: " . $conn->error);
    $latest_panels = [];
    while ($row = $panel_result->fetch_assoc()) {
        $latest_panels[$row['LineID']] = $row;
    }

    // Query 2: Ambil KPI untuk production run TERKINI
    $kpi_query = "
        WITH RankedRuns AS (
            SELECT LineID, Assembly, LotCode, ROW_NUMBER() OVER (PARTITION BY LineID ORDER BY MAX(EndTime) DESC) as rn
            FROM Inspections
            GROUP BY LineID, Assembly, LotCode
        ),
        CurrentRun AS (
            SELECT LineID, Assembly, LotCode FROM RankedRuns WHERE rn = 1
        ),
        RunStats AS (
            SELECT i.LineID,
                   COUNT(i.InspectionID) AS Inspected,
                   SUM(CASE WHEN i.FinalResult = 'Defective' THEN 1 ELSE 0 END) AS Defect,
                   SUM(CASE WHEN i.FinalResult = 'False Fail' THEN 1 ELSE 0 END) AS FalseCall,
                   SUM(CASE WHEN i.FinalResult = 'Pass' THEN 1 ELSE 0 END) AS Pass
            FROM Inspections i
            JOIN CurrentRun cr ON i.LineID = cr.LineID AND i.Assembly = cr.Assembly AND i.LotCode = cr.LotCode
            GROUP BY i.LineID
        )
        SELECT pl.LineID, cr.Assembly, rs.*
        FROM ProductionLines pl
        LEFT JOIN CurrentRun cr ON pl.LineID = cr.LineID
        LEFT JOIN RunStats rs ON pl.LineID = rs.LineID;";
    $kpi_result = $conn->query($kpi_query);
    if (!$kpi_result) throw new Exception("Query Error [kpi]: " . $conn->error);
    $kpis = [];
    while ($row = $kpi_result->fetch_assoc()) {
        $kpis[$row['LineID']] = $row;
    }

    // Query 3: Dapatkan histori pass rate (9 lot terakhir)
    $history_query = "
        WITH RankedLots AS (
            SELECT LineID, LotCode, MAX(EndTime) as LastTime,
                   ROW_NUMBER() OVER (PARTITION BY LineID ORDER BY MAX(EndTime) DESC) as rn
            FROM Inspections
            WHERE LotCode IS NOT NULL AND LotCode <> ''
            GROUP BY LineID, LotCode
        ),
        Last9Lots AS (
            SELECT LineID, LotCode, rn FROM RankedLots WHERE rn BETWEEN 2 AND 10
        ),
        LotStats AS (
            SELECT i.LineID, i.LotCode,
                   COUNT(i.InspectionID) as Total,
                   SUM(CASE WHEN i.FinalResult = 'Pass' THEN 1 ELSE 0 END) as Passed
            FROM Inspections i
            JOIN Last9Lots l9 ON i.LineID = l9.LineID AND i.LotCode = l9.LotCode
            GROUP BY i.LineID, i.LotCode
        )
        SELECT l9.LineID, l9.LotCode, (ls.Passed / ls.Total) * 100 as PassRate
        FROM Last9Lots l9
        JOIN LotStats ls ON l9.LineID = ls.LineID AND l9.LotCode = ls.LotCode
        ORDER BY l9.LineID, l9.rn DESC;";
    $history_result = $conn->query($history_query);
    if (!$history_result) throw new Exception("Query Error [history]: " . $conn->error);
    $histories = [];
    while ($row = $history_result->fetch_assoc()) {
        if (!isset($histories[$row['LineID']])) {
            $histories[$row['LineID']] = ['labels' => [], 'data' => []];
        }
        $histories[$row['LineID']]['labels'][] = $row['LotCode'];
        $histories[$row['LineID']]['data'][] = round($row['PassRate']);
    }

    // Gabungkan semua data
    for ($i = 1; $i <= 6; $i++) {
        $panel_data = $latest_panels[$i] ?? null;
        $kpi_data = $kpis[$i] ?? null;

        $total_inspected = (int)($kpi_data['Inspected'] ?? 0);
        $pass_count = (int)($kpi_data['Pass'] ?? 0);
        $defect_count = (int)($kpi_data['Defect'] ?? 0);

        $image_url = null;
        if ($panel_data && !empty($panel_data['ImageFileName'])) {
            $path_parts = explode('\\', $panel_data['ImageFileName']);
            if (count($path_parts) >= 2) {
                $date_folder = $path_parts[0];
                $actual_filename = end($path_parts);
                $image_url = "api/get_image.php?line=" . $i . "&date=" . urlencode($date_folder) . "&file=" . urlencode($actual_filename);
            }
        }

        // PENAMBAHAN: Cek Critical Defect
        $is_critical_alert = false;
        if ($panel_data && isset($panel_data['MachineDefectCode'])) {
            if (in_array(strtoupper($panel_data['MachineDefectCode']), CRITICAL_DEFECTS)) {
                $is_critical_alert = true;
            }
        }

        $response['lines']['line_' . $i] = [
            'status' => $panel_data['FinalResult'] ?? 'INACTIVE',
            'details' => [
                'time' => $panel_data['EndTime'] ? date('H:i:s', strtotime($panel_data['EndTime'])) : 'N/A',
                'component_ref' => $panel_data['ComponentRef'] ?? 'N/A',
                'part_number' => $panel_data['PartNumber'] ?? 'N/A',
                'machine_defect' => $panel_data['MachineDefectCode'] ?? 'N/A',
                'inspection_result' => $panel_data['InitialResult'] ?? 'N/A',
                'review_result' => $panel_data['FinalResult'] ?? 'N/A'
            ],
            'kpi' => [
                'assembly' => $kpi_data['Assembly'] ?? 'N/A',
                'total_inspected' => $total_inspected,
                'total_pass' => $pass_count,
                'total_false_call' => (int)($kpi_data['FalseCall'] ?? 0),
                'pass_rate' => ($total_inspected > 0) ? number_format(($pass_count / $total_inspected) * 100) : '0',
                'ppm' => ($total_inspected > 0) ? (int)(($defect_count / $total_inspected) * 1000000) : 0
            ],
            'pass_rate_history' => $histories[$i] ?? ['labels' => [], 'data' => []],
            'image_url' => $image_url,
            'is_critical_alert' => $is_critical_alert // Flag baru untuk frontend
        ];
    }

    return $response;
}
