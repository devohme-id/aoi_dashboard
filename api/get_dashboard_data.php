<?php

/**
 * File: api/get_dashboard_data.php
 * Purpose: Provide structured AOI dashboard data with optimized DB access
 * Optimized by: ChatGPT (2025)
 */

$debug_mode = false;

// --- Error Handling Setup ---
if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json; charset=utf-8');
}

// --- Dependencies ---
try {
    require_once __DIR__ . '/db_config.php';

    if (!isset($conn) || !$conn || $conn->connect_error) {
        throw new Exception("Database Connection Failed: " . ($conn->connect_error ?? "Unknown error"));
    }

    // Optional: reduce MySQL memory usage
    $conn->query("SET SESSION sql_mode = ''");

    // --- Cache Layer (lightweight, 3 seconds default) ---
    $cache_file = sys_get_temp_dir() . '/aoi_dashboard_cache.json';
    $cache_lifetime = 3; // seconds

    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_lifetime) {
        echo file_get_contents($cache_file);
        exit;
    }

    $data = getDashboardData($conn);
    $json_output = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_SLASHES);

    // Cache result
    file_put_contents($cache_file, $json_output, LOCK_EX);

    echo $json_output;
} catch (Exception $e) {
    http_response_code(500);
    $error_response = ['error' => 'API Error: ' . $e->getMessage()];
    if ($debug_mode) {
        echo "<pre>" . print_r($error_response, true) . "</pre>";
    } else {
        echo json_encode($error_response);
    }
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

// =====================================================================
// Core Function
// =====================================================================
function getDashboardData(mysqli $conn): array
{
    define('CRITICAL_DEFECTS', [
        'SHORT SOLDER',
        'POOR SOLDER',
        'BALL SOLDER',
        'NO SOLDER',
        'WRONG POLARITY',
        'WRONG COMPONENT'
    ]);

    $response = ['lines' => []];

    // --- 1️⃣ Query panel terakhir per line ---
    $panel_query = "
        WITH LatestPanel AS (
            SELECT i.LineID, i.EndTime,i.InspectionID , d.ComponentRef, d.PartNumber, d.ReworkDefectCode, d.MachineDefectCode, d.ImageFileName,
                   ROW_NUMBER() OVER (PARTITION BY i.LineID ORDER BY i.EndTime DESC) AS rn
            FROM Inspections i
            LEFT JOIN Defects d ON i.InspectionID = d.InspectionID
            WHERE i.RecordTimestamp BETWEEN CURDATE() - INTERVAL 1 DAY AND CURDATE()
        )
        SELECT * FROM LatestPanel WHERE rn = 1;
    ";

    $panel_result = $conn->query($panel_query);

    if (!$panel_result) {
        throw new Exception("Panel query failed: " . $conn->error);
    }

    $latest_panels = [];
    while ($row = $panel_result->fetch_assoc()) {
        $latest_panels[$row['LineID']] = $row;
    }
    $panel_result->free();

    // --- 2️⃣ Loop untuk setiap line 1–6 ---
    for ($i = 1; $i <= 6; $i++) {
        $panel_data = $latest_panels[$i] ?? null;

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

            $line_data['is_critical_alert'] = in_array(strtoupper(trim($panel_data['MachineDefectCode'] ?? '')), CRITICAL_DEFECTS, true);

            // --- Build Image URL safely ---
            if (!empty($panel_data['ImageFileName'])) {
                $path_parts = preg_split('/[\\\\\\/]/', $panel_data['ImageFileName']);
                if (count($path_parts) >= 2) {
                    $date_folder = $path_parts[0];
                    $actual_filename = end($path_parts);
                    $line_data['image_url'] = sprintf(
                        "api/get_image.php?line=%d&date=%s&file=%s",
                        $i,
                        urlencode($date_folder),
                        urlencode($actual_filename)
                    );
                }
            }

            // --- KPI & Comparison ---
            $current_assembly = $panel_data['Assembly'] ?? '';
            $current_lot = $panel_data['LotCode'] ?? '';
            $current_cycle = (int)($panel_data['TuningCycleID'] ?? 0);

            if ($current_assembly && $current_lot && $current_cycle >= 0) {
                $line_data['kpi'] = getKpi($conn, $i, $current_assembly, $current_lot, $current_cycle);
                $line_data['comparison_data']['current'] = $line_data['kpi'];

                if ($current_cycle > 1) {
                    $line_data['comparison_data']['before'] = getKpi($conn, $i, $current_assembly, $current_lot, $current_cycle - 1);
                }
            }
        }

        $response['lines']['line_' . $i] = $line_data;
    }

    return $response;
}

// =====================================================================
// Helper Functions
// =====================================================================
function getKpi(mysqli $conn, int $line, string $assembly, string $lot, int $cycle): array
{
    static $stmt = null;
    if (!$stmt) {
        $stmt = $conn->prepare("
            SELECT Assembly, LotCode, COUNT(InspectionID) AS Inspected,
                   SUM(FinalResult = 'Pass') AS Pass,
                   SUM(FinalResult = 'Defective') AS Defect,
                   SUM(FinalResult IN ('False Fail', 'Unreviewed')) AS FalseCall
            FROM Inspections
            WHERE LineID = ? AND Assembly = ? AND LotCode = ? AND TuningCycleID = ?
            GROUP BY Assembly, LotCode
        ");
    }

    $stmt->bind_param("issi", $line, $assembly, $lot, $cycle);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->free_result();

    return calculateKpiMetrics($result);
}

function calculateKpiMetrics(array $result): array
{
    $inspected = (int)($result['Inspected'] ?? 0);
    $pass = (int)($result['Pass'] ?? 0);
    $defect = (int)($result['Defect'] ?? 0);
    $false_call = (int)($result['FalseCall'] ?? 0);

    return [
        'assembly' => $result['Assembly'] ?? 'N/A',
        'lot_code' => $result['LotCode'] ?? 'N/A',
        'total_inspected' => $inspected,
        'total_pass' => $pass,
        'total_defect' => $defect,
        'total_false_call' => $false_call,
        'pass_rate' => $inspected > 0 ? round(($pass / $inspected) * 100, 2) : 0,
        'ppm' => $inspected > 0 ? (int)(($defect / $inspected) * 1000000) : 0
    ];
}

function createDefaultDetails(): array
{
    return [
        'time' => 'N/A',
        'component_ref' => 'N/A',
        'part_number' => 'N/A',
        'machine_defect' => 'N/A',
        'inspection_result' => 'N/A',
        'review_result' => 'N/A'
    ];
}

function createDefaultKpi(): array
{
    return [
        'assembly' => 'N/A',
        'lot_code' => 'N/A',
        'total_inspected' => 0,
        'total_pass' => 0,
        'total_defect' => 0,
        'total_false_call' => 0,
        'pass_rate' => 0,
        'ppm' => 0
    ];
}

function createDefaultComparison(): array
{
    return [
        'before' => createDefaultKpi(),
        'current' => createDefaultKpi()
    ];
}