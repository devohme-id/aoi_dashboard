<?php $current_page = 'summary_report.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verification Summary</title>
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { slate: { 850: '#151e2e', 900: '#0f172a', 950: '#020617' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
  </script>

  <!-- Restore Assets CSS Asli (Agar fitur DataTables jalan normal) -->
  <link rel="stylesheet" href="css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="css/flatpickr.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/report.css">
  <link rel="stylesheet" href="css/summary_report.css">
  
  <!-- CSS Adapter untuk membuat DataTables terlihat bagus di Dark Mode Tailwind -->
  <style>
    /* Paksa teks DataTables agar berwarna terang di background gelap */
    .dataTables_wrapper { color: #94a3b8; font-size: 0.85rem; padding: 1rem; }
    
    /* Input Search & Select Length */
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        background-color: #0f172a; /* Slate 900 */
        border: 1px solid #334155; /* Slate 700 */
        color: #e2e8f0;
        border-radius: 0.375rem;
        padding: 0.3rem 0.5rem;
    }

    /* Tabel Header & Body */
    table.dataTable { border-collapse: collapse !important; width: 100% !important; border-bottom: 1px solid #334155 !important; }
    table.dataTable thead th { background-color: #020617; color: #cbd5e1; border-bottom: 1px solid #334155 !important; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; padding: 12px !important; }
    table.dataTable tbody tr { background-color: transparent !important; }
    table.dataTable tbody td { color: #cbd5e1; border-bottom: 1px solid #1e293b !important; padding: 10px !important; font-size: 0.85rem; }
    
    /* Hover Row */
    table.dataTable tbody tr:hover { background-color: #1e293b !important; }

    /* Pagination Buttons */
    .dataTables_wrapper .dataTables_paginate .paginate_button { color: #94a3b8 !important; border-radius: 0.375rem !important; border: 1px solid transparent !important; margin: 0 2px; }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #1e293b !important; color: white !important; border: 1px solid #334155 !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current, 
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { background: #059669 !important; color: white !important; border: 1px solid #059669 !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled { color: #475569 !important; }

    /* Info text */
    .dataTables_info { color: #64748b !important; }
  </style>
</head>
<body class="bg-slate-950 text-slate-200 font-sans selection:bg-emerald-500 selection:text-white">

  <?php include 'templates/navbar.php'; ?>

  <main class="max-w-[1920px] mx-auto p-4 space-y-6 min-h-screen pb-24"> 
    
    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800/60 pb-4">
        <div class="flex items-center gap-3">
            <span class="p-2.5 bg-gradient-to-br from-emerald-600 to-teal-600 rounded-xl shadow-lg shadow-emerald-600/20 text-white">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
            <div>
                <h1 class="text-xl font-bold text-white tracking-tight leading-none">Verification Summary</h1>
                <p class="text-slate-400 text-xs mt-1 font-medium">Performance metrics & analyst logs.</p>
            </div>
        </div>
        
        <div>
            <button onclick="window.print()" class="group flex items-center gap-2 px-4 py-2 bg-slate-900 border border-slate-700 hover:border-slate-600 rounded-lg text-slate-400 hover:text-white transition-all shadow-sm active:scale-95 text-xs font-bold uppercase tracking-wider">
                <svg class="w-4 h-4 group-hover:text-emerald-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- FILTER PANEL -->
    <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-1 shadow-sm backdrop-blur-sm">
        <div class="bg-slate-950/80 rounded-xl p-5"> 
            
            <div class="flex flex-col xl:flex-row gap-5 items-end">
                
                <!-- Filter Inputs Grid -->
                <div class="flex-grow grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 w-full xl:w-auto">
                    
                    <!-- Date -->
                    <div class="space-y-1.5 group">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider group-focus-within:text-emerald-400 transition-colors flex items-center gap-1.5">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Date Range
                        </label>
                        <div class="relative">
                            <input type="text" id="date_range" placeholder="Filter date..." class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500/50 outline-none transition-all shadow-inner placeholder-slate-600 h-10">
                        </div>
                    </div>

                    <!-- Line -->
                    <div class="space-y-1.5 group">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider group-focus-within:text-emerald-400 transition-colors flex items-center gap-1.5">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Line
                        </label>
                        <div class="relative">
                            <select id="line_filter" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500/50 outline-none transition-all shadow-inner appearance-none cursor-pointer h-10 hover:bg-slate-800">
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

                    <!-- Analyst -->
                    <div class="space-y-1.5 group">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider group-focus-within:text-emerald-400 transition-colors flex items-center gap-1.5">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Analyst
                        </label>
                        <div class="relative">
                            <select id="analyst_filter" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500/50 outline-none transition-all shadow-inner appearance-none cursor-pointer h-10 hover:bg-slate-800">
                                <option value="">All Analysts</option>
                            </select>
                        </div>
                    </div>

                    <!-- Operator -->
                    <div class="space-y-1.5 group">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider group-focus-within:text-emerald-400 transition-colors flex items-center gap-1.5">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Operator
                        </label>
                        <div class="relative">
                            <select id="operator_filter" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500/50 outline-none transition-all shadow-inner appearance-none cursor-pointer h-10 hover:bg-slate-800">
                                <option value="">All Operators</option>
                            </select>
                        </div>
                    </div>

                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-3 w-full xl:w-auto mt-2 xl:mt-0">
                    <button id="view_data" class="flex-1 xl:flex-none px-6 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-lg shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center gap-2 transform active:scale-95 hover:-translate-y-0.5 h-10 group">
                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        APPLY
                    </button>
                    <button id="export_excel" class="flex-1 xl:flex-none px-6 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center gap-2 transform active:scale-95 hover:-translate-y-0.5 h-10 group">
                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        EXCEL
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- TABLE CARD (NO OVERFLOW HIDDEN to prevent footer clipping) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl ring-1 ring-white/5 flex flex-col">
        
        <div class="px-6 py-4 border-b border-slate-800 bg-slate-950/30 flex justify-between items-center backdrop-blur-md">
            <h3 class="text-xs font-bold text-slate-300 uppercase tracking-widest flex items-center gap-2">
                <span class="w-2 h-2 bg-emerald-500 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.5)] animate-pulse"></span>
                Activity Logs
            </h3>
        </div>

        <!-- Table Container -->
        <div class="relative w-full">
            <table id="summary_table" class="display w-full text-left" width="100%">
                <!-- Headers injected by DataTables -->
            </table>
        </div>
    </div>

  </main>

  <!-- SCRIPTS -->
  <script src="js/jquery-3.7.0.min.js"></script>
  <script src="js/jquery.dataTables.min.js"></script>
  <script src="js/flatpickr.min.js"></script>
  <script src="js/xlsx.full.min.js"></script>
  <script src="js/summary_report.js"></script>
</body>
</html>