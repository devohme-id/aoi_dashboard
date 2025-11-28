<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
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
  <title>Feedback Management</title>
  
  <!-- INCLUDE HEADER COMMON -->
  <?php include 'templates/header_common.php'; ?>

  <!-- PAGE SPECIFIC STYLES -->
  <style type="text/tailwindcss">
    @layer utilities {
      /* Detail View Classes */
      .detail-content-wrapper { @apply flex flex-col h-full animate-[fadeIn_0.3s_ease-out]; }
      .detail-image-container { @apply relative w-full h-64 bg-slate-950 rounded-xl overflow-hidden border border-slate-800 flex items-center justify-center group mb-6 shadow-inner; }
      .detail-image-container img { @apply w-full h-full object-contain transition-transform duration-500 group-hover:scale-105; }
      .critical-banner { @apply absolute top-3 right-3 bg-red-500/90 text-white text-[10px] font-bold px-3 py-1 rounded-full shadow-lg z-10 backdrop-blur-sm animate-pulse border border-red-400/50; }
      .detail-grid { @apply grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 mb-6; }
      .btn-submit-feedback { @apply mt-6 w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-blue-900/20 transform transition-all active:scale-95 text-sm tracking-wide flex items-center justify-center gap-2; }
      .btn-submit-feedback:disabled { @apply opacity-50 cursor-not-allowed grayscale; }
      .verified-overlay { @apply flex flex-col items-center justify-center h-full text-center p-8 animate-[fadeIn_0.5s_ease-out]; }
      /* Table Selection */
      tr.selected { @apply bg-blue-600/10 border-l-2 border-blue-500 transition-all !important; }
      tr.selected td { @apply text-blue-100; }
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  </style>

  <link rel="stylesheet" href="css/notiflix.css">
  <link rel="stylesheet" href="css/flatpickr.min.css">
</head>
<body class="bg-slate-950 text-slate-200 font-sans selection:bg-blue-500 selection:text-white">

  <?php include 'templates/navbar.php'; ?>

  <main class="max-w-[1920px] mx-auto p-6 space-y-6 min-h-[calc(100vh-80px)]">
    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
                <span class="p-2 bg-yellow-600 rounded-lg shadow-lg shadow-yellow-600/20 text-white">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </span>
                Feedback Management
            </h1>
            <p class="text-slate-400 text-sm mt-1 ml-14">Verify operator decisions and machine results.</p>
        </div>
        <div class="hidden md:flex items-center gap-2 px-4 py-2 bg-slate-900 border border-slate-800 rounded-lg shadow-sm">
            <span class="relative flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
            <span class="text-sm font-bold text-slate-300">Live Feed Active</span>
        </div>
    </div>

    <!-- MAIN GRID LAYOUT -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-[calc(100vh-200px)] min-h-[600px]">
        <!-- LEFT PANEL -->
        <div class="col-span-1 lg:col-span-7 flex flex-col bg-slate-900 border border-slate-800 rounded-xl shadow-xl overflow-hidden ring-1 ring-white/5">
            <div class="p-5 border-b border-slate-800 bg-slate-950/30 backdrop-blur-md sticky top-0 z-20">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Filters (HTML Sama seperti sebelumnya) -->
                    <div class="space-y-1.5 group">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1 group-focus-within:text-blue-400 transition-colors">Line</label>
                        <div class="relative"><select id="line-filter" class="w-full bg-slate-950 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/50 transition-all appearance-none cursor-pointer hover:bg-slate-900"><option value="">All Lines</option></select><div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-500"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></div></div>
                    </div>
                    <div class="space-y-1.5 group">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1 group-focus-within:text-blue-400 transition-colors">Defect Type</label>
                        <div class="relative"><select id="defect-filter" class="w-full bg-slate-950 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/50 transition-all appearance-none cursor-pointer hover:bg-slate-900"><option value="">All Defects</option></select><div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-500"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></div></div>
                    </div>
                    <div class="space-y-1.5 group">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1 group-focus-within:text-blue-400 transition-colors">Assembly</label>
                        <input type="text" id="assembly-filter" placeholder="Search..." class="w-full bg-slate-950 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/50 transition-all placeholder-slate-600">
                    </div>
                    <div class="space-y-1.5 group">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1 group-focus-within:text-blue-400 transition-colors">Date Range</label>
                        <input type="text" id="date-range-filter" placeholder="Filter Date..." class="w-full bg-slate-950 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/50 transition-all placeholder-slate-600">
                    </div>
                </div>
            </div>
            <div class="flex-grow relative overflow-y-auto custom-scrollbar bg-slate-900">
                <table id="feedback-table" class="w-full text-left text-sm border-collapse">
                    <thead class="bg-slate-950 text-slate-400 uppercase text-[10px] tracking-wider sticky top-0 z-10 shadow-sm border-b border-slate-800">
                        <tr>
                            <th class="px-5 py-3.5 font-bold w-12 text-center border-r border-slate-800">#</th>
                            <th class="px-5 py-3.5 font-bold w-32">Time</th>
                            <th class="px-5 py-3.5 font-bold w-24 text-center">Line</th>
                            <th class="px-5 py-3.5 font-bold">Defect Type</th>
                            <th class="px-5 py-3.5 font-bold w-40 text-center">Machine Result</th>
                        </tr>
                    </thead>
                    <tbody id="feedback-table-body" class="divide-y divide-slate-800/50 text-slate-300"></tbody>
                </table>
                <div id="loading-indicator" class="absolute inset-0 bg-slate-900/90 backdrop-blur-sm flex flex-col items-center justify-center z-20 transition-opacity duration-300">
                    <div class="relative mb-4"><div class="w-12 h-12 border-4 border-slate-800 border-t-blue-500 rounded-full animate-spin"></div></div>
                    <span class="text-slate-400 text-xs font-bold uppercase tracking-widest animate-pulse">Syncing Data...</span>
                </div>
            </div>
            <div class="px-4 py-2 bg-slate-950 border-t border-slate-800 flex justify-between items-center text-[10px] text-slate-500">
                <span>Showing latest items first</span><span class="font-mono">Auto-refresh active</span>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="col-span-1 lg:col-span-5 bg-slate-900 border border-slate-800 rounded-xl shadow-2xl flex flex-col relative overflow-hidden ring-1 ring-white/5 h-full">
            <div id="detail-view-placeholder" class="flex flex-col items-center justify-center h-full text-slate-500 p-8 text-center bg-slate-900 transition-opacity duration-300">
                <div class="relative mb-6 group">
                    <div class="absolute inset-0 bg-blue-500/20 rounded-full blur-xl group-hover:bg-blue-500/30 transition-all duration-500"></div>
                    <div class="relative p-6 bg-slate-800 border border-slate-700 rounded-3xl shadow-xl">
                        <svg class="w-16 h-16 text-slate-400 group-hover:text-blue-400 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-white tracking-tight mb-2">Ready to Verify</h3>
                <p class="text-sm max-w-xs mx-auto leading-relaxed text-slate-400">Select an item from the queue on the left to inspect defect details and submit your decision.</p>
            </div>
            <div id="detail-view-content" class="hidden h-full flex flex-col overflow-y-auto custom-scrollbar bg-slate-900"></div>
        </div>
    </div>
  </main>
  
  <div id="userId" data-id="<?= $userId ?>" class="hidden"></div>
  <script src="js/notiflix.js"></script>
  <script src="js/flatpickr.min.js"></script>
  <script src="js/feedback.js"></script>
</body>
</html>