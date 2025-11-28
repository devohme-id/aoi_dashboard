<?php
/**
 * File: api/get_dashboard_data.php
 * Purpose: Provide structured AOI dashboard data with optimized PDO access
 */

$debug_mode = false;

if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/db_config.php';

try {
    // --- Cache Layer (3 detik) ---
    $cache_file = sys_get_temp_dir() . '/aoi_dashboard_cache.json';
    $cache_lifetime = 3; 

    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_lifetime) {
        // Return cached data
        readfile($cache_file);
        exit;
    }

    // Init Database
    $database = new Database();
    $conn = $database->connect();

    // Fetch Data
    $data = getDashboardData($conn);
    $json_output = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_SLASHES);

    // Save Cache
    file_put_contents($cache_file, $json_output, LOCK_EX);

    echo $json_output;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'API Error: ' . $e->getMessage()]);
}

// =====================================================================
// Core Functions
// =====================================================================

function getDashboardData(PDO $conn): array
{
    $critical_defects = [
        'SHORT SOLDER', 'POOR SOLDER', 'BALL SOLDER', 
        'NO SOLDER', 'WRONG POLARITY', 'WRONG COMPONENT'
    ];

    $response = ['lines' => []];

    // 1. Query Panel Terakhir (Optimized Window Function)
    $sql = "
        WITH LatestPanel AS (
            SELECT i.*, d.ComponentRef, d.PartNumber, d.ReworkDefectCode, d.MachineDefectCode, d.ImageFileName,
                   ROW_NUMBER() OVER (PARTITION BY i.LineID ORDER BY i.EndTime DESC) AS rn
            FROM Inspections i
            LEFT JOIN Defects d ON i.InspectionID = d.InspectionID
            WHERE i.RecordTimestamp >= CURDATE()
            AND i.RecordTimestamp < CURDATE() + INTERVAL 1 DAY
        )
        SELECT * FROM LatestPanel WHERE rn = 1;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $latest_panels = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $latest_panels[$row['LineID']] = $row;
    }

    // 2. Loop Lines 1-6
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

            // Cek Critical Defect
            $defectCode = strtoupper(trim($panel_data['MachineDefectCode'] ?? ''));
            $line_data['is_critical_alert'] = in_array($defectCode, $critical_defects, true);

            // Build Image URL
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

            // KPI & Comparison
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

function getKpi(PDO $conn, int $line, string $assembly, string $lot, int $cycle): array
{
    $sql = "
        SELECT Assembly, LotCode, COUNT(InspectionID) AS Inspected,
               SUM(FinalResult = 'Pass') AS Pass,
               SUM(FinalResult = 'Defective') AS Defect,
               SUM(FinalResult IN ('False Fail', 'Unreviewed')) AS FalseCall
        FROM Inspections
        WHERE LineID = ? AND Assembly = ? AND LotCode = ? AND TuningCycleID = ?
        GROUP BY Assembly, LotCode
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$line, $assembly, $lot, $cycle]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

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

function createDefaultDetails(): array {
    return [
        'time' => 'N/A', 'component_ref' => 'N/A', 'part_number' => 'N/A',
        'machine_defect' => 'N/A', 'inspection_result' => 'N/A', 'review_result' => 'N/A'
    ];
}

function createDefaultKpi(): array {
    return [
        'assembly' => 'N/A', 'lot_code' => 'N/A', 'total_inspected' => 0,
        'total_pass' => 0, 'total_defect' => 0, 'total_false_call' => 0, 'pass_rate' => 0, 'ppm' => 0
    ];
}

function createDefaultComparison(): array {
    return ['before' => createDefaultKpi(), 'current' => createDefaultKpi()];
}
?>