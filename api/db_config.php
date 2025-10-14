<?php
// File: api/db_config.php

// Aktifkan mode exception untuk error koneksi MySQLi
// Ini akan membuat error lebih mudah ditangkap oleh blok try-catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- GANTI DENGAN DETAIL KONEKSI ANDA ---
$servername = "192.168.12.204";
$username = "db_admin";
$password = "ohm@2025"; // Isi password Anda jika ada
$dbname = "aoi_dashboard";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Atur charset ke utf8mb4 untuk dukungan karakter yang lebih baik
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Jika koneksi gagal, script akan berhenti di sini dan error akan ditangkap
    // oleh blok try-catch di file get_dashboard_data.php
    throw new Exception("Database Connection Error: " . $e->getMessage());
}

// Variabel $conn sekarang tersedia untuk file yang memanggil require_once 'db_config.php';
