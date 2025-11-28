<?php
session_start();
// Cek Login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  // Redirect ke index.php dan perintahkan untuk membuka modal login
  $current_page = basename($_SERVER['PHP_SELF']);
  header("Location: index.php?trigger_login=true&redirect=" . urlencode($current_page));
  exit;
}

$userId = htmlspecialchars($_SESSION['user_id']);
$fullName = htmlspecialchars($_SESSION['full_name']);
$current_page = 'feedback.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feedback Management</title>
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
      <!-- User Info -->
      <a href="#" class="btn-report" style="background: linear-gradient(90deg, var(--blue-color),rgb(15, 87, 243)); color: white; cursor: default;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/><path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/></svg>
        <span id="userId" data-id="<?= $userId ?>"><?= $fullName ?></span>
      </a>

      <!-- Navigation -->
      <a href="index.php" class="btn-report"><span>DASHBOARD</span></a>
      <a href="report.php" class="btn-report"><span>KPI REPORT</span></a>
      <a href="feedback.php" class="btn-report active-nav" style="background: linear-gradient(90deg, var(--yellow-color), #f59e0b); color: var(--text-dark);"><span>FEEDBACK</span></a>
      <a href="summary_report.php" class="btn-report"><span>SUMMARY</span></a>
      <a href="tuning.php" class="btn-report" style="background: linear-gradient(90deg, #818cf8, var(--blue-color)); color: var(--text-dark);"><span>DEBUGGING</span></a>
      
      <a href="logout.php?from=feedback.php" class="btn-report" style="background: linear-gradient(90deg, var(--red-color),rgb(243, 140, 15)); color: var(--text-dark);">
        <span>KELUAR</span>
      </a>

      <div class="header-clock" style="margin-left: auto;">
        <p id="clock">00:00:00</p>
        <p id="date">-- -- --</p>
      </div>
    </div>
  </header>

  <main class="feedback-page-layout">
    <!-- List Queue Panel -->
    <div class="card-ui feedback-list-panel">
      <div class="list-header">
        <h2>Verification Queue</h2>
        <p>Items requiring analyst review.</p>
        <div class="list-filters">
          <div class="filter-control"><label>Line</label><select id="line-filter"><option value="">All Lines</option></select></div>
          <div class="filter-control"><label>Defect</label><select id="defect-filter"><option value="">All Defects</option></select></div>
          <div class="filter-control"><label>Assembly</label><input type="text" id="assembly-filter" placeholder="Search Assembly..."></div>
          <div class="filter-control"><label>Date</label><input type="text" id="date-range-filter" placeholder="Filter by Date..."></div>
        </div>
      </div>
      <div class="table-container">
        <table id="feedback-table">
          <thead><tr><th>No.</th><th>Time</th><th>Line</th><th>Defect</th><th>Result</th></tr></thead>
          <tbody id="feedback-table-body"></tbody>
        </table>
        <div id="loading-indicator"><div class="spinner"></div><span>Loading...</span></div>
      </div>
    </div>

    <!-- Detail & Form Panel -->
    <div class="card-ui feedback-detail-panel">
      <div id="detail-view-placeholder" class="placeholder-view">
        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" viewBox="0 0 16 16"><path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z"/><path d="M7 5.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm-1.496-.854a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0zM7 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm-1.496-.854a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 0 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0z"/></svg>
        <h3>Select an item to view details</h3>
      </div>
      <div id="detail-view-content" class="hidden"></div>
    </div>
  </main>
  
  <script src="js/notiflix.js"></script>
  <script src="js/flatpickr.min.js"></script>
  <script src="js/feedback.js"></script>
</body>
</html>