<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  // 1. Dapatkan nama file saat ini (misal: "feedback.php")
  $current_page = basename($_SERVER['PHP_SELF']);
  // 2. Redirect ke login.php DAN kirim nama halaman sebagai parameter URL
  header("Location: login.php?redirect=" . urlencode($current_page));
  exit;
}

$userId = htmlspecialchars($_SESSION['user_id']);
$username = htmlspecialchars($_SESSION['username']);
$fullName = htmlspecialchars($_SESSION['full_name']);

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
</head>

<body>
  <header class="header-ui">
    <div>
      <h1 class="header-title">TUNING CYCLE MANAGEMENT</h1>
      <p class="header-subtitle">Start a New Program Tuning Cycle</p>
    </div>
    <div class="header-clock-area">
    <a href="#" class="btn-report" style="background: linear-gradient(90deg, var(--blue-color),rgb(15, 87, 243)); color: var(--text-white);">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
          <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
          <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1" />
        </svg>
        <span id="userId" data-id="<?php echo $userId ?>"><?php echo $fullName; ?></span>
      </a>
      <a href="index.php" class="btn-report <?= ($current_page == 'index.php') ? 'active-nav' : '' ?>" style="background: var(--gray-color); color: var(--text-color);">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-house-door" viewBox="0 0 16 16">
          <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H10v-4a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v4z" />
        </svg>
        <span>DASHBOARD</span>
      </a>
      <a href="report.php" class="btn-report <?= ($current_page == 'report.php') ? 'active-nav' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-clipboard-data" viewBox="0 0 16 16">
          <path d="M4 11a1 1 0 1 1 2 0v1a1 1 0 1 1-2 0zm6-4a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0zM7 9a1 1 0 0 1 2 0v3a1 1 0 1 1-2 0z" />
          <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z" />
          <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0z" />
        </svg>
        <span>KPI REPORT</span>
      </a>
      <a href="feedback.php" class="btn-report <?= ($current_page == 'feedback.php') ? 'active-nav' : '' ?>" style="background: linear-gradient(90deg, var(--yellow-color), #f59e0b); color: var(--text-dark);">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
          <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
        </svg>
        <span>FEEDBACK</span>
      </a>
      <a href="summary_report.php" class="btn-report <?= ($current_page == 'summary_report.php') ? 'active-nav' : '' ?>" style="background: linear-gradient(90deg, var(--green-color), #22c55e); color: var(--text-dark);">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-file-earmark-text" viewBox="0 0 16 16">
          <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5" />
          <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5zm0 1v2A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z" />
        </svg>
        <span>SUMMARY</span>
      </a>
      <a href="tuning.php" class="btn-report <?= ($current_page == 'tuning.php') ? 'active-nav' : '' ?>" style="background: linear-gradient(90deg, #818cf8, var(--blue-color)); color: var(--text-dark);">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi-grid-custom" viewBox="0 0 16 16">
          <path d="M6 1v3H1V1h5z m8 11v3h-5v-3h5z M2 9v7h3V9H2z m11-9v7h3V0h-3z m-4 0v7h3V0h-3z" />
        </svg>
        <span>DEBUGGING</span>
      </a>

      <a href="logout.php?from=<?php echo urlencode($current_page); ?>" class="btn-report" style="background: linear-gradient(90deg, var(--red-color),rgb(243, 140, 15)); color: var(--text-dark);">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-door-open" viewBox="0 0 16 16">
          <path d="M8.5 10c-.276 0-.5-.448-.5-1s.224-1 .5-1 .5.448.5 1-.224 1-.5 1" />
          <path d="M10.828.122A.5.5 0 0 1 11 .5V1h.5A1.5 1.5 0 0 1 13 2.5V15h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3V1.5a.5.5 0 0 1 .43-.495l7-1a.5.5 0 0 1 .398.117M11.5 2H11v13h1V2.5a.5.5 0 0 0-.5-.5M4 1.934V15h6V1.077z" />
        </svg>
        <span>KELUAR</span>
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
        <input type="hidden" id="user_id" value="<?= htmlspecialchars($userId) ?>">
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