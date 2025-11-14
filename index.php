<?php $current_page = 'index.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart AOI Dashboard</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/charts.js"></script>
  <script src="js/chartjs-plugin-datalabels.js"></script>
</head>

<body>
  <!-- HEADER -->
  <header class="header-ui">
    <div>
      <h1 class="header-title">Smart AOI Dashboard</h1>
      <p class="header-subtitle">Real-time Production Monitoring</p>
    </div>

    <div class="header-clock-area">

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

      <div class="header-clock" style="margin-left: auto;">
        <p id="clock">00:00:00</p>
        <p id="date">Rabu, 15 Oktober 2025</p>
      </div>

    </div>
  </header>

  <!-- MAIN CONTENT WRAPPER -->
  <main id="panel-area" class="panel-area">
    <!-- Panel akan di-generate oleh JavaScript -->
  </main>

  <audio id="alert-sound" src="assets/sounds/alarm.wav" preload="auto"></audio>

  <script src="js/main.js"></script>
</body>

</html>