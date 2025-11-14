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
      font-family: var(--font-main);
      display: flex;
      align-items: center;
      justify-content: center;
      /* Latar belakang dengan gradient halus */
      background: var(--bg-color);
      background: linear-gradient(135deg, var(--bg-color) 0%, #303444 100%);
    }

    @font-face {
      font-family: 'MyFontText';
      src: url('../assets/fonts/LGEITextTTF-Regular.ttf') format('truetype');
      font-weight: normal;
      font-style: normal;
    }

    @font-face {
      font-family: 'MyFontHeadline';
      src: url('../assets/fonts/LGEIHeadlineTTF-Regular.ttf') format('truetype');
      font-weight: normal;
      font-style: normal;
    }

    /* --- Wrapper Utama (Kartu Login) --- */
    .login-page-wrapper {
      display: flex;
      width: 100%;
      max-width: 900px;
      /* Lebar total kartu */
      min-height: 550px;
      background: var(--bg-light);
      border-radius: var(--border-radius-lg);
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      /* Penting agar border-radius berfungsi di dalam flex */
      animation: fadeIn 0.5s ease-out;
      /* Animasi muncul */
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
      /* Gradient dari variabel warna Anda */
      background: linear-gradient(45deg, var(--blue-color), #818cf8);
      color: white;
    }

    .login-splash-icon {
      width: 80px;
      height: 80px;
      margin-bottom: 1.5rem;
    }

    .login-splash h2 {
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
      /* Pastikan padding tidak merusak layout */
    }

    .login-form-container h1 {
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
      position: relative;
      /* Penting untuk ikon */
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: var(--text-color);
      font-weight: 600;
    }

    .form-group .input-icon {
      position: absolute;
      left: 12px;
      /* Sesuaikan 'top' agar pas dengan input */
      top: 41px;
      color: var(--text-muted);
      font-size: 1.1rem;
    }

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
      /* Efek 'glow' saat di-klik */
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
      /* Efek 'pop' saat hover */
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
      <svg class="login-splash-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
        <path d="M6 12.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5ZM3 8.062C3 6.76 4.235 5.765 5.53 5.886a26.58 26.58 0 0 0 4.94 0C11.765 5.765 13 6.76 13 8.062v1.157a.933.933 0 0 1-.765.935c-.845.147-2.34.346-4.235.346-1.895 0-3.39-.2-4.235-.346A.933.933 0 0 1 3 9.219V8.062Zm4.542-.827a.25.25 0 0 0-.217.068l-.92.9a24.767 24.767 0 0 1-1.871-.183.25.25 0 0 0-.068.495c.5.073 1.03.16 1.557.233l-.626.62a.25.25 0 0 0 .177.424l.982-.001-.018.916a.25.25 0 0 0 .449.103l.217-.381.217.381a.25.25 0 0 0 .449-.103l-.018-.916.982.001a.25.25 0 0 0 .177-.424l-.626-.62c.527-.073 1.057-.16 1.557-.233a.25.25 0 0 0-.068-.495 24.792 24.792 0 0 1-1.871.183l-.92-.9a.25.25 0 0 0-.217-.068Z" />
        <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1Zm-1.5 6.062V8.062a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v.002Z" />
        <path d="M1 1.5A1.5 1.5 0 0 1 2.5 0h11A1.5 1.5 0 0 1 15 1.5v13a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 1 14.5v-13ZM2.5 1a.5.5 0 0 0-.5.5v13a.5.5 0 0 0 .5.5h11a.5.5 0 0 0 .5-.5v-13a.5.5 0 0 0-.5-.5h-11Z" />
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
          <span class="input-icon">👤</span>
          <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <span class="input-icon">🔒</span>
          <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
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