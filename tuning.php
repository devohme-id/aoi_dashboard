<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  // 1. Dapatkan nama file saat ini (misal: "feedback.php")
  $current_page = basename($_SERVER['PHP_SELF']);
  // 2. Redirect ke login.php DAN kirim nama halaman sebagai parameter URL
  header("Location: login.php?redirect=" . urlencode($current_page));
  exit;
}

$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['username'];
$current_full_name = $_SESSION['full_name'];
$current_page = 'tuning.php';
require_once 'api/db_config.php';
$lines = $conn->query("SELECT LineID, LineName FROM ProductionLines ORDER BY LineID");
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Start New Tuning Cycle - Smart AOI Dashboard</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/report.css">
  <link rel="stylesheet" href="css/tuning.css">

  <style>
    /* --- Style untuk Info User di Header --- */

    .header-clock-area {
      /* Pastikan semua item di dalam header-clock-area sejajar */
      align-items: center;
    }

    .header-user-info {
      display: flex;
      align-items: center;
      padding: 0.5rem 1rem;
      margin: 0 0.5rem;
      color: var(--text-color);
      background-color: var(--bg-color);
      /* Warna sedikit beda dari background */
      border-radius: var(--border-radius-md);
      border: 1px solid var(--border-color);
      font-size: 0.9rem;
      font-weight: 600;
    }

    .header-user-info .user-icon {
      margin-right: 0.5rem;
      font-size: 1.1rem;
      opacity: 0.8;
    }

    /* Style untuk Tombol Logout (terpisah) */
    .btn-report.btn-logout {
      background: #e11d48;
      /* Warna merah */
      color: var(--text-dark);
    }

    .btn-report.btn-logout:hover {
      background: #c51a40;
      /* Warna merah lebih gelap saat hover */
    }
  </style>
</head>

<body>
  <header class="header-ui">
    <div>
      <h1 class="header-title">TUNING CYCLE MANAGEMENT</h1>
      <p class="header-subtitle">Start a New Program Tuning Cycle</p>
    </div>
    <div class="header-clock-area">
      <a href="index.php" class="btn-report <?= ($current_page == 'index.php') ? 'active-nav' : '' ?>" style="background: var(--gray-color); color: var(--text-color);">
        <span class="report-icon">🏠</span> DASHBOARD
      </a>
      <a href="report.php" class="btn-report <?= ($current_page == 'report.php') ? 'active-nav' : '' ?>">
        <span class="report-icon">📊</span> KPI REPORT
      </a>
      <a href="feedback.php" class="btn-report <?= ($current_page == 'feedback.php') ? 'active-nav' : '' ?>" style="background: linear-gradient(90deg, var(--yellow-color), #f59e0b); color: var(--text-dark);">
        <span class="report-icon">🔍</span> FEEDBACK
      </a>
      <a href="summary_report.php" class="btn-report <?= ($current_page == 'summary_report.php') ? 'active-nav' : '' ?>" style="background: linear-gradient(90deg, var(--green-color), #22c55e); color: var(--text-dark);">
        <span class="report-icon">📋</span> SUMMARY
      </a>
      <a href="tuning.php" class="btn-report <?= ($current_page == 'tuning.php') ? 'active-nav' : '' ?>" style="background: linear-gradient(90deg, #818cf8, var(--blue-color)); color: var(--text-dark);">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 0.2rem;">
          <path d="M6 1v3H1V1h5zM1 0a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1H1zm14 12v3h-5v-3h5zm-5-1a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-5zM2 9v7h3V9H2zM1 8a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1H1zm12-8v7h3V0h-3zm-4 0v7h3V0h-3z" />
        </svg>
        <span>DEBUGGING</span>
      </a>

      <div class="header-user-info">
        <span class="user-icon">👤</span>
        <span><?php echo htmlspecialchars($current_full_name); ?></span>
      </div>

      <a href="logout.php?from=<?php echo urlencode($current_page); ?>" class="btn-report btn-logout">
          <span class="report-icon">🚪</span> KELUAR
      </a>

      <div class="header-clock">
        <p id="clock">00:00:00</p>
        <p id="date">Rabu, 15 Oktober 2025</p>
      </div>
    </div>
  </header>

  <main class="report-container">
    <div class="card-ui tuning-panel">
      <h2>Start New Cycle</h2>
      <p class="tuning-description">Gunakan form ini setelah Anda menyelesaikan proses tuning/debugging program AOI. Memulai siklus baru akan memisahkan data KPI sebelum dan sesudah tuning untuk analisis yang akurat.</p>
      <form id="tuning_form">
        <input type="hidden" id="user_id" value="<?= htmlspecialchars($current_user_id) ?>">
        <div class="form-row">
          <div class="form-control">
            <label for="line_id">1. Select Production Line</label>
            <select id="line_id" name="line_id" required>
              <option value="">-- Choose Line --</option>
              <?php while ($row = $lines->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($row['LineID']) ?>"><?= htmlspecialchars($row['LineName']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-control">
            <label for="assembly_name">2. Select Assembly Name</label>
            <select id="assembly_name" name="assembly_name" required disabled>
              <option value="">-- Choose Line First --</option>
            </select>
          </div>
        </div>
        <div class="form-control">
          <label for="notes">3. Notes / Change Log (Wajib Diisi)</label>
          <textarea id="notes" name="notes" rows="4" placeholder="Contoh: Menyesuaikan threshold untuk C101, menonaktifkan inspeksi pin R52." required></textarea>
        </div>
        <div class="form-actions">
          <div id="status_message" class="status-message"></div>
          <button type="submit" id="submit_button" class="btn btn-primary"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z" />
            </svg><span>Start New Cycle & Save</span></button>
        </div>
      </form>
    </div>
  </main>
  <script src="js/jquery-3.7.0.min.js"></script>
  <script src="js/tuning.js"></script>
</body>

</html>