<?php
// File: api/get_dashboard_data.php

$debug_mode = false;

if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json');
}

try {
    require_once __DIR__ . '/db_config.php';
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database Connection Failed: " . ($conn->connect_error ?? "Unknown error"));
    }
    $data = getDashboardData($conn);
    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    $error_response = ['error' => 'API Error: ' . $e->getMessage()];
    if ($debug_mode) {
        echo "<pre>" . print_r($error_response, true) . "</pre>";
    } else {
        echo json_encode($error_response);
    }
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}

function getDashboardData($conn)
{
    define('CRITICAL_DEFECTS', ['SHORT SOLDER', 'POOR SOLDER', 'BALL SOLDER', 'NO SOLDER', 'WRONG POLARITY', 'WRONG COMPONENT']);
    $response = ['lines' => []];

    // Query 1: Ambil detail panel terakhir (tidak berubah)
    $panel_query = "
        WITH LatestPanel AS (
            SELECT i.*, d.ComponentRef, d.PartNumber, d.ReworkDefectCode, d.MachineDefectCode, d.ImageFileName,
                   ROW_NUMBER() OVER(PARTITION BY i.LineID ORDER BY i.EndTime DESC) as rn
            FROM Inspections i LEFT JOIN Defects d ON i.InspectionID = d.InspectionID
        )
        SELECT * FROM LatestPanel WHERE rn = 1;";
    $panel_result = $conn->query($panel_query);
    $latest_panels = [];
    while ($row = $panel_result->fetch_assoc()) {
        $latest_panels[$row['LineID']] = $row;
    }

    // Gabungkan semua data per line
    for ($i = 1; $i <= 6; $i++) {
        $panel_data = $latest_panels[$i] ?? null;

        // Inisialisasi data default
        $line_data = [
            'status' => 'INACTIVE',
            'details' => createDefaultDetails(),
            'kpi' => createDefaultKpi(),
            'comparison_data' => createDefaultComparison(),
            'image_url' => null,
            'is_critical_alert' => false
        ];

        if ($panel_data) {
            $line_data['status'] = $panel_data['FinalResult'] ?? 'INACTIVE';
            $line_data['details'] = [
                'time' => $panel_data['EndTime'] ? date('H:i:s', strtotime($panel_data['EndTime'])) : 'N/A',
                'component_ref' => $panel_data['ComponentRef'] ?? 'N/A',
                'part_number' => $panel_data['PartNumber'] ?? 'N/A',
                'machine_defect' => $panel_data['MachineDefectCode'] ?? 'N/A',
                'inspection_result' => $panel_data['InitialResult'] ?? 'N/A',
                'review_result' => $panel_data['FinalResult'] ?? 'N/A'
            ];
            $line_data['is_critical_alert'] = in_array(strtoupper($panel_data['MachineDefectCode'] ?? ''), CRITICAL_DEFECTS);

            if (!empty($panel_data['ImageFileName'])) {
                $path_parts = explode('\\', $panel_data['ImageFileName']);
                if (count($path_parts) >= 2) {
                    $date_folder = $path_parts[0];
                    $actual_filename = end($path_parts);
                    $line_data['image_url'] = "api/get_image.php?line=" . $i . "&date=" . urlencode($date_folder) . "&file=" . urlencode($actual_filename);
                }
            }

            // Variabel kunci untuk query selanjutnya
            $current_assembly = $panel_data['Assembly'];
            $current_lot = $panel_data['LotCode'];
            $current_cycle = (int)$panel_data['TuningCycleID'];

            // ** POINT 1: KPI sekarang dihitung berdasarkan SIKLUS AKTIF **
            $kpi_query = $conn->prepare("
                SELECT Assembly, LotCode, COUNT(InspectionID) AS Inspected,
                       SUM(CASE WHEN FinalResult = 'Pass' THEN 1 ELSE 0 END) AS Pass,
                       SUM(CASE WHEN FinalResult = 'Defective' THEN 1 ELSE 0 END) AS Defect,
                       SUM(CASE WHEN FinalResult IN ('False Fail', 'Unreviewed') THEN 1 ELSE 0 END) AS FalseCall
                FROM Inspections
                WHERE LineID = ? AND Assembly = ? AND LotCode = ? AND TuningCycleID = ?
                GROUP BY Assembly, LotCode
            ");
            $kpi_query->bind_param("issi", $i, $current_assembly, $current_lot, $current_cycle);
            $kpi_query->execute();
            $kpi_result = $kpi_query->get_result()->fetch_assoc();
            if ($kpi_result) {
                $line_data['kpi'] = calculateKpiMetrics($kpi_result);
            }

            // ** POINT 2: Ambil data untuk GRAFIK PERBANDINGAN BARU **
            $line_data['comparison_data']['current'] = $line_data['kpi'];

            if ($current_cycle > 1) {
                $previous_cycle = $current_cycle - 1;
                $before_query = $conn->prepare("
                    SELECT Assembly, LotCode, COUNT(InspectionID) AS Inspected,
                           SUM(CASE WHEN FinalResult = 'Pass' THEN 1 ELSE 0 END) AS Pass,
                           SUM(CASE WHEN FinalResult = 'Defective' THEN 1 ELSE 0 END) AS Defect,
                           SUM(CASE WHEN FinalResult IN ('False Fail', 'Unreviewed') THEN 1 ELSE 0 END) AS FalseCall
                    FROM Inspections
                    WHERE LineID = ? AND Assembly = ? AND LotCode = ? AND TuningCycleID = ?
                    GROUP BY Assembly, LotCode
                ");
                $before_query->bind_param("issi", $i, $current_assembly, $current_lot, $previous_cycle);
                $before_query->execute();
                $before_result = $before_query->get_result()->fetch_assoc();
                if ($before_result) {
                    $line_data['comparison_data']['before'] = calculateKpiMetrics($before_result);
                }
            }
        }
        $response['lines']['line_' . $i] = $line_data;
    }
    return $response;
}

// Helper functions
function calculateKpiMetrics($result)
{
    $inspected = (int)($result['Inspected'] ?? 0);
    $pass = (int)($result['Pass'] ?? 0);
    $defect = (int)($result['Defect'] ?? 0);
    return [
        'assembly' => $result['Assembly'] ?? 'N/A',
        'lot_code' => $result['LotCode'] ?? 'N/A',
        'total_inspected' => $inspected,
        'total_pass' => $pass,
        'total_defect' => $defect,
        'total_false_call' => (int)($result['FalseCall'] ?? 0),
        'pass_rate' => ($inspected > 0) ? round(($pass / $inspected) * 100, 2) : 0,
        'ppm' => ($inspected > 0) ? (int)(($defect / $inspected) * 1000000) : 0
    ];
}

function createDefaultDetails()
{
    return ['time' => 'N/A', 'component_ref' => 'N/A', 'part_number' => 'N/A', 'machine_defect' => 'N/A', 'inspection_result' => 'N/A', 'review_result' => 'N/A'];
}

function createDefaultKpi()
{
    return ['assembly' => 'N/A', 'lot_code' => 'N/A', 'total_inspected' => 0, 'total_pass' => 0, 'total_defect' => 0, 'total_false_call' => 0, 'pass_rate' => 0, 'ppm' => 0];
}

function createDefaultComparison()
{
    return ['before' => createDefaultKpi(), 'current' => createDefaultKpi()];
}
