<?php $current_page = 'summary_report.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Summary - Smart AOI Dashboard</title>
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
            <div class="header-clock">
                <p id="clock">00:00:00</p>
                <p id="date">Rabu, 15 Oktober 2025</p>
            </div>
        </div>
    </header>

    <main class="report-container">
        <div class="card-ui filter-panel">
            <div class="filter-group">
                <div class="form-control"><label for="date_range">Date Range</label><input type="text" id="date_range" placeholder="Select date range.."></div>
                <div class="form-control"><label for="line_filter">Line</label><select id="line_filter">
                        <option value="">All Lines</option>
                        <option value="1">Line 1</option>
                        <option value="2">Line 2</option>
                        <option value="3">Line 3</option>
                        <option value="4">Line 4</option>
                        <option value="5">Line 5</option>
                        <option value="6">Line 6</option>
                    </select></div>
                <div class="form-control"><label for="analyst_filter">Data Analyst</label><select id="analyst_filter">
                        <option value="">All Analysts</option>
                    </select></div>
                <div class="form-control"><label for="operator_filter">Operator</label><select id="operator_filter">
                        <option value="">All Operators</option>
                    </select></div>
            </div>
            <div class="filter-actions"><button id="view_data" class="btn btn-primary">View Data</button><button id="export_excel" class="btn btn-secondary">Export to Excel</button></div>
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