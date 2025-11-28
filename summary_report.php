<?php $current_page = 'summary_report.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Verification Summary Report</title>
  <link rel="stylesheet" href="css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="css/flatpickr.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/report.css">
  <link rel="stylesheet" href="css/summary_report.css">
</head>
<body>
  <header class="header-ui">
    <div class="header-info">
      <h1 class="header-title">VERIFICATION SUMMARY</h1>
      <p class="header-subtitle">Accountability & Performance Report</p>
    </div>
    <div class="header-clock-area">
      <!-- Navigasi -->
      <a href="index.php" class="btn-report"><span>DASHBOARD</span></a>
      <a href="report.php" class="btn-report"><span>KPI REPORT</span></a>
      <a href="feedback.php" class="btn-report"><span>FEEDBACK</span></a>
      <a href="summary_report.php" class="btn-report active-nav" style="background: linear-gradient(90deg, var(--green-color), #22c55e); color: var(--text-dark);"><span>SUMMARY</span></a>
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
          <label>Date Range</label>
          <input type="text" id="date_range" placeholder="Select date range..">
        </div>
        <div class="form-control">
          <label>Line</label>
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
        <div class="form-control">
          <label>Analyst</label>
          <select id="analyst_filter"><option value="">All Analysts</option></select>
        </div>
        <div class="form-control">
          <label>Operator</label>
          <select id="operator_filter"><option value="">All Operators</option></select>
        </div>
      </div>
      <div class="filter-actions">
        <button id="view_data" class="btn btn-primary">View Data</button>
        <button id="export_excel" class="btn btn-secondary">Export to Excel</button>
      </div>
    </div>

    <div class="card-ui table-panel">
      <table id="summary_table" class="display" width="100%"></table>
    </div>
  </main>

  <script src="js/jquery-3.7.0.min.js"></script>
  <script src="js/jquery.dataTables.min.js"></script>
  <script src="js/flatpickr.min.js"></script>
  <script src="js/xlsx.full.min.js"></script>
  <script src="js/summary_report.js"></script>
</body>
</html>