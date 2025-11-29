<?php

declare(strict_types=1);

session_start();

$currentPage = 'index.php';
$loginError = filter_input(INPUT_GET, 'login_error', FILTER_SANITIZE_SPECIAL_CHARS);
$triggerLogin = filter_input(INPUT_GET, 'trigger_login', FILTER_VALIDATE_BOOLEAN);
$redirectTarget = filter_input(INPUT_GET, 'redirect', FILTER_SANITIZE_URL) ?? 'feedback.php';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Smart AOI Dashboard</title>
    <?php include 'templates/header_common.php'; ?>
    <script src="js/charts.js"></script>
    <script src="js/chartjs-plugin-datalabels.js"></script>
</head>
<body class="bg-slate-950 text-slate-200 font-sans selection:bg-blue-500 selection:text-white overflow-hidden">
    <div id="main-content" class="h-screen flex flex-col transition-all duration-300">
        <?php include 'templates/navbar.php'; ?>
        <main id="panel-area" class="flex-grow p-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 lg:grid-rows-2 gap-3 min-h-0 overflow-y-auto lg:overflow-hidden">
            <div class="col-span-full h-full flex flex-col items-center justify-center text-slate-500 space-y-4">
                <div class="relative w-14 h-14">
                    <div class="absolute top-0 left-0 w-full h-full border-4 border-slate-800 rounded-full"></div>
                    <div class="absolute top-0 left-0 w-full h-full border-4 border-t-blue-500 rounded-full animate-spin"></div>
                </div>
                <p class="text-xs font-bold tracking-widest animate-pulse">CONNECTING LIVE DATA...</p>
            </div>
        </main>
    </div>

    <div id="loginModal" class="fixed inset-0 z-[100] hidden flex justify-center items-center backdrop-blur-md bg-slate-900/80 opacity-0 transition-opacity duration-300">
        <div id="loginModalBox" class="bg-slate-900 border border-slate-700 shadow-2xl rounded-2xl w-full max-w-sm mx-4 transform transition-all duration-300 scale-95 p-0 overflow-hidden relative">
            <button class="absolute top-4 right-4 text-slate-500 hover:text-white transition-colors z-10" id="closeModalBtn">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="p-6 text-center bg-gradient-to-b from-slate-800 to-slate-900 border-b border-slate-700">
                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-3 text-white shadow-lg shadow-blue-500/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-white">Analyst Login</h2>
                <p class="text-slate-400 text-xs mt-1">Please sign in to continue</p>
            </div>
            <?php if ($loginError): ?>
                <div class="mx-6 mt-4 p-2.5 bg-red-500/10 border border-red-500/20 text-red-400 rounded-lg text-xs text-center font-bold">
                    <?= htmlspecialchars($loginError) ?>
                </div>
            <?php endif; ?>
            <form action="api/auth.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="redirect_url" value="<?= htmlspecialchars($redirectTarget) ?>">
                <div class="space-y-1.5">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Username</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 group-focus-within:text-blue-500 transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></span>
                        <input type="text" id="username" name="username" class="w-full pl-9 pr-3 py-2.5 bg-slate-950 border border-slate-700 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-white placeholder-slate-600 text-sm" placeholder="Enter ID" required>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Password</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 group-focus-within:text-blue-500 transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></span>
                        <input type="password" id="password" name="password" class="w-full pl-9 pr-3 py-2.5 bg-slate-950 border border-slate-700 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-white placeholder-slate-600 text-sm" placeholder="••••••••" required>
                    </div>
                </div>
                <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-lg shadow-lg shadow-blue-500/20 transition-all text-xs tracking-wide transform active:scale-[0.98]">
                    SECURE LOGIN
                </button>
            </form>
        </div>
    </div>
    <audio id="alert-sound" src="assets/sounds/alarm.wav" preload="auto"></audio>
    <script src="js/main.js"></script>
    <?php if ($loginError || $triggerLogin): ?>
    <script>document.addEventListener('DOMContentLoaded', () => window.DashboardAuth.openLoginModal());</script>
    <?php endif; ?>
</body>
</html>