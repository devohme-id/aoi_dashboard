<?php $current_page = 'report.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>KPI History Report</title>
  <link rel="stylesheet" href="css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="css/flatpickr.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/report.css">
</head>
<body>
  <header class="header-ui">
    <div class="header-info">
      <h1 class="header-title">KPI HISTORY REPORT</h1>
      <p class="header-subtitle">Historical Production Data Analysis</p>
    </div>
    <div class="header-clock-area">
      <!-- Navigasi -->
      <a href="index.php" class="btn-report"><span>DASHBOARD</span></a>
      <a href="report.php" class="btn-report active-nav"><span>KPI REPORT</span></a>
      <a href="feedback.php" class="btn-report"><span>FEEDBACK</span></a>
      <a href="summary_report.php" class="btn-report"><span>SUMMARY</span></a>
      <a href="tuning.php" class="btn-report"><span>DEBUGGING</span></a>
      
      <div class="header-clock" style="margin-left: auto;">
        <p id="clock">00:00:00</p>
        <p id="date">-- -- --</p>
      </div>
    </div>
  </header>

  <main class="report-container">
    <div class="card-ui filter-panel">
      <div class="filter-group">
        <div class="form-control">
          <label for="date_range">Date Range</label>
          <input type="text" id="date_range" placeholder="Select date range..">
        </div>
        <div class="form-control">
          <label for="line_filter">Line</label>
          <select id="line_filter">
            <option value="">All Lines</option>
            <option value="1">Line 1</option>
            <option value="2">Line 2</option>
            <option value="3">Line 3</option>
            <option value="4">Line 4</option>
            <option value="5">Line 5</option>
            <option value="6">Line 6</option>
          </select>
        </div>
      </div>
      <div class="filter-actions">
        <button id="view_data" class="btn btn-primary">View Data</button>
        <button id="export_excel" class="btn btn-secondary">Export to Excel</button>
      </div>
    </div>
    
    <div class="card-ui table-panel">
      <table id="report_table" class="display" width="100%"></table>
    </div>
  </main>

  <script src="js/jquery-3.7.0.min.js"></script>
  <script src="js/jquery.dataTables.min.js"></script>
  <script src="js/flatpickr.min.js"></script>
  <script src="js/xlsx.full.min.js"></script>
  <script src="js/report.js"></script>
</body>
</html>