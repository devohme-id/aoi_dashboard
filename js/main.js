/**
 * main.js - Dashboard Logic (Compact Layout & Smart Design)
 * Revised: Image Container wrapped for robust grid sizing
 */

const CONFIG = {
    API_URL: 'api/get_dashboard_data.php',
    REFRESH_INTERVAL: 5000,
    TARGET_PASS_RATE: 90,
    TARGET_PPM: 2100,
    SOUND_DELAY: 2000
};

const state = {
    lineCharts: {},
    alertAudio: null,
    isSoundLooping: false,
    isMuted: true,
    soundUnlocked: false
};

document.addEventListener('DOMContentLoaded', () => {
    // 1. Chart.js Setup
    if (typeof Chart !== 'undefined' && ChartDataLabels) {
        Chart.register(ChartDataLabels);
    }

    // 2. Audio Setup
    state.alertAudio = document.getElementById('alert-sound');
    const soundToggleBtn = document.getElementById('sound-toggle-btn');

    if (state.alertAudio) {
        state.alertAudio.addEventListener('ended', () => {
            if (state.isSoundLooping) setTimeout(playAlertSound, CONFIG.SOUND_DELAY);
        });
    }

    if (soundToggleBtn) {
        soundToggleBtn.addEventListener('click', () => toggleSound(soundToggleBtn));
    }

    // 3. Render Panel Grid
    const panelArea = document.getElementById('panel-area');
    if (panelArea) {
        let content = '';
        for (let i = 1; i <= 6; i++) {
            content += createPanelHTML(i);
        }
        panelArea.innerHTML = content;

        // Navigasi saat klik gambar
        panelArea.addEventListener('click', (event) => {
            const container = event.target.closest('.image-container');
            if (container && container.dataset.line) {
                window.location.href = `feedback.php?line=${container.dataset.line}`;
            }
        });
    }

    // 4. Start Loops
    fetchData();
    setInterval(fetchData, CONFIG.REFRESH_INTERVAL);
    updateClock();
    setInterval(updateClock, 1000);
});

// --- Data Fetching ---
async function fetchData() {
    try {
        const response = await fetch(`${CONFIG.API_URL}?t=${Date.now()}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (data.error) throw new Error(data.error);
        updateDashboardUI(data.lines || {});
    } catch (error) {
        console.warn('Dashboard Data Error:', error);
    }
}

function updateDashboardUI(linesData) {
    let isAnyCriticalAlertActive = false;
    for (let i = 1; i <= 6; i++) {
        const lineKey = `line_${i}`;
        const lineData = linesData[lineKey] || createDefaultLineData();
        updateSinglePanel(i, lineData);
        if (lineData.is_critical_alert) isAnyCriticalAlertActive = true;
    }
    manageAlertSound(isAnyCriticalAlertActive);
}

function updateSinglePanel(lineNumber, data) {
    const isCritical = data.is_critical_alert;
    const isActive = data.status !== 'INACTIVE';
    
    // Status Header Styling
    const statusEl = document.getElementById(`panel_status_${lineNumber}`);
    if (statusEl) {
        statusEl.textContent = data.status;
        statusEl.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider border shadow-sm transition-all duration-300';
        
        if (isCritical) {
            statusEl.classList.add('bg-red-500/20', 'text-red-400', 'border-red-500/50', 'animate-pulse');
        } else if (data.status === 'Pass') {
            statusEl.classList.add('bg-green-500/20', 'text-green-400', 'border-green-500/50');
        } else if (isActive) {
            statusEl.classList.add('bg-blue-500/20', 'text-blue-400', 'border-blue-500/50');
        } else {
            statusEl.classList.add('bg-slate-800', 'text-slate-500', 'border-slate-700');
        }
    }

    // Text Details
    setText(`detail_assembly_${lineNumber}`, data.kpi.assembly);
    setText(`detail_time_${lineNumber}`, data.details.time);
    setText(`detail_ref_${lineNumber}`, data.details.component_ref);
    setText(`detail_part_${lineNumber}`, data.details.part_number);
    
    // Defect special handling
    setText(`detail_machine_defect_${lineNumber}`, data.details.machine_defect);
    
    setText(`detail_inspect_${lineNumber}`, data.details.inspection_result);
    setText(`detail_review_${lineNumber}`, data.details.review_result);

    // Image / Visual State
    const imgContainer = document.getElementById(`image_container_${lineNumber}`);
    if (imgContainer) {
        imgContainer.className = 'image-container relative h-full w-full bg-slate-950 rounded-lg border flex items-center justify-center overflow-hidden cursor-pointer group hover:border-blue-500/50 transition-all min-h-0';
        
        if (isCritical) {
            imgContainer.classList.add('border-red-500', 'shadow-[0_0_15px_rgba(239,68,68,0.3)]');
        } else if (isActive) {
            imgContainer.classList.add('border-slate-800');
        } else {
            imgContainer.classList.add('border-slate-800', 'opacity-60');
        }

        if (data.image_url) {
            imgContainer.innerHTML = `<img src="${data.image_url}" alt="Defect" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">`;
        } else {
            const statusColor = isCritical ? 'text-red-500' : (isActive ? 'text-blue-500' : 'text-slate-700');
            const displayText = data.status === 'INACTIVE' ? 'NO SIGNAL' : 'NO IMAGE';
            
            imgContainer.innerHTML = `<div class="flex flex-col items-center justify-center ${statusColor} transition-colors duration-300">
                <svg class="w-8 h-8 mb-1 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-[10px] font-bold tracking-[0.2em]">${displayText}</span>
            </div>`;
        }
    }

    updateKPI(lineNumber, data.kpi);
    updateComparisonChart(lineNumber, data.comparison_data);
}

function updateKPI(line, kpi) {
    const setVal = (id, val, good) => {
        const el = document.getElementById(id);
        if(!el) return;
        el.textContent = val;
        el.className = `text-lg font-bold leading-none ${good === true ? 'text-green-400' : (good === false ? 'text-red-400' : 'text-white')}`;
    };

    setVal(`kpi_pass_rate_${line}`, `${kpi.pass_rate}%`, kpi.pass_rate >= CONFIG.TARGET_PASS_RATE);
    setVal(`kpi_ppm_${line}`, kpi.ppm, kpi.ppm <= CONFIG.TARGET_PPM);
    setVal(`kpi_inspected_${line}`, kpi.total_inspected, null);
    
    const passEl = document.getElementById(`kpi_pass_${line}`);
    if(passEl) {
        passEl.textContent = kpi.total_pass;
        passEl.className = "text-lg font-bold leading-none text-green-500";
    }
    
    const failEl = document.getElementById(`kpi_false_call_${line}`);
    if(failEl) {
        failEl.textContent = kpi.total_false_call;
        failEl.className = "text-lg font-bold leading-none text-yellow-500";
    }
}

function updateComparisonChart(lineNumber, data) {
    const chartId = `comparisonChart_${lineNumber}`;
    const canvas = document.getElementById(chartId);
    if (!canvas) return;

    const beforeVal = parseFloat(data.before.pass_rate) || 0;
    const currentVal = parseFloat(data.current.pass_rate) || 0;

    if (state.lineCharts[chartId]) {
        state.lineCharts[chartId].data.datasets[0].data = [beforeVal, currentVal];
        state.lineCharts[chartId].update('none');
    } else {
        state.lineCharts[chartId] = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Before', 'Current'],
                datasets: [{
                    label: 'Pass Rate',
                    data: [beforeVal, currentVal],
                    backgroundColor: ['#475569', '#3b82f6'],
                    borderRadius: 3,
                    barPercentage: 0.65
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 20, left: 0, right: 0, bottom: 0 } },
                plugins: { 
                    legend: { display: false },
                    datalabels: { 
                        display: true, color: '#fff', anchor: 'end', align: 'top', 
                        offset: 2, font: { size: 10, weight: 'bold' }, 
                        formatter: v => v + '%' 
                    }
                },
                scales: {
                    x: { display: true, ticks: { color: '#94a3b8', font: { size: 9 } }, grid: { display: false } },
                    y: { display: false, max: 115 }
                }
            }
        });
    }
}

// --- HTML Template (Full Viewport & Wrapped Image) ---
function createPanelHTML(num) {
    return `
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-2 flex flex-col gap-2 h-full shadow-lg hover:border-slate-700 transition-colors group min-h-0">
        <!-- HEADER -->
        <div class="flex justify-between items-center bg-slate-950/40 p-1.5 rounded-lg border border-slate-800/50 backdrop-blur-sm shrink-0">
            <h2 class="text-xs font-bold text-white flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span> LINE ${num}
            </h2>
            <span id="panel_status_${num}" class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider bg-slate-800 text-slate-500 border border-slate-700">INACTIVE</span>
        </div>

        <!-- MAIN CONTENT (GRID) -->
        <div class="flex-grow grid grid-cols-5 gap-3 min-h-0">
            <!-- Left: Detail Text (2 Cols) -->
            <div class="col-span-2 flex flex-col gap-1 h-full overflow-hidden">
                
                <!-- Assembly Info -->
                <div class="flex flex-col border-b border-slate-800/80 pb-1 mb-0.5 shrink-0">
                    <span class="text-[10px] text-slate-500 uppercase tracking-wider font-bold">Assembly</span>
                    <span id="detail_assembly_${num}" class="font-bold text-white text-xs leading-tight break-words">N/A</span>
                </div>

                <div class="space-y-1 overflow-y-auto custom-scrollbar pr-1 flex-grow min-h-0">
                    ${detailRow('Time', `detail_time_${num}`)}
                    ${detailRow('Ref', `detail_ref_${num}`)}
                    ${detailRow('Part', `detail_part_${num}`)}
                    
                    <!-- Defect Row -->
                    <div class="flex flex-col bg-red-500/5 border-l-2 border-red-500/50 pl-1.5 py-0.5 my-0.5 rounded-r shrink-0">
                        <span class="text-[10px] text-red-400/70 uppercase tracking-wider font-bold">Defect</span>
                        <span id="detail_machine_defect_${num}" class="font-bold text-red-400 text-xs leading-tight break-words">N/A</span>
                    </div>
                    
                    ${detailRow('Insp', `detail_inspect_${num}`)}
                    ${detailRow('Rev', `detail_review_${num}`)}
                </div>
            </div>

            <!-- Right: Image (3 Cols) with Wrapper -->
            <div class="col-span-3 h-full min-h-0">
                <div id="image_container_${num}" class="image-container relative h-full w-full bg-slate-950 rounded-lg border border-slate-800 flex items-center justify-center overflow-hidden cursor-pointer shadow-inner" data-line="${num}">
                    <div class="flex flex-col items-center justify-center text-slate-700">
                        <span class="text-[9px] font-bold tracking-widest">LOADING...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI STATS -->
        <div class="grid grid-cols-5 gap-1 bg-slate-950/40 p-1.5 rounded-lg border border-slate-800/50 backdrop-blur-sm shrink-0">
            ${kpiBox(`kpi_pass_rate_${num}`, 'Rate')}
            ${kpiBox(`kpi_ppm_${num}`, 'PPM')}
            ${kpiBox(`kpi_inspected_${num}`, 'Insp')}
            ${kpiBox(`kpi_pass_${num}`, 'Pass', 'text-green-500')}
            ${kpiBox(`kpi_false_call_${num}`, 'FC', 'text-yellow-500')}
        </div>

        <!-- CHART -->
        <div class="h-16 w-full shrink-0">
            <canvas id="comparisonChart_${num}"></canvas>
        </div>
    </div>`;
}

function detailRow(label, id, isBold = false, colorClass = 'text-slate-300') {
    const fontClass = isBold ? 'font-bold text-white' : colorClass;
    return `
    <div class="flex justify-between items-start text-xs border-b border-slate-800/50 pb-0.5 last:border-0 shrink-0">
        <span class="text-slate-500 uppercase tracking-tight text-[9px] w-8 shrink-0 mt-0.5">${label}</span>
        <span id="${id}" class="${fontClass} text-right ml-1 break-all text-[10px]">N/A</span>
    </div>`;
}

function kpiBox(id, label, color = 'text-white') {
    return `
    <div class="flex flex-col items-center justify-center p-1 rounded bg-slate-800/30 hover:bg-slate-800/50 transition-colors">
        <div id="${id}" class="text-sm font-bold leading-none ${color}">0</div>
        <div class="text-[8px] text-slate-500 uppercase leading-none mt-0.5 font-bold tracking-tight">${label}</div>
    </div>`;
}

// --- Helpers ---
function toggleSound(btn) {
    if (!state.soundUnlocked) {
        state.soundUnlocked = true;
        state.alertAudio.play().then(() => state.alertAudio.pause()).catch(() => {});
    }
    state.isMuted = !state.isMuted;
    btn.classList.toggle('muted', state.isMuted);
    const isCritical = document.querySelector('.critical-alert') !== null;
    manageAlertSound(isCritical);
}

function manageAlertSound(shouldPlay) {
    if (shouldPlay && !state.isMuted && state.soundUnlocked && !state.isSoundLooping) {
        state.isSoundLooping = true;
        playAlertSound();
    } else if ((!shouldPlay || state.isMuted) && state.isSoundLooping) {
        state.isSoundLooping = false;
        if (state.alertAudio) {
            state.alertAudio.pause();
            state.alertAudio.currentTime = 0;
        }
    }
}

function playAlertSound() {
    if (state.isSoundLooping && state.alertAudio) {
        state.alertAudio.play().catch(e => {
            console.warn("Audio Blocked", e);
            state.isSoundLooping = false;
        });
    }
}

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text || 'N/A';
}

function normalizeStatus(status) { return (status || 'inactive').toLowerCase().replace(/\s+/g, '_'); }

// REVISI CLOCK
function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
    // Format: JUM, 29 NOV 2025
    const days = ['MIN', 'SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB'];
    const months = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
    
    const dayName = days[now.getDay()];
    const dayNum = now.getDate();
    const monthName = months[now.getMonth()];
    const year = now.getFullYear();
    
    const dateStr = `${dayName}, ${dayNum} ${monthName} ${year}`;
    
    const clockEl = document.getElementById('clock');
    const dateEl = document.getElementById('date');
    
    if (clockEl) clockEl.textContent = timeStr;
    if (dateEl) dateEl.textContent = dateStr;
}

function createDefaultLineData() {
    return {
        status: 'INACTIVE', is_critical_alert: false, image_url: null,
        details: { time: '-', component_ref: '-', part_number: '-', machine_defect: '-', inspection_result: '-', review_result: '-' },
        kpi: { assembly: '-', total_inspected: 0, total_pass: 0, total_defect: 0, total_false_call: 0, pass_rate: 0, ppm: 0 },
        comparison_data: { before: { pass_rate: 0 }, current: { pass_rate: 0 } }
    };
}