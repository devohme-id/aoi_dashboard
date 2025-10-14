<?php
// ======================================================
//  get_image.php - Cross Platform SMB/Local Access
// ======================================================

// Mapping path Windows
$path_mapping = [
    1 => '\\\\192.168.0.19\\qx600\\QX600\\Images\\ExportedImages',
    2 => '\\\\192.168.0.21\\qx600\\Images\\ExportedImages\\ExportedImages',
    3 => '\\\\192.168.0.29\\qx600\\Images\\ExportedImages',
    4 => '\\\\192.168.0.25\\qx600\\Images\\ExportedImages',
    5 => '\\\\192.168.0.35\\D_Drive\\QX600\\Images\\ExportedImages',
    6 => '\\\\192.168.0.23\\D_Drive\\QX600\\Images\\ExportedImages'
];

// Mapping path Linux (hasil mount)
$linux_mapping = [
    1 => '/mnt/qx600_1',
    2 => '/mnt/qx600_2',
    3 => '/mnt/qx600_3',
    4 => '/mnt/qx600_4',
    5 => '/mnt/qx600_5',
    6 => '/mnt/qx600_6'
];

// Ambil parameter dari URL
$line = isset($_GET['line']) ? intval($_GET['line']) : 0;
$date = isset($_GET['date']) ? preg_replace('/[^0-9]/', '', $_GET['date']) : ''; // hanya angka
$file = isset($_GET['file']) ? basename($_GET['file']) : '';

if ($line === 0 || empty($file)) {
    http_response_code(400);
    echo "Invalid parameters.";
    exit;
}

// Deteksi OS
$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

// Tentukan base path sesuai OS
$base_path = $is_windows ? ($path_mapping[$line] ?? null) : ($linux_mapping[$line] ?? null);

if (!$base_path) {
    http_response_code(404);
    echo "Invalid line ID.";
    exit;
}

// Buat full path — jika folder per tanggal digunakan (misal /20251013/)
$image_path = rtrim($base_path, '/\\') . DIRECTORY_SEPARATOR;
if (!empty($date) && is_dir($image_path . $date)) {
    $image_path .= $date . DIRECTORY_SEPARATOR;
}
$image_path .= $file;

// Cek apakah file ada
if (!file_exists($image_path)) {
    http_response_code(404);
    echo "Image not found: " . htmlspecialchars($image_path);
    exit;
}

// Tampilkan gambar
$mime = mime_content_type($image_path);
header('Content-Type: ' . $mime);
readfile($image_path);
exit;
