<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Summary - Smart AOI Dashboard</title>

    <!-- Library CSS (Sama seperti halaman report) -->
    <link rel="stylesheet" href="css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="css/flatpickr.min.css">

    <!-- Style Kustom -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/report.css"> <!-- Bisa re-use style report -->
    <link rel="stylesheet" href="css/summary_report.css"> <!-- Style tambahan -->
</head>

<body>
    <header class="header-ui">
        <div class="header-info">
            <h1 class="header-title">VERIFICATION SUMMARY</h1>
            <p class="header-subtitle">Accountability & Performance Report</p>
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
                <p id="date">Senin, 13 Oktober 2025</p>
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
                <div class="form-control">
                    <label for="analyst_filter">Data Analyst</label>
                    <select id="analyst_filter">
                        <option value="">All Analysts</option>
                    </select>
                </div>
                <div class="form-control">
                    <label for="operator_filter">Operator</label>
                    <select id="operator_filter">
                        <option value="">All Operators</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button id="view_data" class="btn btn-primary">View Data</button>
                <button id="export_excel" class="btn btn-secondary">Export to Excel</button>
            </div>
        </div>

        <!-- Panel DataTable -->
        <div class="card-ui table-panel">
            <table id="summary_table" class="display" width="100%"></table>
        </div>
    </main>

    <!-- Library JS -->
    <script src="js/jquery-3.7.0.min.js"></script>
    <script src="js/jquery.dataTables.min.js"></script>
    <script src="js/flatpickr.min.js"></script>
    <script src="js/xlsx.full.min.js"></script>

    <!-- Script Kustom -->
    <script src="js/summary_report.js"></script>
</body>

</html>