<?php
// =============================================
// api/db_config.php (Refactored - PDO Version)
// =============================================

class Database {
    // Konfigurasi Database Dashboard (Lokal)
    private $dash_host = "127.0.0.1";
    private $dash_db   = "aoi_dashboard";
    private $dash_user = "root";
    private $dash_pass = "root";

    // Konfigurasi Database ERP (User Auth)
    private $erp_host  = "127.0.0.1"; // Ganti jika IP berbeda (misal: 192.168.12.203)
    private $erp_db    = "stockflow_system";
    private $erp_user  = "root";      // Sesuaikan dengan user ERP (misal: ohmuser)
    private $erp_pass  = "root";

    public $conn;

    // Method untuk koneksi ke AOI Dashboard
    public function connect() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->dash_host . ";dbname=" . $this->dash_db . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true // Persistent connection
            ];
            
            $this->conn = new PDO($dsn, $this->dash_user, $this->dash_pass, $options);
            
        } catch(PDOException $e) {
            // Log error ke file server, jangan tampilkan ke user
            error_log("[" . date("Y-m-d H:i:s") . "] Dashboard DB Connection Error: " . $e->getMessage() . "\n", 3, __DIR__ . "/../logs/db_error.log");
            
            // Return JSON jika ini dipanggil oleh API
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(["error" => "Connection failed."]);
            exit;
        }
        return $this->conn;
    }

    // Method untuk koneksi ke ERP (Khusus Auth/User)
    public function connectERP() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->erp_host . ";dbname=" . $this->erp_db . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->conn = new PDO($dsn, $this->erp_user, $this->erp_pass, $options);

        } catch(PDOException $e) {
            error_log("[" . date("Y-m-d H:i:s") . "] ERP DB Connection Error: " . $e->getMessage() . "\n", 3, __DIR__ . "/../logs/db_error.log");
            throw new Exception("ERP Database Connection Error");
        }
        return $this->conn;
    }
}
?>