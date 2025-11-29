<?php

declare(strict_types=1);

class Database
{
    private string $dashHost = 'localhost';
    private string $dashDb = 'aoi_dashboard';
    private string $dashUser = 'root';
    private string $dashPass = 'root';

    private string $erpHost = 'localhost';
    private string $erpDb = 'stockflow_system';
    private string $erpUser = 'root';
    private string $erpPass = 'root';

    public ?PDO $conn = null;

    public function connect(): ?PDO
    {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->dashHost};dbname={$this->dashDb};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ];
            
            $this->conn = new PDO($dsn, $this->dashUser, $this->dashPass, $options);
        } catch (PDOException $e) {
            error_log("[Dashboard DB Error] " . $e->getMessage(), 3, __DIR__ . "/../logs/db_error.log");
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(["error" => "Connection failed."]);
            exit;
        }
        return $this->conn;
    }

    public function connectERP(): PDO
    {
        try {
            $dsn = "mysql:host={$this->erpHost};dbname={$this->erpDb};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            return new PDO($dsn, $this->erpUser, $this->erpPass, $options);
        } catch (PDOException $e) {
            error_log("[ERP DB Error] " . $e->getMessage(), 3, __DIR__ . "/../logs/db_error.log");
            throw new Exception("ERP Database Connection Error");
        }
    }
}