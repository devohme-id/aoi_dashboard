<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = ($_SESSION['loggedin'] ?? false) === true;
$userFullName = htmlspecialchars((string)($_SESSION['full_name'] ?? 'Guest'));
$currentPage = $current_page ?? '';

$navItems = [
    [
        'url' => 'index.php',
        'label' => 'DASHBOARD',
        'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>'
    ],
    [
        'url' => 'report.php',
        'label' => 'KPI REPORT',
        'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>'
    ],
    [
        'url' => 'feedback.php',
        'label' => 'FEEDBACK',
        'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>'
    ],
    [
        'url' => 'summary_report.php',
        'label' => 'SUMMARY',
        'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
    ],
    [
        'url' => 'tuning.php',
        'label' => 'DEBUGGING',
        'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'
    ]
];
?>

<nav class="w-full bg-slate-900/80 border-b border-slate-800 sticky top-0 z-50 backdrop-blur-md">
    <div class="px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/20 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                </svg>
            </div>
            <div class="hidden sm:block leading-tight">
                <h1 class="text-white font-bold text-lg tracking-wide font-sans">SMART AOI</h1>
                <p class="text-slate-500 text-[10px] font-bold tracking-widest">MONITORING SYSTEM</p>
            </div>
        </div>

        <div class="flex items-center gap-1 md:gap-2 overflow-x-auto no-scrollbar px-4">
            <?php foreach ($navItems as $item): 
                $isActive = $currentPage === $item['url'];
                $baseClass = "flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative overflow-hidden select-none";
                $activeClass = "bg-blue-600/10 text-blue-400 ring-1 ring-blue-500/50 shadow-[0_0_15px_rgba(59,130,246,0.15)]";
                $inactiveClass = "text-slate-400 hover:text-slate-200 hover:bg-slate-800";
            ?>
                <a href="<?= $item['url'] ?>" class="<?= $baseClass ?> <?= $isActive ? $activeClass : $inactiveClass ?>">
                    <?= $item['icon'] ?>
                    <span class="hidden xl:block"><?= $item['label'] ?></span>
                    <?php if ($isActive): ?>
                        <!-- Active Indicator -->
                        <span class="hidden xl:block text-[8px] text-blue-500 animate-pulse ml-1">●</span>
                        <div class="absolute bottom-0 left-0 h-[2px] w-full bg-blue-500 shadow-[0_0_10px_#3b82f6]"></div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="flex items-center gap-4">
            <button id="sound-toggle-btn" class="p-2 rounded-full text-slate-400 hover:text-white hover:bg-slate-800 transition-all muted" title="Sound Notification">
                <svg class="w-5 h-5 sound-icon muted-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
                <svg class="w-5 h-5 sound-icon unmuted-icon hidden text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
            </button>

            <?php if ($isLoggedIn): ?>
                <div class="flex items-center gap-3 border-l border-slate-700 pl-4">
                    <div class="hidden lg:block text-right leading-tight">
                        <div class="text-xs font-bold text-white"><?= $userFullName ?></div>
                        <div class="text-[10px] text-emerald-400 font-bold tracking-wide">ONLINE</div>
                    </div>
                    <a href="logout.php?from=<?= urlencode($currentPage) ?>" class="group p-2 rounded-lg bg-slate-800 hover:bg-red-500/20 text-slate-400 hover:text-red-400 border border-slate-700 hover:border-red-500/50 transition-all" title="Logout">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </a>
                </div>
            <?php else: ?>
                <button onclick="window.DashboardAuth?.openLoginModal()" class="px-5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-bold uppercase tracking-wider rounded-lg shadow-lg shadow-blue-500/20 transition-all transform hover:-translate-y-0.5">
                    LOGIN
                </button>
            <?php endif; ?>

            <div class="hidden xl:block text-right border-l border-slate-700 pl-4">
                <div id="clock" class="text-lg font-bold text-slate-200 font-mono leading-none tracking-tight">00:00</div>
                <div id="date" class="text-[10px] text-slate-500 uppercase font-bold tracking-widest mt-1">-- -- --</div>
            </div>
        </div>
    </div>
</nav>