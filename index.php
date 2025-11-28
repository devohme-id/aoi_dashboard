<?php
session_start();
$current_page = 'index.php';

$login_error = isset($_GET['login_error']) ? $_GET['login_error'] : '';
$trigger_login = isset($_GET['trigger_login']) && $_GET['trigger_login'] == 'true';
$redirect_target = isset($_GET['redirect']) ? $_GET['redirect'] : 'feedback.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart AOI Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { slate: { 850: '#151e2e', 900: '#0f172a', 950: '#020617' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
  </script>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/modal.css">
  <script src="js/charts.js"></script>
  <script src="js/chartjs-plugin-datalabels.js"></script>
</head>
<body class="bg-slate-950 text-slate-200 font-sans selection:bg-blue-500 selection:text-white overflow-hidden">

  <!-- Main Wrapper -->
  <div id="main-content" class="h-screen flex flex-col transition-all duration-300">
      <?php include 'templates/navbar.php'; ?>

      <!-- Content Area -->
      <!-- 
           REVISI PENTING:
           1. lg:grid-rows-2: Memaksa grid menjadi 2 baris di layar besar.
           2. min-h-0: Mencegah grid meluap dari container flex parent.
           3. overflow-y-auto: Mengizinkan scroll HANYA jika konten benar-benar tidak muat (safety).
      -->
      <main id="panel-area" class="flex-grow p-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 lg:grid-rows-2 gap-3 min-h-0 overflow-y-auto lg:overflow-hidden">
          
          <!-- Loading State -->
          <div class="col-span-full h-full flex flex-col items-center justify-center text-slate-500 space-y-4">
              <div class="relative w-14 h-14">
                  <div class="absolute top-0 left-0 w-full h-full border-4 border-slate-800 rounded-full"></div>
                  <div class="absolute top-0 left-0 w-full h-full border-4 border-t-blue-500 rounded-full animate-spin"></div>
              </div>
              <p class="text-xs font-bold tracking-widest animate-pulse">CONNECTING LIVE DATA...</p>
          </div>

      </main>
  </div> 

  <!-- Modal Login -->
  <div id="loginModal" class="modal-overlay backdrop-blur-md bg-slate-900/60 fixed inset-0 z-[100] hidden flex justify-center items-center opacity-0 transition-opacity duration-300">
    <div class="modal-container bg-slate-900 border border-slate-700 shadow-2xl rounded-2xl w-full max-w-sm mx-4 transform transition-all scale-95 p-0 overflow-hidden">
        <button class="absolute top-4 right-4 text-slate-500 hover:text-white transition-colors z-10" onclick="closeLoginModal()">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div class="p-6 text-center bg-gradient-to-b from-slate-800 to-slate-900 border-b border-slate-700">
            <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-3 text-white shadow-lg shadow-blue-500/30">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <h2 class="text-lg font-bold text-white">Analyst Login</h2>
        </div>

        <?php if (!empty($login_error)): ?>
            <div class="mx-6 mt-4 p-2 bg-red-500/10 border border-red-500/20 text-red-400 rounded text-[10px] text-center font-bold">
                <?= htmlspecialchars($login_error) ?>
            </div>
        <?php endif; ?>

        <form action="api/auth.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="redirect_url" value="<?= htmlspecialchars($redirect_target) ?>">
            
            <div class="space-y-1">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></span>
                    <input type="text" id="username" name="username" class="w-full pl-9 pr-3 py-2 bg-slate-950 border border-slate-700 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-white placeholder-slate-600 text-xs" placeholder="Username" required>
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></span>
                    <input type="password" id="password" name="password" class="w-full pl-9 pr-3 py-2 bg-slate-950 border border-slate-700 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-white placeholder-slate-600 text-xs" placeholder="Password" required>
                </div>
            </div>

            <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-md shadow-lg shadow-blue-500/20 transition-all text-xs tracking-wide">
                LOGIN
            </button>
        </form>
    </div>
  </div>

  <audio id="alert-sound" src="assets/sounds/alarm.wav" preload="auto"></audio>
  <script src="js/main.js"></script>

  <script>
    const modal = document.getElementById('loginModal');
    const modalContainer = modal.querySelector('.modal-container');
    const body = document.body;

    function openLoginModal() {
        modal.classList.remove('hidden');
        void modal.offsetWidth; 
        modal.classList.remove('opacity-0');
        modalContainer.classList.remove('scale-95');
        modalContainer.classList.add('scale-100');
        body.classList.add('modal-open');
        setTimeout(() => document.getElementById('username').focus(), 100);
    }

    function closeLoginModal() {
        modal.classList.add('opacity-0');
        modalContainer.classList.remove('scale-100');
        modalContainer.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            body.classList.remove('modal-open');
        }, 300);
        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('login_error');
            url.searchParams.delete('trigger_login');
            window.history.replaceState(null, '', url);
        }
    }

    modal.addEventListener('click', (e) => { if (e.target === modal) closeLoginModal(); });
    <?php if (!empty($login_error) || $trigger_login): ?>
        document.addEventListener('DOMContentLoaded', openLoginModal);
    <?php endif; ?>
  </script>

</body>
</html>