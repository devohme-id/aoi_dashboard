<?php
// index.php
session_start();
$current_page = 'index.php';
$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$user_fullname = $_SESSION['full_name'] ?? 'Guest';

// Cek apakah ada error login atau trigger login dari halaman lain
$login_error = isset($_GET['login_error']) ? $_GET['login_error'] : '';
$trigger_login = isset($_GET['trigger_login']) && $_GET['trigger_login'] == 'true';
$redirect_target = isset($_GET['redirect']) ? $_GET['redirect'] : 'feedback.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart AOI Dashboard</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/modal.css"> <!-- Tambahkan CSS Modal -->
  <script src="js/charts.js"></script>
  <script src="js/chartjs-plugin-datalabels.js"></script>
</head>

<body>

  <!-- WRAPPER UTAMA UNTUK EFEK BLUR -->
  <div id="main-content">
      
      <header class="header-ui">
        <div class="header-branding">
          <h1 class="header-title">Smart AOI Dashboard</h1>
          <p class="header-subtitle">Real-time Production Monitoring</p>
        </div>

        <div class="header-clock-area">
          <a href="index.php" class="btn-report <?= $current_page == 'index.php' ? 'active-nav' : '' ?>" style="background: var(--gray-color); color: var(--text-color);">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H10v-4a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v4z"/></svg>
            <span>DASHBOARD</span>
          </a>
          <a href="report.php" class="btn-report">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M4 11a1 1 0 1 1 2 0v1a1 1 0 1 1-2 0zm6-4a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0zM7 9a1 1 0 0 1 2 0v3a1 1 0 1 1-2 0z"/><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z"/></svg>
            <span>KPI REPORT</span>
          </a>
          <a href="feedback.php" class="btn-report" style="background: linear-gradient(90deg, var(--yellow-color), #f59e0b); color: var(--text-dark);">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>
            <span>FEEDBACK</span>
          </a>

          <button id="sound-toggle-btn" class="btn-report sound-btn muted" title="Enable/Disable Sound">
            <svg class="sound-icon muted-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M6.717 3.55A.5.5 0 0 1 7 4v8a.5.5 0 0 1-.812.39L3.825 10.5H1.5A.5.5 0 0 1 1 10V6a.5.5 0 0 1 .5-.5h2.325l2.363-1.89a.5.5 0 0 1 .529-.06zM6 5.04 4.312 6.39A.5.5 0 0 1 4 6.5H2v3h2a.5.5 0 0 1 .312.11L6 10.96V5.04zm7.854.606a.5.5 0 0 1 0 .708L12.207 8l1.647 1.646a.5.5 0 0 1-.708.708L11.5 8.707l-1.646 1.647a.5.5 0 0 1-.708-.708L10.793 8 9.146 6.354a.5.5 0 1 1 .708-.708L11.5 7.293l1.646-1.647a.5.5 0 0 1 .708 0z"/></svg>
            <svg class="sound-icon unmuted-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M11.536 14.01A8.473 8.473 0 0 0 14.026 8a8.473 8.473 0 0 0-2.49-6.01l-.708.707A7.476 7.476 0 0 1 13.025 8c0 2.071-.84 3.946-2.197 5.303l.708.707z"/><path d="M10.121 12.596A6.48 6.48 0 0 0 12.025 8a6.48 6.48 0 0 0-1.904-4.596l-.707.707A5.482 5.482 0 0 1 11.025 8a5.482 5.482 0 0 1-1.61 3.89l.706.706z"/><path d="M8.707 11.182A4.486 4.486 0 0 0 10.025 8a4.486 4.486 0 0 0-1.318-3.182L8 5.525A3.489 3.489 0 0 1 9.025 8 3.49 3.49 0 0 1 8 10.475l.707.707zM6.717 3.55A.5.5 0 0 1 7 4v8a.5.5 0 0 1-.812.39L3.825 10.5H1.5A.5.5 0 0 1 1 10V6a.5.5 0 0 1 .5-.5h2.325l2.363-1.89a.5.5 0 0 1 .529-.06z"/></svg>
          </button>

          <?php if ($is_logged_in): ?>
            <a href="logout.php?from=index.php" class="btn-report" style="background: linear-gradient(90deg, var(--red-color),rgb(243, 140, 15)); color: var(--text-dark);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8.5 10c-.276 0-.5-.448-.5-1s.224-1 .5-1 .5.448.5 1-.224 1-.5 1"/><path d="M10.828.122A.5.5 0 0 1 11 .5V1h.5A1.5 1.5 0 0 1 13 2.5V15h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3V1.5a.5.5 0 0 1 .43-.495l7-1a.5.5 0 0 1 .398.117M11.5 2H11v13h1V2.5a.5.5 0 0 0-.5-.5M4 1.934V15h6V1.077z"/></svg>
                <span>LOGOUT</span>
            </a>
          <?php else: ?>
            <!-- Tombol Login memicu Modal -->
            <button onclick="openLoginModal()" class="btn-report" style="background: linear-gradient(90deg, #6366f1, #4f46e5); color: white;">
                <span>LOGIN</span>
            </button>
          <?php endif; ?>

          <div class="header-clock" style="margin-left: auto;">
            <p id="clock">00:00:00</p>
            <p id="date">-- -- --</p>
          </div>
        </div>
      </header>

      <main id="panel-area" class="panel-area">
          <div style="grid-column: 1/-1; text-align:center; color:white; padding: 2rem;">Loading Dashboard Data...</div>
      </main>
      
  </div> 
  <!-- END MAIN CONTENT -->

  <!-- LOGIN MODAL COMPONENT -->
  <div id="loginModal" class="modal-overlay">
    <div class="modal-container">
        <button class="btn-modal-close" onclick="closeLoginModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
            </svg>
        </button>

        <div class="modal-header">
            <svg class="modal-logo" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                <path d="M5 8c0-1.657 2.343-3 4-3V4a4 4 0 0 0-4 4"/><path d="M12.318 3h2.015C15.253 3 16 3.746 16 4.667v6.666c0 .92-.746 1.667-1.667 1.667h-2.015A5.97 5.97 0 0 1 9 14a5.97 5.97 0 0 1-3.318-1H1.667C.747 13 0 12.254 0 11.333V4.667C0 3.747.746 3 1.667 3H2a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1h.682A5.97 5.97 0 0 1 9 2c1.227 0 2.367.368 3.318 1M2 4.5a.5.5 0 1 0-1 0 .5.5 0 0 0 1 0M14 8A5 5 0 1 0 4 8a5 5 0 0 0 10 0"/>
            </svg>
            <h2 class="modal-title">Smart AOI</h2>
            <p class="modal-subtitle">Login to access Analyst features</p>
        </div>

        <?php if (!empty($login_error)): ?>
            <div class="modal-alert">
                <?= htmlspecialchars($login_error) ?>
            </div>
        <?php endif; ?>

        <form action="api/auth.php" method="POST" class="modal-form">
            <input type="hidden" name="redirect_url" value="<?= htmlspecialchars($redirect_target) ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-with-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/><path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/></svg>
                    <input type="text" id="username" name="username" placeholder="Enter username" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 11.5a3.5 3.5 0 1 1 3.163-5H14L15.5 8 14 9.5l-1-1-1 1-1-1-1 1-1-1-1 1H6.663a3.5 3.5 0 0 1-3.163 2M2.5 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/></svg>
                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                </div>
            </div>

            <button type="submit" class="btn-modal-submit">LOGIN</button>
        </form>
    </div>
  </div>

  <audio id="alert-sound" src="assets/sounds/alarm.wav" preload="auto"></audio>
  <script src="js/main.js"></script>

  <script>
    // Logic Modal Login
    const modal = document.getElementById('loginModal');
    const body = document.body;

    function openLoginModal() {
        modal.classList.add('show');
        body.classList.add('modal-open');
        // Fokus otomatis ke input username
        setTimeout(() => document.getElementById('username').focus(), 100);
    }

    function closeLoginModal() {
        modal.classList.remove('show');
        body.classList.remove('modal-open');
        
        // Hapus parameter error di URL agar tidak muncul terus saat refresh
        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('login_error');
            url.searchParams.delete('trigger_login');
            window.history.replaceState(null, '', url);
        }
    }

    // Event Listener untuk menutup modal jika klik di luar box
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeLoginModal();
        }
    });

    // Cek jika ada trigger dari PHP (misal error login atau redirect dari halaman lain)
    <?php if (!empty($login_error) || $trigger_login): ?>
        document.addEventListener('DOMContentLoaded', openLoginModal);
    <?php endif; ?>
  </script>

</body>
</html>