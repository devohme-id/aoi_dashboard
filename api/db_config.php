<?php

declare(strict_types=1);

class Database
{
    // Konfigurasi Database Dashboard (Lokal)
    private string $dashHost = 'localhost';
    private string $dashDb   = 'aoi_dashboard';
    private string $dashUser = 'root';
    private string $dashPass = 'root';

    // Konfigurasi Database ERP (User Auth)
    private string $erpHost  = 'localhost';
    private string $erpDb    = 'stockflow_system';
    private string $erpUser  = 'root';
    private string $erpPass  = 'root';

    public ?PDO $conn = null;

    /**
     * Koneksi ke Database Utama Dashboard
     */
    public function connect(): ?PDO
    {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->dashHost};dbname={$this->dashDb};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true
            ];
            
            $this->conn = new PDO($dsn, $this->dashUser, $this->dashPass, $options);

        } catch (PDOException $e) {
            $this->handleConnectionError($e, "Dashboard DB");
        }

        return $this->conn;
    }

    /**
     * Koneksi ke Database ERP (Untuk Auth)
     */
    public function connectERP(): ?PDO
    {
        $erpConn = null;
        try {
            $dsn = "mysql:host={$this->erpHost};dbname={$this->erpDb};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $erpConn = new PDO($dsn, $this->erpUser, $this->erpPass, $options);

        } catch (PDOException $e) {
            $this->handleConnectionError($e, "ERP DB");
        }

        return $erpConn;
    }

    /**
     * Menangani Error Koneksi (Cross-Platform)
     */
    private function handleConnectionError(PDOException $e, string $context): void
    {
        // 1. Tentukan Path Log yang aman untuk Windows/Linux
        $logDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs';
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'db_error.log';

        // 2. Buat folder logs jika belum ada (Penting untuk Windows)
        if (!is_dir($logDir)) {
            // 0777 agar bisa ditulis, true untuk recursive
            if (!mkdir($logDir, 0777, true) && !is_dir($logDir)) {
                // Fallback jika gagal buat folder: log ke sistem PHP default
                error_log("CRITICAL: Failed to create log directory at $logDir");
            }
        }

        // 3. Format Pesan Log
        // Menggunakan date default sistem atau set manual jika perlu
        $timestamp = date("Y-m-d H:i:s");
        $errorMessage = "[{$timestamp}] [{$context}] Error: " . $e->getMessage() . PHP_EOL;

        // 4. Tulis ke file (Append mode)
        // file_put_contents lebih aman concurrency-nya dibanding fopen manual
        file_put_contents($logFile, $errorMessage, FILE_APPEND | LOCK_EX);

        // 5. Response ke Client (JSON)
        // Jangan tampilkan detail error SQL ke user (Security)
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }

        echo json_encode([
            "error" => true,
            "message" => "Database connection failed. Please check server logs."
        ]);
        
        exit; // Hentikan eksekusi script
    }
}