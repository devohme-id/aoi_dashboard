<?php
// File: api/get_summary_data.php
require_once 'db_config.php';
header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->connect();
    
    $isExport = isset($_POST['export']) && $_POST['export'] === 'true';
    
    // Conditions
    $conditions = [];
    $params = [];

    // Filters
    $dateFilter = $_POST['date_filter'] ?? [];
    if (!is_array($dateFilter)) $dateFilter = json_decode($dateFilter, true);

    if (is_array($dateFilter) && count($dateFilter) == 2) {
        $conditions[] = "DATE(fb.VerificationTimestamp) BETWEEN ? AND ?";
        $params[] = $dateFilter[0];
        $params[] = $dateFilter[1];
    }
    if (!empty($_POST['line_filter'])) {
        $conditions[] = "i.LineID = ?";
        $params[] = $_POST['line_filter'];
    }
    if (!empty($_POST['analyst_filter'])) {
        $conditions[] = "fb.AnalystUserID = ?";
        $params[] = $_POST['analyst_filter'];
    }
    if (!empty($_POST['operator_filter'])) {
        $conditions[] = "i.OperatorUserID = ?";
        $params[] = $_POST['operator_filter'];
    }

    $searchValue = $_POST['search']['value'] ?? '';
    if (!empty($searchValue) && !$isExport) {
        $conditions[] = "(i.Assembly LIKE ? OR d.MachineDefectCode LIKE ? OR fb.AnalystDecision LIKE ?)";
        $term = "%$searchValue%";
        $params[] = $term; $params[] = $term; $params[] = $term;
    }

    $whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

    // Base Joins
    $fromSql = "
        FROM FeedbackLog fb
        JOIN Defects d ON fb.DefectID = d.DefectID
        JOIN Inspections i ON d.InspectionID = i.InspectionID
        LEFT JOIN Users analyst ON fb.AnalystUserID = analyst.UserID
        LEFT JOIN Users operator ON i.OperatorUserID = operator.UserID
        LEFT JOIN ProductionLines pl ON i.LineID = pl.LineID
    ";

    // Count
    $stmtTotal = $conn->query("SELECT COUNT(FeedbackID) FROM FeedbackLog");
    $recordsTotal = $stmtTotal->fetchColumn();

    $stmtFiltered = $conn->prepare("SELECT COUNT(fb.FeedbackID) $fromSql $whereSql");
    $stmtFiltered->execute($params);
    $recordsFiltered = $stmtFiltered->fetchColumn();

    // Select Data
    $selectSql = "
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
    ";
    
    // DataTable mapping keys
    if (!$isExport) {
        $selectSql = "
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
        ";
    }

    $orderSql = " ORDER BY fb.VerificationTimestamp DESC ";
    $limitSql = "";

    if (!$isExport) {
        $start = (int)($_POST['start'] ?? 0);
        $length = (int)($_POST['length'] ?? 10);
        $limitSql = " LIMIT $length OFFSET $start";
    }

    $stmt = $conn->prepare($selectSql . $fromSql . $whereSql . $orderSql . $limitSql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($isExport) {
        echo json_encode($data);
    } else {
        echo json_encode([
            "draw" => intval($_POST['draw'] ?? 0),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['error' => 'Server Error']);
}
?>