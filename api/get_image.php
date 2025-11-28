<?php
// ======================================================
//  api/get_image.php - Secure Image Fetcher
// ======================================================

// Mapping Config
$path_mapping = [
    // Windows Paths
    'win' => [
        1 => '\\\\192.168.0.19\\QX600\\Images\\ExportedImages',
        2 => '\\\\192.168.0.21\\qx600\\Images\\ExportedImages\\ExportedImages',
        3 => '\\\\192.168.0.29\\qx600\\Images\\ExportedImages',
        4 => '\\\\192.168.0.25\\qx600\\Images\\ExportedImages',
        5 => '\\\\192.168.0.35\\D_Drive\\QX600\\Images\\ExportedImages',
        6 => '\\\\192.168.0.23\\D_Drive\\QX600\\Images\\ExportedImages'
    ],
    // Linux Mount Paths
    'linux' => [
        1 => '/mnt/qx600_1',
        2 => '/mnt/qx600_2',
        3 => '/mnt/qx600_3',
        4 => '/mnt/qx600_4',
        5 => '/mnt/qx600_5',
        6 => '/mnt/qx600_6'
    ]
];

// 1. Sanitasi Input
$line = filter_input(INPUT_GET, 'line', FILTER_VALIDATE_INT);
$date = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_NUMBER_INT); // Hanya angka
$file = basename($_GET['file'] ?? ''); // Hanya nama file, hapus path components

if (!$line || empty($file)) {
    http_response_code(400);
    die("Invalid parameters.");
}

// 2. Tentukan Base Path
$os_key = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'win' : 'linux';
$base_path = $path_mapping[$os_key][$line] ?? null;

if (!$base_path) {
    http_response_code(404);
    die("Line configuration not found.");
}

// 3. Construct Path Aman
$path_separator = ($os_key === 'win') ? '\\' : '/';
$full_path = rtrim($base_path, '/\\') . $path_separator;

if (!empty($date)) {
    // Validasi folder tanggal harus angka saja
    if (ctype_digit($date)) {
        $full_path .= $date . $path_separator;
    }
}

$full_path .= $file;

// 4. Serve File
if (file_exists($full_path)) {
    $mime = mime_content_type($full_path);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($full_path));
    readfile($full_path);
    exit;
} else {
    http_response_code(404);
    echo "Image not found.";
}
?>