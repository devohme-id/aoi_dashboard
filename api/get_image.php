<?php

declare(strict_types=1);

$pathMapping = [
    'win' => [
        1 => '\\\\192.168.0.19\\QX600\\Images\\ExportedImages',
        2 => '\\\\192.168.0.21\\qx600\\Images\\ExportedImages\\ExportedImages',
        3 => '\\\\192.168.0.29\\qx600\\Images\\ExportedImages',
        4 => '\\\\192.168.0.25\\qx600\\Images\\ExportedImages',
        5 => '\\\\192.168.0.35\\D_Drive\\QX600\\Images\\ExportedImages',
        6 => '\\\\192.168.0.23\\D_Drive\\QX600\\Images\\ExportedImages'
    ],
    'linux' => [
        1 => '/mnt/qx600_1',
        2 => '/mnt/qx600_2',
        3 => '/mnt/qx600_3',
        4 => '/mnt/qx600_4',
        5 => '/mnt/qx600_5',
        6 => '/mnt/qx600_6'
    ]
];

$line = filter_input(INPUT_GET, 'line', FILTER_VALIDATE_INT);
$date = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_NUMBER_INT);
$file = basename((string) ($_GET['file'] ?? ''));

if (!$line || empty($file)) {
    http_response_code(400);
    die("Invalid parameters.");
}

$osKey = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'win' : 'linux';
$basePath = $pathMapping[$osKey][$line] ?? null;

if (!$basePath) {
    http_response_code(404);
    die("Line configuration not found.");
}

$separator = $osKey === 'win' ? '\\' : '/';
$fullPath = rtrim($basePath, '/\\') . $separator;

if (!empty($date) && ctype_digit($date)) {
    $fullPath .= $date . $separator;
}

$fullPath .= $file;

if (file_exists($fullPath)) {
    $mime = mime_content_type($fullPath);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
}

http_response_code(404);
echo "Image not found.";