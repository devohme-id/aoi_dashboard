<?php $current_page = 'feedback.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - Smart AOI Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/feedback.css">
    <link rel="stylesheet" href="css/notiflix.css">
    <!-- Tambahkan CSS untuk Flatpickr -->
    <link rel="stylesheet" href="css/flatpickr.min.css">
</head>

<body>
    <header class="header-ui">
        <div class="header-info">
            <h1 class="header-title">FEEDBACK MANAGEMENT</h1>
            <p class="header-subtitle">Operator & Machine Result Verification</p>
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
                <span>TUNING</span>
            </a>
            <div class="header-clock">
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
                <!-- *** PERUBAHAN: Penambahan filter Assembly dan Date Range *** -->
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
    <!-- Tambahkan JS untuk Flatpickr -->
    <script src="js/flatpickr.min.js"></script>
    <script src="js/feedback.js"></script>
</body>

</html>