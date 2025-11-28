<?php
session_start();
require_once 'api/db_config.php';

// Auth Check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  $current_page = basename($_SERVER['PHP_SELF']);
  header("Location: index.php?trigger_login=true&redirect=" . urlencode($current_page));
  exit;
}

$userId = htmlspecialchars($_SESSION['user_id']);
$fullName = htmlspecialchars($_SESSION['full_name']);
$current_page = 'tuning.php';

$lines = [];
try {
    $db = new Database();
    $conn = $db->connect();
    $stmt = $conn->query("SELECT LineID, LineName FROM ProductionLines ORDER BY LineID");
    $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed to fetch lines: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Start New Tuning Cycle</title>
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
      <a href="#" class="btn-report" style="background: linear-gradient(90deg, var(--blue-color),rgb(15, 87, 243)); color: white; cursor:default;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/><path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/></svg>
        <span id="userId" data-id="<?= $userId ?>"><?= $fullName ?></span>
      </a>
      <a href="index.php" class="btn-report"><span>DASHBOARD</span></a>
      <a href="feedback.php" class="btn-report"><span>FEEDBACK</span></a>
      <a href="tuning.php" class="btn-report active-nav" style="background: linear-gradient(90deg, #818cf8, var(--blue-color)); color: var(--text-dark);"><span>DEBUGGING</span></a>
      <a href="logout.php?from=tuning.php" class="btn-report" style="background: linear-gradient(90deg, var(--red-color),rgb(243, 140, 15)); color: var(--text-dark);"><span>KELUAR</span></a>
      
      <div class="header-clock" style="margin-left: auto;">
        <p id="clock">00:00:00</p>
        <p id="date">-- -- --</p>
      </div>
    </div>
  </header>

  <main class="report-container">
    <div class="card-ui tuning-panel">
      <h2>Start New Cycle</h2>
      <p class="tuning-description">Memulai siklus baru akan memisahkan data KPI sebelum dan sesudah tuning.</p>
      
      <form id="tuning_form">
        <input type="hidden" id="user_id" value="<?= $userId ?>">
        <div class="form-row">
          <div class="form-control">
            <label for="line_id">1. Select Production Line</label>
            <select id="line_id" name="line_id" required>
              <option value="">-- Choose Line --</option>
              <?php foreach ($lines as $line): ?>
                <option value="<?= htmlspecialchars($line['LineID']) ?>"><?= htmlspecialchars($line['LineName']) ?></option>
              <?php endforeach; ?>
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
          <label for="notes">3. Notes / Change Log (Wajib)</label>
          <textarea id="notes" name="notes" rows="4" required></textarea>
        </div>
        <div class="form-actions">
          <div id="status_message" class="status-message"></div>
          <button type="submit" id="submit_button" class="btn btn-primary">Start New Cycle & Save</button>
        </div>
      </form>
    </div>
  </main>
  <script src="js/jquery-3.7.0.min.js"></script>
  <script src="js/tuning.js"></script>
</body>
</html>