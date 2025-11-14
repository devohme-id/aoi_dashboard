<?php
// file api/auth.php
session_start();
$user_table = "erp_master_users"; // Pastikan ini nama tabel Anda

try {
  require_once 'db_erp_user.php';

  if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- ▼▼▼ PERUBAHAN 1: Tangkap URL redirect saat error ▼▼▼ ---
    $redirect_param = ''; // Parameter URL untuk dikirim balik jika error
    if (isset($_POST['redirect_url'])) {
      // Ambil nama filenya saja untuk keamanan
      $safe_redirect_on_error = basename($_POST['redirect_url']);
      $redirect_param = '?redirect=' . urlencode($safe_redirect_on_error);
    }
    // --- ▲▲▲ SELESAI ▲▲▲ ---

    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
      $_SESSION['login_error'] = "Username dan password tidak boleh kosong.";
      // Kirim balik parameter redirect
      header("Location: ../login.php" . $redirect_param);
      exit;
    }

    // Pastikan Anda juga mengambil full_name di sini
    $stmt = $conn->prepare("SELECT user_id, username, full_name, password FROM $user_table WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
      $user = $result->fetch_assoc();
      $hashed_input_password = md5($password);

      if ($hashed_input_password === $user['password']) {
        session_regenerate_id(true);
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name']; // Pastikan ini di-set

        // --- ▼▼▼ PERUBAHAN 2: INI BAGIAN PENTINGNYA (Logika Redirect Dinamis) ▼▼▼ ---

        // Daftar halaman yang diizinkan untuk redirect
        $allowed_pages = [
          'feedback.php',
          'tuning.php',
          'index.php',
          'report.php',
          'summary_report.php'
        ];

        // Halaman default jika tidak ada yg spesifik
        $redirect_target = '../feedback.php';

        if (isset($_POST['redirect_url'])) {
          // Ambil nama filenya saja (keamanan)
          $requested_page = basename($_POST['redirect_url']);

          // Cek apakah halaman yg diminta ada di daftar yg diizinkan
          if (in_array($requested_page, $allowed_pages)) {
            $redirect_target = '../' . $requested_page;
          }
        }

        // Redirect ke halaman yang dituju
        header("Location: " . $redirect_target);
        exit;
        // --- ▲▲▲ SELESAI ▲▲▲ ---

      } else {
        $_SESSION['login_error'] = "Password salah. Silakan coba lagi.";
        header("Location: ../login.php" . $redirect_param); // Kirim balik
        exit;
      }
    } else {
      $_SESSION['login_error'] = "Username tidak ditemukan.";
      header("Location: ../login.php" . $redirect_param); // Kirim balik
      exit;
    }

    $stmt->close();
    $conn->close();
  } else {
    header("Location: ../login.php");
    exit;
  }
} catch (Exception $e) {
  error_log($e->getMessage());
  $_SESSION['login_error'] = "Terjadi error pada sistem. Silakan hubungi administrator.";
  header("Location: ../login.php");
  exit;
}
