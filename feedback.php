<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  // 1. Dapatkan nama file saat ini (misal: "feedback.php")
  $current_page = basename($_SERVER['PHP_SELF']);
  // 2. Redirect ke login.php DAN kirim nama halaman sebagai parameter URL
  header("Location: login.php?redirect=" . urlencode($current_page));
  exit;
}

// Variabel $username ini akan dipakai di header
$userId = htmlspecialchars($_SESSION['user_id']);
$username = htmlspecialchars($_SESSION['username']);
$fullName = htmlspecialchars($_SESSION['full_name']);

$current_page = 'feedback.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feedback Management - Smart AOI Dashboard</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/feedback.css">
  <link rel="stylesheet" href="css/notiflix.css">
  <link rel="stylesheet" href="css/flatpickr.min.css">
</head>

<body>
  <header class="header-ui">
    <div class="header-info">
      <h1 class="header-title">FEEDBACK MANAGEMENT</h1>
      <p class="header-subtitle">Operator & Machine Result Verification</p>
    </div>
    <div class="header-clock-area">

      <div class="btn-report" style="background: linear-gradient(90deg, var(--blue-color),rgb(15, 87, 243)); color: var(--text-white);">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 0.5rem;">
          <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
          <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z" />
        </svg>
        <span id="userId" data-id="<?php echo $userId ?>"><?php echo $fullName; ?></span>
      </div>

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
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi-grid-custom" viewBox="0 0 16 16">
          <path d="M6 1v3H1V1h5z m8 11v3h-5v-3h5z M2 9v7h3V9H2z m11-9v7h3V0h-3z m-4 0v7h3V0h-3z" />
        </svg>
        <span>DEBUGGING</span>
      </a>

      <button id="sound-toggle-btn" class="btn-report sound-btn muted" title="Enable/Disable Sound">
        <svg class="sound-icon muted-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M6.717 3.55A.5.5 0 0 1 7 4v8a.5.5 0 0 1-.812.39L3.825 10.5H1.5A.5.5 0 0 1 1 10V6a.5.5 0 0 1 .5-.5h2.325l2.363-1.89a.5.5 0 0 1 .529-.06zM6 5.04 4.312 6.39A.5.5 0 0 1 4 6.5H2v3h2a.5.5 0 0 1 .312.11L6 10.96V5.04zm7.854.606a.5.5 0 0 1 0 .708L12.207 8l1.647 1.646a.5.5 0 0 1-.708.708L11.5 8.707l-1.646 1.647a.5.5 0 0 1-.708-.708L10.793 8 9.146 6.354a.5.5 0 1 1 .708-.708L11.5 7.293l1.646-1.647a.5.5 0 0 1 .708 0z" />
        </svg>
        <svg class="sound-icon unmuted-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M11.536 14.01A8.473 8.473 0 0 0 14.026 8a8.473 8.473 0 0 0-2.49-6.01l-.708.707A7.476 7.476 0 0 1 13.025 8c0 2.071-.84 3.946-2.197 5.303l.708.707z" />
          <path d="M10.121 12.596A6.48 6.48 0 0 0 12.025 8a6.48 6.48 0 0 0-1.904-4.596l-.707.707A5.482 5.482 0 0 1 11.025 8a5.482 5.482 0 0 1-1.61 3.89l.706.706z" />
          <path d="M8.707 11.182A4.486 4.486 0 0 0 10.025 8a4.486 4.486 0 0 0-1.318-3.182L8 5.525A3.489 3.489 0 0 1 9.025 8 3.49 3.49 0 0 1 8 10.475l.707.707zM6.717 3.55A.5.5 0 0 1 7 4v8a.5.5 0 0 1-.812.39L3.825 10.5H1.5A.5.5 0 0 1 1 10V6a.5.5 0 0 1 .5-.5h2.325l2.363-1.89a.5.5 0 0 1 .529-.06z" />
        </svg>
      </button>

      <a href="logout.php?from=<?php echo urlencode($current_page); ?>" class="btn-report" style="background: linear-gradient(90deg, var(--red-color),rgb(243, 140, 15)); color: var(--text-dark);">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 0.2rem;">
          <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2.a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z" />
          <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z" />
        </svg>
        <span>KELUAR</span>
      </a>

      <div class="header-clock" style="margin-left: auto;">
        <p id="clock">00:00:00</p>
        <p id="date">Rabu, 15 Oktober 2025</p>
      </div>

    </div>
  </header>

  <main class="feedback-page-layout">
    <div class="card-ui feedback-list-panel">
      <div class="list-header">
        <h2>Verification Queue</h2>
        <p>Items requiring analyst review.</p>
        <div class="list-filters">
          <div class="filter-control"><label for="line-filter">Line</label><select id="line-filter">
              <option value="">All Lines</option>
            </select></div>
          <div class="filter-control"><label for="defect-filter">Defect</label><select id="defect-filter">
              <option value="">All Defects</option>
            </select></div>
          <div class="filter-control"><label for="assembly-filter">Assembly</label><input type="text" id="assembly-filter" placeholder="Search Assembly..."></div>
          <div class="filter-control"><label for="date-range-filter">Date Range</label><input type="text" id="date-range-filter" placeholder="Select date range..."></div>
        </div>
      </div>
      <div class="table-container">
        <table id="feedback-table">
          <thead>
            <tr>
              <th>No.</th>
              <th>Time</th>
              <th>Line</th>
              <th>Defect</th>
              <th>Operator Result</th>
            </tr>
          </thead>
          <tbody id="feedback-table-body"></tbody>
        </table>
        <div id="loading-indicator">
          <div class="spinner"></div><span>Loading Verification Data...</span>
        </div>
      </div>
    </div>
    <div class="card-ui feedback-detail-panel">
      <div id="detail-view-placeholder" class="placeholder-view">
        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="bi bi-card-checklist" viewBox="0 0 16 16">
          <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z" />
          <path d="M7 5.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm-1.496-.854a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0zM7 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm-1.496-.854a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 0 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0z" />
        </svg>
        <h3>Select an item to view details</h3>
        <p>Choose an inspection from the queue on the left to begin verification.</p>
      </div>
      <div id="detail-view-content" class="hidden"></div>
    </div>
  </main>
  <script src="js/notiflix.js"></script>
  <script src="js/flatpickr.min.js"></script>
  <script src="js/feedback.js"></script>
</body>

</html>