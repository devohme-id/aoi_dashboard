<?php 
declare(strict_types=1);
$currentPage = 'summary_report.php'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Verification Summary</title>
    <?php include 'templates/header_common.php'; ?>
    <link rel="stylesheet" href="css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="css/flatpickr.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/summary_report.css">
    <style>
        .dataTables_wrapper { @apply text-slate-400 text-sm p-4; }
        .dataTables_length select { @apply bg-slate-900 border border-slate-700 text-slate-300 rounded px-2 py-1 focus:ring-2 focus:ring-blue-500 outline-none; }
        .dataTables_filter input { @apply bg-slate-900 border border-slate-700 text-slate-300 rounded px-3 py-1 ml-2 focus:ring-2 focus:ring-blue-500 outline-none placeholder-slate-600; }
        table.dataTable thead th { @apply bg-slate-950 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800 text-xs py-3 !important; }
        table.dataTable tbody tr { @apply bg-slate-900 text-slate-300 transition-colors border-b border-slate-800/50 !important; }
        table.dataTable tbody tr.odd { @apply bg-slate-900/50 !important; }
        table.dataTable tbody tr:hover { @apply bg-slate-800 !important; }
        table.dataTable.no-footer { @apply border-b-0 !important; }
        table.dataTable tbody td { @apply py-3 px-4 border-b border-slate-800/50 !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { @apply text-slate-400 rounded hover:bg-slate-800 hover:text-white border-0 transition-colors !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { @apply bg-blue-600 text-white hover:bg-blue-500 font-bold !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled { @apply text-slate-600 cursor-not-allowed hover:bg-transparent !important; }
        .dataTables_processing { @apply bg-slate-800/90 text-white rounded shadow-lg border border-slate-700 !important; }
        .dataTables_scrollBody { height: auto !important; max-height: none !important; overflow-y: visible !important; }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 font-sans selection:bg-emerald-500 selection:text-white">
    <?php include 'templates/navbar.php'; ?>
    <main class="max-w-[1920px] mx-auto p-6 space-y-6 min-h-screen pb-20">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800/60 pb-6">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
                    <span class="p-2 bg-emerald-600 rounded-lg shadow-lg shadow-emerald-600/20 text-white">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                    Verification Summary
                </h1>
                <p class="text-slate-400 text-sm mt-1 ml-14">Track analyst performance and operator accuracy.</p>
            </div>
            <div class="hidden md:flex gap-3">
                <button onclick="window.print()" class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors" title="Print Report">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                </button>
            </div>
        </div>
        <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-1 shadow-xl backdrop-blur-sm">
            <div class="bg-slate-950/80 rounded-xl p-5"> 
                <div class="flex flex-col xl:flex-row gap-6 items-end">
                    <div class="flex-grow grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 w-full xl:w-auto">
                        <div class="space-y-1.5 group">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider group-focus-within:text-emerald-400 transition-colors flex items-center gap-1.5">Date Range</label>
                            <input type="text" id="date_range" placeholder="Filter date..." class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none transition-all shadow-inner h-10">
                        </div>
                        <div class="space-y-1.5 group">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider group-focus-within:text-emerald-400 transition-colors flex items-center gap-1.5">Line</label>
                            <select id="line_filter" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none transition-all shadow-inner h-10"><option value="">All Lines</option><option value="1">Line 1</option><option value="2">Line 2</option><option value="3">Line 3</option><option value="4">Line 4</option><option value="5">Line 5</option><option value="6">Line 6</option></select>
                        </div>
                        <div class="space-y-1.5 group">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider group-focus-within:text-emerald-400 transition-colors flex items-center gap-1.5">Analyst</label>
                            <select id="analyst_filter" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none transition-all shadow-inner h-10"><option value="">All Analysts</option></select>
                        </div>
                        <div class="space-y-1.5 group">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider group-focus-within:text-emerald-400 transition-colors flex items-center gap-1.5">Operator</label>
                            <select id="operator_filter" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none transition-all shadow-inner h-10"><option value="">All Operators</option></select>
                        </div>
                    </div>
                    <div class="flex gap-3 w-full xl:w-auto mt-4 xl:mt-0">
                        <button id="view_data" class="flex-1 xl:flex-none px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-lg shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center gap-2 transform active:scale-95">APPLY</button>
                        <button id="export_excel" class="flex-1 xl:flex-none px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-lg shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center gap-2 transform active:scale-95">EXCEL</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl ring-1 ring-white/5 flex flex-col">
            <div class="px-6 py-4 border-b border-slate-800 bg-slate-950/30 flex justify-between items-center backdrop-blur-md">
                <h3 class="text-xs font-bold text-slate-300 uppercase tracking-widest flex items-center gap-2">Activity Logs</h3>
            </div>
            <div class="relative w-full">
                <table id="summary_table" class="display w-full text-left border-collapse" width="100%"></table>
            </div>
        </div>
    </main>
    <script src="js/jquery-3.7.0.min.js"></script>
    <script src="js/jquery.dataTables.min.js"></script>
    <script src="js/flatpickr.min.js"></script>
    <script src="js/xlsx.full.min.js"></script>
    <script src="js/summary_report.js"></script>
</body>
</html>