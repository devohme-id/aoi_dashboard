<?php

declare(strict_types=1);

require_once __DIR__ . '/db_config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $cacheFile = sys_get_temp_dir() . '/aoi_dashboard_cache.json';
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3) {
        readfile($cacheFile);
        exit;
    }

    $database = new Database();
    $conn = $database->connect();

    $data = getDashboardData($conn);
    $jsonOutput = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_SLASHES);

    file_put_contents($cacheFile, $jsonOutput, LOCK_EX);
    echo $jsonOutput;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'API Error: ' . $e->getMessage()]);
}

function getDashboardData(PDO $conn): array
{
    $criticalDefects = [
        'SHORT SOLDER', 'POOR SOLDER', 'BALL SOLDER', 
        'NO SOLDER', 'WRONG POLARITY', 'WRONG COMPONENT'
    ];

    $response = ['lines' => []];

    $sql = "
        WITH LatestPanel AS (
            SELECT i.*, d.ComponentRef, d.PartNumber, d.ReworkDefectCode, d.MachineDefectCode, d.ImageFileName,
                   ROW_NUMBER() OVER (PARTITION BY i.LineID ORDER BY i.EndTime DESC) AS rn
            FROM Inspections i
            LEFT JOIN Defects d ON i.InspectionID = d.InspectionID
            WHERE i.RecordTimestamp >= CURDATE()
            AND i.RecordTimestamp < CURDATE() + INTERVAL 1 DAY
        )
        SELECT * FROM LatestPanel WHERE rn = 1
    ";

    $stmt = $conn->query($sql);
    $latestPanels = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $latestPanels[$row['LineID']] = $row;
    }

    for ($i = 1; $i <= 6; $i++) {
        $panelData = $latestPanels[$i] ?? null;
        
        $lineData = [
            'status' => 'INACTIVE',
            'details' => createDefaultDetails(),
            'kpi' => createDefaultKpi(),
            'comparison_data' => createDefaultComparison(),
            'image_url' => null,
            'is_critical_alert' => false
        ];

        if ($panelData) {
            $lineData['status'] = $panelData['FinalResult'] ?? 'INACTIVE';
            $lineData['details'] = [
                'time' => $panelData['EndTime'] ? date('H:i:s', strtotime($panelData['EndTime'])) : 'N/A',
                'component_ref' => $panelData['ComponentRef'] ?? 'N/A',
                'part_number' => $panelData['PartNumber'] ?? 'N/A',
                'machine_defect' => $panelData['MachineDefectCode'] ?? 'N/A',
                'inspection_result' => $panelData['InitialResult'] ?? 'N/A',
                'review_result' => $panelData['FinalResult'] ?? 'N/A'
            ];

            $defectCode = strtoupper(trim($panelData['MachineDefectCode'] ?? ''));
            $lineData['is_critical_alert'] = in_array($defectCode, $criticalDefects, true);

            if (!empty($panelData['ImageFileName'])) {
                $parts = preg_split('/[\\\\\\/]/', $panelData['ImageFileName']);
                if (count($parts) >= 2) {
                    $dateFolder = $parts[0];
                    $filename = end($parts);
                    $lineData['image_url'] = sprintf(
                        "api/get_image.php?line=%d&date=%s&file=%s",
                        $i,
                        urlencode($dateFolder),
                        urlencode($filename)
                    );
                }
            }

            $currentAssembly = $panelData['Assembly'] ?? '';
            $currentLot = $panelData['LotCode'] ?? '';
            $currentCycle = (int) ($panelData['TuningCycleID'] ?? 0);

            if ($currentAssembly && $currentLot && $currentCycle >= 0) {
                $lineData['kpi'] = getKpi($conn, $i, $currentAssembly, $currentLot, $currentCycle);
                $lineData['comparison_data']['current'] = $lineData['kpi'];

                if ($currentCycle > 1) {
                    $lineData['comparison_data']['before'] = getKpi($conn, $i, $currentAssembly, $currentLot, $currentCycle - 1);
                }
            }
        }

        $response['lines']['line_' . $i] = $lineData;
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
    $inspected = (int) ($result['Inspected'] ?? 0);
    $pass = (int) ($result['Pass'] ?? 0);
    $defect = (int) ($result['Defect'] ?? 0);
    $falseCall = (int) ($result['FalseCall'] ?? 0);

    return [
        'assembly' => $result['Assembly'] ?? 'N/A',
        'lot_code' => $result['LotCode'] ?? 'N/A',
        'total_inspected' => $inspected,
        'total_pass' => $pass,
        'total_defect' => $defect,
        'total_false_call' => $falseCall,
        'pass_rate' => $inspected > 0 ? round(($pass / $inspected) * 100, 2) : 0,
        'ppm' => $inspected > 0 ? (int) (($defect / $inspected) * 1000000) : 0
    ];
}

function createDefaultDetails(): array
{
    return [
        'time' => 'N/A', 'component_ref' => 'N/A', 'part_number' => 'N/A',
        'machine_defect' => 'N/A', 'inspection_result' => 'N/A', 'review_result' => 'N/A'
    ];
}

function createDefaultKpi(): array
{
    return [
        'assembly' => 'N/A', 'lot_code' => 'N/A', 'total_inspected' => 0,
        'total_pass' => 0, 'total_defect' => 0, 'total_false_call' => 0, 'pass_rate' => 0, 'ppm' => 0
    ];
}

function createDefaultComparison(): array
{
    return ['before' => createDefaultKpi(), 'current' => createDefaultKpi()];
}