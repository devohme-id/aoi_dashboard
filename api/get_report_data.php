<?php
// File: api/get_report_data.php
require_once 'db_config.php';

header('Content-Type: application/json');

// Menggunakan 'Throwable' untuk menangkap semua jenis error
try {
    if (isset($_POST['export']) && $_POST['export'] === 'true') {
        handleExport($conn);
    } else {
        handleDataTable($conn);
    }
} catch (Throwable $t) {
    // Mencatat error ke log server untuk debugging yang pasti
    error_log('API Error: ' . $t->getMessage() . ' in ' . $t->getFile() . ' on line ' . $t->getLine());

    http_response_code(500);
    // Mengirim pesan error yang lebih informatif ke browser
    echo json_encode([
        'error' => 'A server error occurred. Please check the server logs for more details.',
        'debug_message' => $t->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

function buildWhereClause($conn, $isExport = false)
{
    // Logika ini tetap sama, sudah solid
    if ($isExport) {
        $dateFilter = isset($_POST['date_filter']) ? json_decode($_POST['date_filter'], true) : [];
        $lineFilter = isset($_POST['line_filter']) ? $_POST['line_filter'] : '';
        $searchValue = '';
    } else {
        $dateFilter = isset($_POST['date_filter']) ? $_POST['date_filter'] : [];
        $lineFilter = isset($_POST['line_filter']) ? $_POST['line_filter'] : '';
        $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    }

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

// Fungsi helper baru untuk binding parameter yang lebih aman
function bindParams($stmt, $types, $params)
{
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

    // PERBAIKAN: Memisahkan bagian-bagian query untuk urutan sintaks yang benar
    $fromClause = "FROM Inspections i JOIN ProductionLines pl ON i.LineID = pl.LineID";
    $groupByClause = "GROUP BY i.LineID, i.Assembly, i.LotCode";

    // Dapatkan total records (filtered)
    $countQuery = "SELECT COUNT(*) as total FROM (SELECT i.LineID " . $fromClause . " " . $whereSql . " " . $groupByClause . ") as subquery";
    $stmt = $conn->prepare($countQuery);
    if (!$stmt) throw new Exception("Prepare failed for count query: " . $conn->error);
    bindParams($stmt, $paramTypes, $params);
    if (!$stmt->execute()) throw new Exception("Count Query Failed: " . $stmt->error);
    $recordsFiltered = $stmt->get_result()->fetch_assoc()['total'];

    // Dapatkan total semua records (tanpa filter)
    $totalQuery = "SELECT COUNT(*) as total FROM (SELECT i.LineID " . $fromClause . " " . $groupByClause . ") as subquery";
    $recordsTotal = $conn->query($totalQuery)->fetch_assoc()['total'];

    // Query utama untuk data
    $dataQuery = "
        SELECT 
            MAX(i.EndTime) as EndTime,
            pl.LineName,
            i.Assembly,
            i.LotCode,
            COUNT(i.InspectionID) AS Inspected,
            SUM(CASE WHEN i.FinalResult = 'Pass' THEN 1 ELSE 0 END) AS Pass,
            SUM(CASE WHEN i.FinalResult = 'Defective' THEN 1 ELSE 0 END) AS Defect,
            SUM(CASE WHEN i.FinalResult = 'False Fail' THEN 1 ELSE 0 END) AS FalseCall,
            (SUM(CASE WHEN i.FinalResult = 'Pass' THEN 1 ELSE 0 END) / COUNT(i.InspectionID)) * 100 AS PassRate,
            (SUM(CASE WHEN i.FinalResult = 'Defective' THEN 1 ELSE 0 END) / COUNT(i.InspectionID)) * 1000000 AS PPM
        " . $fromClause . " " . $whereSql . " " . $groupByClause . " ORDER BY EndTime DESC LIMIT ?, ?";

    $stmt = $conn->prepare($dataQuery);
    if (!$stmt) throw new Exception("Prepare failed for data query: " . $conn->error);

    $limitParams = [$start, $length > 0 ? $length : 1000000];
    bindParams($stmt, $paramTypes . 'ii', array_merge($params, $limitParams));

    if (!$stmt->execute()) throw new Exception("Data Query Failed: " . $stmt->error);
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['PassRate'] = number_format($row['PassRate'], 2);
        $row['PPM'] = (int)$row['PPM'];
        $data[] = $row;
    }

    echo json_encode(["draw" => $draw, "recordsTotal" => $recordsTotal, "recordsFiltered" => $recordsFiltered, "data" => $data]);
}

function handleExport($conn)
{
    $filter = buildWhereClause($conn, true);
    $whereSql = $filter['sql'];
    $params = $filter['params'];
    $paramTypes = $filter['types'];

    // PERBAIKAN: Memisahkan bagian query untuk urutan sintaks yang benar
    $fromClause = "FROM Inspections i JOIN ProductionLines pl ON i.LineID = pl.LineID";
    $groupByClause = "GROUP BY i.LineID, i.Assembly, i.LotCode";

    $dataQuery = "
        SELECT 
            MAX(i.EndTime) as Timestamp,
            pl.LineName,
            i.Assembly,
            i.LotCode,
            COUNT(i.InspectionID) AS Inspected,
            SUM(CASE WHEN i.FinalResult = 'Pass' THEN 1 ELSE 0 END) AS Pass,
            SUM(CASE WHEN i.FinalResult = 'Defective' THEN 1 ELSE 0 END) AS Defect,
            SUM(CASE WHEN i.FinalResult = 'False Fail' THEN 1 ELSE 0 END) AS 'False Call',
            (SUM(CASE WHEN i.FinalResult = 'Pass' THEN 1 ELSE 0 END) / COUNT(i.InspectionID)) * 100 AS 'Pass Rate (%)',
            (SUM(CASE WHEN i.FinalResult = 'Defective' THEN 1 ELSE 0 END) / COUNT(i.InspectionID)) * 1000000 AS PPM
        " . $fromClause . " " . $whereSql . " " . $groupByClause . " ORDER BY Timestamp DESC";

    $stmt = $conn->prepare($dataQuery);
    if (!$stmt) throw new Exception("Prepare failed for export query: " . $conn->error);
    bindParams($stmt, $paramTypes, $params);

    if (!$stmt->execute()) throw new Exception("Export Query Failed: " . $stmt->error);
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['Pass Rate (%)'] = number_format($row['Pass Rate (%)'], 2);
        $row['PPM'] = (int)$row['PPM'];
        $data[] = $row;
    }
    echo json_encode($data);
}
