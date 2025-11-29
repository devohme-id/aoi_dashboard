<!-- templates/header_common.php -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- 1. TAILWIND CSS & CONFIG -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        colors: {
          slate: { 850: '#151e2e', 900: '#0f172a', 950: '#020617' }
        },
        fontFamily: {
          sans: ['Inter', 'sans-serif'],
        },
        animation: {
          'fade-in-up': 'fadeInUp 0.3s ease-out forwards',
          'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        },
        keyframes: {
          fadeInUp: {
            '0%': { opacity: '0', transform: 'translateY(10px)' },
            '100%': { opacity: '1', transform: 'translateY(0)' },
          }
        }
      }
    }
  }
</script>

<!-- 2. GLOBAL CSS CUSTOM (Tailwind Utilities) -->
<style type="text/tailwindcss">
  @layer utilities {
    /* Custom Scrollbar Global */
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { @apply bg-slate-900; }
    .custom-scrollbar::-webkit-scrollbar-thumb { @apply bg-slate-700 rounded-full hover:bg-slate-600 transition-colors; }
    
    /* Global Transitions */
    .transition-smooth { @apply transition-all duration-300 ease-in-out; }
  }
</style>

<!-- 3. LIBRARY CSS UMUM -->
<link rel="stylesheet" href="css/style.css">
<!-- Font Inter (Opsional jika ingin load dari Google Fonts) -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">