<?php

declare(strict_types=1);

require_once 'db_config.php';
header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->connect();

    $isExport = isset($_POST['export']) && $_POST['export'] === 'true';
    $conditions = [];
    $params = [];

    $dateFilter = $_POST['date_filter'] ?? [];
    if (!is_array($dateFilter)) {
        $dateFilter = json_decode((string)$dateFilter, true);
    }

    if (is_array($dateFilter) && count($dateFilter) === 2) {
        $conditions[] = "DATE(i.EndTime) BETWEEN ? AND ?";
        $params[] = $dateFilter[0];
        $params[] = $dateFilter[1];
    }

    if (!empty($_POST['line_filter'])) {
        $conditions[] = "i.LineID = ?";
        $params[] = $_POST['line_filter'];
    }

    $searchValue = $_POST['search']['value'] ?? '';
    if (!empty($searchValue) && !$isExport) {
        $conditions[] = "(i.Assembly LIKE ? OR i.LotCode LIKE ?)";
        $params[] = "%$searchValue%";
        $params[] = "%$searchValue%";
    }

    $whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

    $fromSql = "
        FROM Inspections i
        JOIN ProductionLines pl ON i.LineID = pl.LineID
        LEFT JOIN TuningCycles tc ON i.LineID = tc.LineID AND i.Assembly = tc.Assembly AND i.TuningCycleID = tc.CycleVersion
        LEFT JOIN Users u ON tc.StartedByUserID = u.UserID
    ";
    $groupSql = " GROUP BY i.LineID, i.Assembly, i.LotCode, i.TuningCycleID";

    $stmtTotal = $conn->query("SELECT COUNT(DISTINCT LineID, Assembly, LotCode, TuningCycleID) FROM Inspections");
    $recordsTotal = $stmtTotal->fetchColumn();

    $countSql = "SELECT COUNT(*) FROM (SELECT i.LineID $fromSql $whereSql $groupSql) as sub";
    $stmtCount = $conn->prepare($countSql);
    $stmtCount->execute($params);
    $recordsFiltered = $stmtCount->fetchColumn();

    $selectSql = "
        SELECT
            MAX(i.EndTime) as EndTime,
            pl.LineName,
            i.Assembly,
            i.LotCode,
            i.TuningCycleID,
            MAX(u.FullName) as DebuggerFullName,
            MAX(tc.Notes) as Notes,
            COUNT(i.InspectionID) AS Inspected,
            SUM(i.FinalResult = 'Pass') AS Pass,
            SUM(i.FinalResult = 'Defective') AS Defect,
            SUM(i.FinalResult IN ('False Fail', 'Unreviewed')) AS FalseCall,
            (SUM(i.FinalResult = 'Pass') / COUNT(i.InspectionID)) * 100 AS PassRate,
            (SUM(i.FinalResult = 'Defective') / COUNT(i.InspectionID)) * 1000000 AS PPM
    ";

    $orderSql = " ORDER BY EndTime DESC, i.Assembly ASC ";
    $limitSql = "";

    if (!$isExport) {
        $start = (int)($_POST['start'] ?? 0);
        $length = (int)($_POST['length'] ?? 10);
        $limitSql = " LIMIT $length OFFSET $start";
    }

    $finalSql = $selectSql . $fromSql . $whereSql . $groupSql . $orderSql . $limitSql;
    $stmt = $conn->prepare($finalSql);
    $stmt->execute($params);
    
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['PassRate'] = number_format((float)$row['PassRate'], 2);
        $row['PPM'] = (int)$row['PPM'];
        $row['Notes'] = $row['Notes'] ?? 'Initial Program';
        $row['DebuggerFullName'] = $row['DebuggerFullName'] ?? 'N/A';
        $data[] = $row;
    }

    if ($isExport) {
        echo json_encode($data);
    } else {
        echo json_encode([
            "draw" => (int)($_POST['draw'] ?? 0),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data
        ]);
    }

} catch (Throwable $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['error' => 'Server Error']);
}