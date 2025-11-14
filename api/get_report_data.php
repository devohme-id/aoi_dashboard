<?php
// File: api/get_report_data.php
require_once 'db_config.php';

header('Content-Type: application/json');

try {
    if (isset($_POST['export']) && $_POST['export'] === 'true') {
        handleExport($conn);
    } else {
        handleDataTable($conn);
    }
} catch (Throwable $t) {
    error_log('API Error: ' . $t->getMessage() . ' in ' . $t->getFile() . ' on line ' . $t->getLine());
    http_response_code(500);
    echo json_encode([
        'error' => 'A server error occurred. Please check the server logs for more details.',
        'debug_message' => $t->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

function buildWhereClause($conn)
{
    // ... (Fungsi ini tidak berubah) ...
    $dateFilter = isset($_POST['date_filter']) ? $_POST['date_filter'] : [];
    $lineFilter = isset($_POST['line_filter']) ? $_POST['line_filter'] : '';
    $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    $whereClauses = [];
    $params = [];
    $paramTypes = '';
    if (is_array($dateFilter) && count($dateFilter) == 2) {
        $whereClauses[] = "DATE(i.EndTime) BETWEEN ? AND ?";
        $params[] = $dateFilter[0];
        $params[] = $dateFilter[1];
        $paramTypes .= 'ss';
    }
    if (!empty($lineFilter)) {
        $whereClauses[] = "i.LineID = ?";
        $params[] = $lineFilter;
        $paramTypes .= 'i';
    }
    if (!empty($searchValue)) {
        $searchPattern = "%" . $searchValue . "%";
        $whereClauses[] = "(i.Assembly LIKE ? OR i.LotCode LIKE ?)";
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $paramTypes .= 'ss';
    }
    $whereSql = '';
    if (count($whereClauses) > 0) {
        $whereSql = ' WHERE ' . implode(' AND ', $whereClauses);
    }
    return ['sql' => $whereSql, 'params' => $params, 'types' => $paramTypes];
}

function buildWhereClauseForExport($conn)
{
    // ... (Fungsi ini tidak berubah, karena JS hanya mengirim filter ini) ...
    $dateFilter = isset($_POST['date_filter']) ? json_decode($_POST['date_filter'], true) : [];
    $lineFilter = isset($_POST['line_filter']) ? $_POST['line_filter'] : '';
    $whereClauses = [];
    $params = [];
    $paramTypes = '';
    if (is_array($dateFilter) && count($dateFilter) == 2) {
        $whereClauses[] = "DATE(i.EndTime) BETWEEN ? AND ?";
        $params[] = $dateFilter[0];
        $params[] = $dateFilter[1];
        $paramTypes .= 'ss';
    }
    if (!empty($lineFilter)) {
        $whereClauses[] = "i.LineID = ?";
        $params[] = $lineFilter;
        $paramTypes .= 'i';
    }
    $whereSql = '';
    if (count($whereClauses) > 0) {
        $whereSql = ' WHERE ' . implode(' AND ', $whereClauses);
    }
    return ['sql' => $whereSql, 'params' => $params, 'types' => $paramTypes];
}


function bindParams($stmt, $types, $params)
{
    // ... (Fungsi ini tidak berubah) ...
    if (empty($types) || empty($params)) {
        return;
    }
    $bindArgs = [$types];
    foreach ($params as $key => $value) {
        $bindArgs[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindArgs);
}

function handleDataTable($conn)
{
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;

    $filter = buildWhereClause($conn);
    $whereSql = $filter['sql'];
    $params = $filter['params'];
    $paramTypes = $filter['types'];

    $fromClause = "
        FROM Inspections i
        JOIN ProductionLines pl ON i.LineID = pl.LineID
        LEFT JOIN TuningCycles tc ON i.LineID = tc.LineID AND i.Assembly = tc.Assembly AND i.TuningCycleID = tc.CycleVersion
        LEFT JOIN Users u ON tc.StartedByUserID = u.UserID
    ";

    $groupByClause = "GROUP BY i.LineID, i.Assembly, i.LotCode, i.TuningCycleID";

    $countQuery = "SELECT COUNT(*) as total FROM (SELECT i.LineID " . $fromClause . " " . $whereSql . " " . $groupByClause . ") as subquery";
    $stmt = $conn->prepare($countQuery);
    bindParams($stmt, $paramTypes, $params);
    $stmt->execute();
    $recordsFiltered = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $totalQuery = "SELECT COUNT(*) as total FROM (SELECT i.LineID FROM Inspections i " . $groupByClause . ") as subquery";
    $recordsTotal = $conn->query($totalQuery)->fetch_assoc()['total'];


    $dataQuery = "
        SELECT
            MAX(i.EndTime) as EndTime,
            pl.LineName,
            i.Assembly,
            i.LotCode,
            i.TuningCycleID,
            MAX(u.FullName) as DebuggerFullName,
            MAX(tc.Notes) as Notes,
            COUNT(i.InspectionID) AS Inspected,
            SUM(CASE WHEN i.FinalResult = 'Pass' THEN 1 ELSE 0 END) AS Pass,
            SUM(CASE WHEN i.FinalResult = 'Defective' THEN 1 ELSE 0 END) AS Defect,
            SUM(CASE WHEN i.FinalResult IN ('False Fail', 'Unreviewed') THEN 1 ELSE 0 END) AS FalseCall,
            (SUM(CASE WHEN i.FinalResult = 'Pass' THEN 1 ELSE 0 END) / COUNT(i.InspectionID)) * 100 AS PassRate,
            (SUM(CASE WHEN i.FinalResult = 'Defective' THEN 1 ELSE 0 END) / COUNT(i.InspectionID)) * 1000000 AS PPM
        " . $fromClause . " " . $whereSql . " " . $groupByClause . " ORDER BY EndTime DESC, i.Assembly ASC, i.LotCode ASC, i.TuningCycleID ASC LIMIT ?, ?";

    $stmt = $conn->prepare($dataQuery);
    $limitParams = [$start, $length > 0 ? $length : 1000000];
    bindParams($stmt, $paramTypes . 'ii', array_merge($params, $limitParams));
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // --- ▼▼▼ REVISI FORMAT ANGKA (Standardisasi 2 desimal) ▼▼▼ ---
        $row['PassRate'] = number_format($row['PassRate'], 2);
        // --- ▲▲▲ SELESAI ▲▲▲ ---
        $row['PPM'] = (int)$row['PPM'];
        $row['Notes'] = $row['Notes'] ?? 'Initial Program';
        $row['DebuggerFullName'] = $row['DebuggerFullName'] ?? 'N/A';
        $data[] = $row;
    }
    $stmt->close();

    echo json_encode(["draw" => $draw, "recordsTotal" => $recordsTotal, "recordsFiltered" => $recordsFiltered, "data" => $data]);
}

function handleExport($conn)
{
    $filter = buildWhereClauseForExport($conn);
    $whereSql = $filter['sql'];
    $params = $filter['params'];
    $paramTypes = $filter['types'];

    $fromClause = "
        FROM Inspections i
        JOIN ProductionLines pl ON i.LineID = pl.LineID
        LEFT JOIN TuningCycles tc ON i.LineID = tc.LineID AND i.Assembly = tc.Assembly AND i.TuningCycleID = tc.CycleVersion
        LEFT JOIN Users u ON tc.StartedByUserID = u.UserID
    ";

    $groupByClause = "GROUP BY i.LineID, i.Assembly, i.LotCode, i.TuningCycleID";

    // --- ▼▼▼ REVISI QUERY EXPORT (Dibuat identik dengan DataTable) ▼▼▼ ---
    $dataQuery = "
        SELECT
            MAX(i.EndTime) as EndTime,
            pl.LineName,
            i.Assembly,
            i.LotCode,
            i.TuningCycleID,
            MAX(u.FullName) as DebuggerFullName,
            MAX(tc.Notes) as Notes,
            COUNT(i.InspectionID) AS Inspected,
            SUM(CASE WHEN i.FinalResult = 'Pass' THEN 1 ELSE 0 END) AS Pass,
            SUM(CASE WHEN i.FinalResult = 'Defective' THEN 1 ELSE 0 END) AS Defect,
            SUM(CASE WHEN i.FinalResult IN ('False Fail', 'Unreviewed') THEN 1 ELSE 0 END) AS FalseCall,
            (SUM(CASE WHEN i.FinalResult = 'Pass' THEN 1 ELSE 0 END) / COUNT(i.InspectionID)) * 100 AS PassRate,
            (SUM(CASE WHEN i.FinalResult = 'Defective' THEN 1 ELSE 0 END) / COUNT(i.InspectionID)) * 1000000 AS PPM
        " . $fromClause . " " . $whereSql . " " . $groupByClause . " ORDER BY EndTime DESC, i.Assembly ASC, i.LotCode ASC, i.TuningCycleID ASC";
    // --- ▲▲▲ SELESAI ▲▲▲ ---

    $stmt = $conn->prepare($dataQuery);
    bindParams($stmt, $paramTypes, $params);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    // --- ▼▼▼ REVISI LOOP EXPORT (Dibuat identik dengan DataTable) ▼▼▼ ---
    while ($row = $result->fetch_assoc()) {
        $row['PassRate'] = number_format($row['PassRate'], 2);
        $row['PPM'] = (int)$row['PPM'];
        $row['Notes'] = $row['Notes'] ?? 'Initial Program';
        $row['DebuggerFullName'] = $row['DebuggerFullName'] ?? 'N/A';
        $data[] = $row;
    }
    // --- ▲▲▲ SELESAI ▲▲▲ ---

    $stmt->close();
    echo json_encode($data);
}
?>