<?php
// =============================================
// api/auth.php
// =============================================
session_start();

// Load koneksi database ERP
require_once 'db_erp_user.php'; 

// Nama tabel user di database ERP
$user_table = "erp_master_users"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Sanitasi Input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 2. Logic Redirect yang Lebih Cerdas
    // Default kembali ke index.php
    $base_redirect = '../index.php';
    
    // Ambil halaman target sukses (misal: feedback.php)
    $success_target = 'feedback.php';
    if (!empty($_POST['redirect_url'])) {
        $clean_url = basename($_POST['redirect_url']);
        // Validasi whitelist halaman
        $allowed = ['feedback.php', 'tuning.php', 'index.php', 'report.php', 'summary_report.php'];
        if (in_array($clean_url, $allowed)) {
            $success_target = $clean_url;
        }
    }

    // 3. Helper untuk Redirect Error (Kembali ke index.php dan buka modal lagi)
    $redirect_on_error = function($msg) use ($base_redirect, $success_target) {
        $error_param = '?login_error=' . urlencode($msg);
        $target_param = '&redirect=' . urlencode($success_target);
        header("Location: " . $base_redirect . $error_param . $target_param . "&trigger_login=true");
        exit;
    };

    // 4. Validasi Kosong
    if (empty($username) || empty($password)) {
        $redirect_on_error("Username dan password wajib diisi.");
    }

    try {
        // 5. Query Login
        $sql = "SELECT user_id, username, full_name, password FROM $user_table WHERE username = :username LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 6. Verifikasi Password
        if ($user) {
            if (md5($password) === $user['password']) {
                session_regenerate_id(true);
                $_SESSION['loggedin']  = true;
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Login Sukses: Ke halaman yang dituju
                header("Location: ../" . $success_target);
                exit;
            } else {
                $redirect_on_error("Password salah.");
            }
        } else {
            $redirect_on_error("Username tidak ditemukan.");
        }

    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        $redirect_on_error("Terjadi kesalahan sistem.");
    }
} else {
    header("Location: ../index.php");
    exit;
}
?>