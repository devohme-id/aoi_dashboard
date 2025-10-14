<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI History Report - Smart AOI Dashboard</title>

    <!-- Library CSS lokal -->
    <link rel="stylesheet" href="css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="css/flatpickr.min.css">

    <!-- Style Kustom -->
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
            <a href="index.php" class="btn-report" style="background: var(--gray-color); color: var(--text-color);">
                <span class="report-icon">🏠</span> DASHBOARD
            </a>
            <a href="report.php" class="btn-report">
                <span class="report-icon">📊</span> KPI REPORT
            </a>
            <a href="feedback.php" class="btn-report" style="background: linear-gradient(90deg, var(--yellow-color), #f59e0b); color: var(--text-dark);">
                <span class="report-icon">🔍</span> FEEDBACK
            </a>
            <a href="summary_report.php" class="btn-report" style="background: linear-gradient(90deg, var(--green-color), #22c55e); color: var(--text-dark);">
                <span class="report-icon">📋</span> SUMMARY
            </a>
            <div class="header-clock">
                <p id="clock">00:00:00</p>
                <p id="date">Jumat, 10 Oktober 2025</p>
            </div>
        </div>
    </header>

    <main class="report-container">
        <!-- Panel Filter -->
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
                <button id="view_data" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill" viewBox="0 0 16 16">
                        <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z" />
                        <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z" />
                    </svg>
                    <span>View Data</span>
                </button>
                <button id="export_excel" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-spreadsheet-fill" viewBox="0 0 16 16">
                        <path d="M6 12v-2h3v2H6z" />
                        <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zM3 9h10v1h-3v2h3v1h-3v2H9v-2H6v2H5v-2H3v-1h2v-2H3V9z" />
                    </svg>
                    <span>Export to Excel</span>
                </button>
            </div>
        </div>

        <!-- Panel DataTable -->
        <div class="card-ui table-panel">
            <table id="report_table" class="display" width="100%"></table>
        </div>
    </main>

    <!-- Library JS lokal -->
    <script src="js/jquery-3.7.0.min.js"></script>
    <script src="js/jquery.dataTables.min.js"></script>
    <script src="js/flatpickr.min.js"></script>
    <script src="js/xlsx.full.min.js"></script>

    <!-- Script Kustom -->
    <script src="js/report.js"></script>
</body>

</html>