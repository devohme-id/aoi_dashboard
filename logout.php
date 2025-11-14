<?php
// File: logout.php

session_start();

// 1. Tentukan tujuan redirect
$redirect_url = "login.php"; // Default jika tidak ada parameter

if (isset($_GET['from'])) {
    // 2. Daftar halaman yang valid (untuk keamanan)
    $allowed_pages = [
        'feedback.php',
        'tuning.php',
        'index.php',
        'report.php',
        'summary_report.php'
    ];

    // 3. Ambil nama file dengan aman
    $from_page = basename($_GET['from']);

    // 4. Jika halaman 'from' valid, buat URL login yang spesifik
    if (in_array($from_page, $allowed_pages)) {
        $redirect_url = "login.php?redirect=" . urlencode($from_page);
    }
}

// 5. Hapus semua variabel session
$_SESSION = array();

// 6. Hancurkan session
session_destroy();

// 7. Redirect ke halaman login (default atau yang spesifik)
header("Location: " . $redirect_url);
exit;
?>