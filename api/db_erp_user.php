<?php
// =============================================
// api/db_erp_user.php
// =============================================

require_once 'db_config.php';

try {
    $database = new Database();
    // Kita gunakan variabel $conn agar kompatibel dengan file legacy yang mungkin meng-include ini
    // Tapi sekarang $conn adalah object PDO, bukan mysqli
    $conn = $database->connectERP(); 
} catch (Exception $e) {
    // Error handling jika koneksi ERP gagal
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["error" => "Authentication System Unavailable"]);
    exit;
}
?>