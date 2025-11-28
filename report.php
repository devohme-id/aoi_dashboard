<?php $current_page = 'report.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KPI History Report</title>
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { slate: { 850: '#151e2e', 900: '#0f172a', 950: '#020617' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
  </script>

  <!-- Library CSS -->
  <link rel="stylesheet" href="css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="css/flatpickr.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/report.css">

  <!-- Custom Styles untuk DataTables di Dark Mode -->
  <style>
    /* Wrapper & Text Colors */
    .dataTables_wrapper { @apply text-slate-400 text-sm; }
    
    /* Length & Filter Inputs */
    .dataTables_length select { @apply bg-slate-950 border border-slate-700 text-slate-300 rounded px-2 py-1 focus:ring-2 focus:ring-blue-500 outline-none; }
    .dataTables_filter input { @apply bg-slate-950 border border-slate-700 text-slate-300 rounded px-3 py-1 ml-2 focus:ring-2 focus:ring-blue-500 outline-none placeholder-slate-600; }
    
    /* Table Header */
    table.dataTable thead th { @apply bg-slate-950 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800 text-xs py-3 !important; }
    
    /* Table Body */
    table.dataTable tbody tr { @apply bg-slate-900 text-slate-300 transition-colors border-b border-slate-800/50 !important; }
    table.dataTable tbody tr.odd { @apply bg-slate-900/50 !important; }
    table.dataTable tbody tr:hover { @apply bg-slate-800 !important; }
    table.dataTable.no-footer { @apply border-b-0 !important; }
    
    /* Cells */
    table.dataTable tbody td { @apply py-3 px-4 border-b border-slate-800/50 !important; }

    /* Pagination */
    .dataTables_wrapper .dataTables_paginate .paginate_button { @apply text-slate-400 rounded hover:bg-slate-800 hover:text-white border-0 transition-colors !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current { @apply bg-blue-600 text-white hover:bg-blue-500 font-bold !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled { @apply text-slate-600 cursor-not-allowed hover:bg-transparent !important; }
    
    /* Processing Indicator */
    .dataTables_processing { @apply bg-slate-800/90 text-white rounded shadow-lg !important; }
  </style>
</head>
<body class="bg-slate-950 text-slate-200 font-sans selection:bg-blue-500 selection:text-white">

  <!-- NAVBAR -->
  <?php include 'templates/navbar.php'; ?>

  <!-- MAIN CONTENT -->
  <main class="max-w-[1920px] mx-auto p-6 space-y-6">
    
    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
                <span class="p-2 bg-blue-600 rounded-lg shadow-lg shadow-blue-600/20">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
                KPI History Report
            </h1>
            <p class="text-slate-400 text-sm mt-1 ml-14">Analyze historical production performance, defects, and false calls.</p>
        </div>
        
        <!-- Quick Stats / Actions (Optional placeholder) -->
        <div class="hidden md:flex gap-3">
            <div class="px-4 py-2 bg-slate-900 border border-slate-800 rounded-lg shadow-sm">
                <span class="text-[10px] text-slate-500 uppercase font-bold block">Status</span>
                <span class="text-sm font-bold text-green-400 flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> System Ready</span>
            </div>
        </div>
    </div>

    <!-- FILTER & TOOLS PANEL -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-1 shadow-xl ring-1 ring-white/5">
        <div class="bg-slate-950/50 rounded-lg p-5">
            <div class="flex flex-col xl:flex-row gap-6 items-end">
                
                <!-- Input Group -->
                <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-5 w-full xl:w-auto">
                    <!-- Date Filter -->
                    <div class="space-y-1.5 group">
                        <label for="date_range" class="text-xs font-bold text-slate-500 uppercase tracking-wider group-focus-within:text-blue-400 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Date Range
                        </label>
                        <input type="text" id="date_range" placeholder="Select date range..." class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all shadow-sm placeholder-slate-600">
                    </div>

                    <!-- Line Filter -->
                    <div class="space-y-1.5 group">
                        <label for="line_filter" class="text-xs font-bold text-slate-500 uppercase tracking-wider group-focus-within:text-blue-400 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Production Line
                        </label>
                        <div class="relative">
                            <select id="line_filter" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all shadow-sm appearance-none cursor-pointer">
                                <option value="">All Lines</option>
                                <option value="1">Line 1</option>
                                <option value="2">Line 2</option>
                                <option value="3">Line 3</option>
                                <option value="4">Line 4</option>
                                <option value="5">Line 5</option>
                                <option value="6">Line 6</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-3 w-full xl:w-auto">
                    <button id="view_data" class="flex-1 xl:flex-none px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-lg shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center gap-2 transform active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        FILTER DATA
                    </button>
                    <button id="export_excel" class="flex-1 xl:flex-none px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-lg shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center gap-2 transform active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        EXPORT EXCEL
                    </button>
                </div>

            </div>
        </div>
    </div>
    
    <!-- DATA TABLE CARD -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl shadow-2xl overflow-hidden ring-1 ring-white/5">
        <!-- Table Toolbar/Header -->
        <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
            <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wide">Detailed Records</h3>
            <div class="text-xs text-slate-500 italic">Showing latest data first</div>
        </div>

        <div class="p-2">
            <table id="report_table" class="display w-full text-sm text-left border-collapse" width="100%">
                <!-- Header content defined in JS but styled by CSS above -->
            </table>
        </div>
    </div>

  </main>

  <!-- SCRIPTS -->
  <script src="js/jquery-3.7.0.min.js"></script>
  <script src="js/jquery.dataTables.min.js"></script>
  <script src="js/flatpickr.min.js"></script>
  <script src="js/xlsx.full.min.js"></script>
  <script src="js/report.js"></script>
</body>
</html>