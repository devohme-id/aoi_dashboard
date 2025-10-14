<?php
// File: api/get_summary_data.php
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
        'error' => 'A server error occurred.',
        'debug_message' => $t->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

function buildWhereClause($conn, $isExport = false)
{
    $params = $_POST;

    $dateFilter = $params['date_filter'] ?? [];
    if (is_string($dateFilter)) $dateFilter = json_decode($dateFilter, true);

    $lineFilter = $params['line_filter'] ?? '';
    $analystFilter = $params['analyst_filter'] ?? '';
    $operatorFilter = $params['operator_filter'] ?? '';
    $searchValue = !$isExport ? ($params['search']['value'] ?? '') : '';

    $whereClauses = [];
    $queryParams = [];
    $paramTypes = '';

    if (is_array($dateFilter) && count($dateFilter) == 2) {
        $whereClauses[] = "DATE(fb.VerificationTimestamp) BETWEEN ? AND ?";
        $queryParams[] = $dateFilter[0];
        $queryParams[] = $dateFilter[1];
        $paramTypes .= 'ss';
    }
    if (!empty($lineFilter)) $whereClauses[] = "i.LineID = ?";
    if (!empty($analystFilter)) $whereClauses[] = "fb.AnalystUserID = ?";
    if (!empty($operatorFilter)) $whereClauses[] = "i.OperatorUserID = ?";

    foreach (['line_filter' => 'i', 'analyst_filter' => 'i', 'operator_filter' => 'i'] as $key => $type) {
        if (!empty($params[$key])) {
            $queryParams[] = $params[$key];
            $paramTypes .= $type;
        }
    }

    if (!empty($searchValue)) {
        $searchPattern = "%" . $searchValue . "%";
        $whereClauses[] = "(i.Assembly LIKE ? OR d.MachineDefectCode LIKE ? OR fb.AnalystDecision LIKE ?)";
        $queryParams = array_merge($queryParams, [$searchPattern, $searchPattern, $searchPattern]);
        $paramTypes .= 'sss';
    }

    $whereSql = '';
    if (count($whereClauses) > 0) {
        $whereSql = ' WHERE ' . implode(' AND ', $whereClauses);
    }

    return ['sql' => $whereSql, 'params' => $queryParams, 'types' => $paramTypes];
}

function bindParams($stmt, $types, $params)
{
    if (empty($types) || empty($params)) return;
    $stmt->bind_param($types, ...$params);
}

// PERUBAHAN TOTAL: Query sekarang berbasis DefectID dari FeedbackLog
function buildBaseQuery()
{
    return "
        FROM FeedbackLog fb
        JOIN Defects d ON fb.DefectID = d.DefectID
        JOIN Inspections i ON d.InspectionID = i.InspectionID
        LEFT JOIN Users analyst ON fb.AnalystUserID = analyst.UserID
        LEFT JOIN Users operator ON i.OperatorUserID = operator.UserID
        LEFT JOIN ProductionLines pl ON i.LineID = pl.LineID
    ";
}

function handleDataTable($conn)
{
    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);

    $filter = buildWhereClause($conn);
    $whereSql = $filter['sql'];
    $params = $filter['params'];
    $paramTypes = $filter['types'];

    $fromClause = buildBaseQuery();

    $countQuery = "SELECT COUNT(fb.FeedbackID) as total " . $fromClause . $whereSql;
    $stmt = $conn->prepare($countQuery);
    if (!$stmt) throw new Exception("Prepare failed (count): " . $conn->error);
    bindParams($stmt, $paramTypes, $params);
    $stmt->execute();
    $recordsFiltered = $stmt->get_result()->fetch_assoc()['total'];

    $totalQuery = "SELECT COUNT(FeedbackID) as total FROM FeedbackLog";
    $recordsTotal = $conn->query($totalQuery)->fetch_assoc()['total'];

    $dataQuery = "
        SELECT 
            fb.VerificationTimestamp,
            analyst.FullName AS AnalystName,
            operator.FullName AS OperatorName,
            pl.LineName,
            i.Assembly,
            i.LotCode,
            d.MachineDefectCode,
            i.FinalResult AS OperatorResult,
            fb.AnalystDecision,
            fb.AnalystNotes
        " . $fromClause . $whereSql . " ORDER BY fb.VerificationTimestamp DESC LIMIT ?, ?";

    $stmt = $conn->prepare($dataQuery);
    if (!$stmt) throw new Exception("Prepare failed (data): " . $conn->error);

    $limitParams = [$start, $length > 0 ? $length : 1000000];
    bindParams($stmt, $paramTypes . 'ii', array_merge($params, $limitParams));
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(["draw" => $draw, "recordsTotal" => $recordsTotal, "recordsFiltered" => $recordsFiltered, "data" => $data]);
}

function handleExport($conn)
{
    $filter = buildWhereClause($conn, true);
    $whereSql = $filter['sql'];
    $params = $filter['params'];
    $paramTypes = $filter['types'];

    $fromClause = buildBaseQuery();

    $dataQuery = "
        SELECT 
            fb.VerificationTimestamp AS 'Verification Time',
            analyst.FullName AS 'Analyst Name',
            operator.FullName AS 'Operator Name',
            pl.LineName AS 'Line',
            i.Assembly,
            i.LotCode,
            d.MachineDefectCode AS 'Machine Defect',
            i.FinalResult AS 'Operator Result',
            fb.AnalystDecision AS 'Analyst Decision',
            fb.AnalystNotes AS 'Analyst Notes'
        " . $fromClause . $whereSql . " ORDER BY fb.VerificationTimestamp DESC";

    $stmt = $conn->prepare($dataQuery);
    if (!$stmt) throw new Exception("Prepare failed (export): " . $conn->error);
    bindParams($stmt, $paramTypes, $params);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($data);
}
