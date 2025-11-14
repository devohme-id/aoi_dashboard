<?php
session_start();

// Redirect jika sudah login
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
  header("Location: feedback.php"); // atau index.php
  exit;
}

// Ambil pesan error jika ada
$login_error = '';
if (isset($_SESSION['login_error'])) {
  $login_error = $_SESSION['login_error'];
  unset($_SESSION['login_error']);
}
// --- ▼▼▼ PERUBAHAN DI SINI ▼▼▼ ---

// Tetapkan tujuan redirect default
$redirect_to = 'feedback.php';

// Jika ada parameter 'redirect' di URL, gunakan itu
if (isset($_GET['redirect'])) {
  // Ambil nama filenya saja untuk keamanan
  $redirect_to = basename($_GET['redirect']);
}
// --- ▲▲▲ SELESAI ▲▲▲ ---
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Smart AOI Dashboard</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    /* --- Reset & Background --- */
    body,
    html {
      height: 100%;
      margin: 0;
      /* ▼▼▼ REVISI FONT ▼▼▼ */
      font-family: 'MyFontText', sans-serif;
      /* Menggunakan font dari style.css */
      /* ▲▲▲ SELESAI ▲▲▲ */
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* --- Wrapper Utama (Kartu Login) --- */
    .login-page-wrapper {
      display: flex;
      width: 100%;
      max-width: 900px;
      min-height: 550px;
      background: var(--bg-light);
      border-radius: var(--border-radius-lg);
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      animation: fadeIn 0.5s ease-out;
    }

    /* --- Panel Kiri (Splash/Branding) --- */
    .login-splash {
      width: 45%;
      padding: 2.5rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      background: linear-gradient(45deg, var(--blue-color), #818cf8);
      color: white;
    }

    .login-splash-icon {
      width: 80px;
      height: 80px;
      margin-bottom: 1.5rem;
    }

    .login-splash h2 {
      /* ▼▼▼ REVISI FONT ▼▼▼ */
      font-family: 'MyFontHeadline', sans-serif;
      /* Menggunakan font dari style.css */
      /* ▲▲▲ SELESAI ▲▲▲ */
      font-size: 2rem;
      margin: 0 0 0.5rem 0;
    }

    .login-splash p {
      font-size: 1.1rem;
      opacity: 0.9;
    }

    /* --- Panel Kanan (Form) --- */
    .login-form-container {
      width: 55%;
      padding: 2rem 3rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      box-sizing: border-box;
      /* ▼▼▼ TAMBAHAN BORDER ▼▼▼ */
      border-left: 1px solid var(--border-color);
      /* Menambah garis pemisah */
      /* ▲▲▲ SELESAI ▲▲▲ */
    }

    .login-form-container h1 {
      /* ▼▼▼ REVISI FONT ▼▼▼ */
      font-family: 'MyFontHeadline', sans-serif;
      /* Menggunakan font dari style.css */
      /* ▲▲▲ SELESAI ▲▲▲ */
      color: var(--text-color);
      text-align: left;
      margin-bottom: 0.5rem;
      font-size: 2.2rem;
    }

    .login-form-container p {
      color: var(--text-muted);
      text-align: left;
      margin-bottom: 2rem;
      font-size: 1rem;
    }

    /* --- Form Group (dengan Ikon) --- */
    .form-group {
      /* Position relative dihapus dari sini */
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: var(--text-color);
      font-weight: 600;
    }

    /* ▼▼▼ REVISI POSISI IKON (CSS) ▼▼▼ */
    .form-group .input-wrapper {
      position: relative;
      /* Wrapper baru untuk input dan ikon */
    }

    .form-group .input-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      /* 1. Atur ke 50% dari tinggi wrapper */
      transform: translateY(-50%);
      /* 2. Tarik ke atas 50% dari tinggi ikon itu sendiri */
      color: var(--text-muted);
      pointer-events: none;
      /* 3. Agar ikon bisa diklik tembus ke input */
      display: flex;
      /* 4. Untuk memusatkan SVG di dalam span */
      align-items: center;
      justify-content: center;
    }

    /* ▲▲▲ SELESAI ▲▲▲ */

    .form-group input {
      width: 100%;
      padding: 0.85rem 1rem 0.85rem 3rem;
      /* Padding kiri untuk ikon */
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius-md);
      background: var(--bg-color);
      color: var(--text-color);
      box-sizing: border-box;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-group input:focus {
      outline: none;
      border-color: var(--blue-color);
      box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.2);
    }

    /* --- Tombol & Link --- */
    .btn-login {
      width: 100%;
      padding: 0.85rem;
      border: none;
      border-radius: var(--border-radius-md);
      background: linear-gradient(90deg, #818cf8, var(--blue-color));
      color: var(--text-dark);
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-login:hover {
      opacity: 0.9;
      transform: scale(1.02);
    }

    .link-navigasi {
      text-align: center;
      margin-top: 1.5rem;
    }

    .link-navigasi a {
      color: var(--blue-color);
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .link-navigasi a:hover {
      text-decoration: underline;
    }

    /* --- Pesan Error --- */
    .login-error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
      padding: 0.75rem 1.25rem;
      border-radius: var(--border-radius-md);
      margin-bottom: 1.5rem;
      text-align: center;
      font-size: 0.9rem;
    }

    /* --- Animasi --- */
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: scale(0.95);
      }

      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    /* --- Responsive untuk Mobile --- */
    @media (max-width: 900px) {
      .login-page-wrapper {
        flex-direction: column;
        width: 90%;
        min-height: auto;
        height: auto;
        margin: 2rem 0;
      }

      .login-splash {
        width: 100%;
        height: 200px;
        padding: 1.5rem;
        box-sizing: border-box;
      }

      .login-form-container {
        width: 100%;
        padding: 2rem 1.5rem;
        /* Hapus border di mobile agar tidak aneh */
        border-left: none;
      }

      .login-form-container h1 {
        font-size: 1.8rem;
      }
    }
  </style>
</head>

<body>

  <div class="login-page-wrapper">

    <div class="login-splash">
      <svg class="login-splash-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-camera2" viewBox="0 0 16 16">
        <path d="M5 8c0-1.657 2.343-3 4-3V4a4 4 0 0 0-4 4" />
        <path d="M12.318 3h2.015C15.253 3 16 3.746 16 4.667v6.666c0 .92-.746 1.667-1.667 1.667h-2.015A5.97 5.97 0 0 1 9 14a5.97 5.97 0 0 1-3.318-1H1.667C.747 13 0 12.254 0 11.333V4.667C0 3.747.746 3 1.667 3H2a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1h.682A5.97 5.97 0 0 1 9 2c1.227 0 2.367.368 3.318 1M2 4.5a.5.5 0 1 0-1 0 .5.5 0 0 0 1 0M14 8A5 5 0 1 0 4 8a5 5 0 0 0 10 0" />
      </svg>
      <h2>Smart AOI</h2>
      <p>Monitoring & Feedback System</p>
    </div>

    <div class="login-form-container">
      <h1>Selamat Datang</h1>
      <p>Harap masuk untuk melanjutkan</p>

      <?php if (!empty($login_error)): ?>
        <div class="login-error">
          <?php echo htmlspecialchars($login_error); ?>
        </div>
      <?php endif; ?>

      <form action="api/auth.php" method="POST">

        <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($redirect_to); ?>">

        <div class="form-group">
          <label for="username">Username</label>
          <div class="input-wrapper"> <span class="input-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
                <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1" />
              </svg>
            </span>
            <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required>
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper"> <span class="input-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-key-fill" viewBox="0 0 16 16">
                <path d="M3.5 11.5a3.5 3.5 0 1 1 3.163-5H14L15.5 8 14 9.5l-1-1-1 1-1-1-1 1-1-1-1 1H6.663a3.5 3.5 0 0 1-3.163 2M2.5 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
              </svg>
            </span>
            <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
          </div>
        </div>
        <button type="submit" class="btn-login">Masuk</button>
      </form>

      <div class="link-navigasi">
        <a href="index.php">Kembali ke Dashboard</a>
      </div>
    </div>

  </div>

</body>

</html>