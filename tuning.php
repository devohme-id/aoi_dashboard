<?php
session_start();
require_once 'api/db_config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  $current_page = basename($_SERVER['PHP_SELF']);
  header("Location: index.php?trigger_login=true&redirect=" . urlencode($current_page));
  exit;
}

$userId = htmlspecialchars($_SESSION['user_id']);
$current_page = 'tuning.php';

$lines = [];
try {
    $db = new Database();
    $conn = $db->connect();
    $stmt = $conn->query("SELECT LineID, LineName FROM ProductionLines ORDER BY LineID");
    $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <title>Start New Tuning Cycle</title>
  
  <!-- INCLUDE HEADER COMMON -->
  <?php include 'templates/header_common.php'; ?>
  
  <!-- Chart JS -->
  <script src="js/charts.js"></script>
  <script src="js/chartjs-plugin-datalabels.js"></script>
</head>
<body class="bg-slate-950 text-slate-200 font-sans selection:bg-indigo-500 selection:text-white">

  <?php include 'templates/navbar.php'; ?>

  <main class="max-w-[1600px] mx-auto p-6 space-y-6">
    <!-- Header (HTML sama seperti sebelumnya) -->
    <div class="flex items-center gap-4 border-b border-slate-800 pb-6">
        <div class="w-14 h-14 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-indigo-600/20">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Tuning Cycle Manager</h2>
            <p class="text-slate-400 text-sm mt-1">Initialize new program versions and log debugging activities.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- FORM (Left) -->
        <div class="lg:col-span-7 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-8 relative overflow-hidden ring-1 ring-white/5">
            <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                <span class="w-1.5 h-6 bg-indigo-500 rounded-full"></span> Cycle Configuration
            </h3>
            <form id="tuning_form" class="space-y-8">
                <input type="hidden" id="user_id" value="<?= $userId ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="space-y-2 group">
                    <label for="line_id" class="text-xs font-bold text-slate-500 uppercase tracking-wider group-focus-within:text-indigo-400 transition-colors pl-1">1. Select Line</label>
                    <div class="relative">
                        <select id="line_id" name="line_id" required class="w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-4 py-3 appearance-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all cursor-pointer hover:bg-slate-800">
                          <option value="">-- Select Production Line --</option>
                          <?php foreach ($lines as $line): ?>
                            <option value="<?= htmlspecialchars($line['LineID']) ?>"><?= htmlspecialchars($line['LineName']) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                  </div>
                  <div class="space-y-2 group">
                    <label for="assembly_name" class="text-xs font-bold text-slate-500 uppercase tracking-wider group-focus-within:text-indigo-400 transition-colors pl-1">2. Select Assembly</label>
                    <div class="relative">
                        <select id="assembly_name" name="assembly_name" required disabled class="w-full bg-slate-950 border border-slate-700 text-slate-400 rounded-xl px-4 py-3 appearance-none disabled:opacity-50 disabled:cursor-not-allowed focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                          <option value="">-- Choose Line First --</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                  </div>
                </div>
                <div class="space-y-2 group">
                  <label for="notes" class="text-xs font-bold text-slate-500 uppercase tracking-wider group-focus-within:text-indigo-400 transition-colors pl-1">3. Change Log / Notes <span class="text-red-500">*</span></label>
                  <textarea id="notes" name="notes" rows="5" required class="w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all placeholder-slate-600 resize-none" placeholder="Describe parameter changes..."></textarea>
                </div>
                <div class="pt-6 border-t border-slate-800 flex items-center justify-between">
                  <div id="status_message" class="text-sm font-medium transition-all"></div>
                  <button type="submit" id="submit_button" class="flex items-center gap-2 px-8 py-3.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold rounded-xl shadow-xl shadow-indigo-500/20 transform hover:-translate-y-0.5 transition-all active:scale-95 tracking-wide text-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    START NEW CYCLE
                  </button>
                </div>
            </form>
        </div>

        <!-- PREVIEW (Right) -->
        <div class="lg:col-span-5 space-y-4 sticky top-24">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Current Line Status
                </h3>
                <span id="preview_status_badge" class="px-2 py-0.5 bg-slate-800 text-slate-500 text-[10px] rounded uppercase font-bold">No Selection</span>
            </div>
            <div id="preview_panel_container" class="min-h-[300px] flex flex-col justify-center">
                <div id="preview_placeholder" class="bg-slate-900/50 border-2 border-dashed border-slate-800 rounded-xl p-8 flex flex-col items-center justify-center text-center h-full min-h-[300px]">
                    <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mb-4"><svg class="w-8 h-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
                    <h4 class="text-slate-300 font-bold">No Line Selected</h4>
                    <p class="text-slate-500 text-xs mt-2 max-w-[200px]">Select a production line from the left to preview its current live status.</p>
                </div>
                <div id="preview_content" class="hidden"></div>
            </div>
            <div class="bg-blue-900/20 border border-blue-900/30 rounded-lg p-3 flex gap-3 items-start">
                <svg class="w-5 h-5 text-blue-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-xs text-blue-200/80 leading-relaxed"><strong>Tip:</strong> Always verify the currently running assembly matches your selection.</div>
            </div>
        </div>
    </div>
  </main>
  
  <script src="js/jquery-3.7.0.min.js"></script>
  <script src="js/tuning.js"></script>
</body>
</html>