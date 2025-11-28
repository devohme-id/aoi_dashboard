<?php
// logout.php
session_start();

$redirect_url = "login.php";

// Cek apakah ada request redirect spesifik
if (isset($_GET['from'])) {
    $allowed = ['feedback.php', 'tuning.php', 'index.php', 'report.php', 'summary_report.php'];
    $from = basename($_GET['from']);
    if (in_array($from, $allowed)) {
        $redirect_url = "login.php?redirect=" . urlencode($from);
    }
}

// Hapus semua data sesi
$_SESSION = array();

// Hapus cookie sesi jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: " . $redirect_url);
exit;
?>