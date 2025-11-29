class DashboardManager {
    constructor() {
        this.config = {
            API_URL: "api/get_dashboard_data.php",
            REFRESH_INTERVAL: 5000,
            TARGET_PASS_RATE: 90,
            TARGET_PPM: 2100,
            SOUND_DELAY: 2000,
        };
        
        this.state = {
            lineCharts: {},
            alertAudio: document.getElementById("alert-sound"),
            isSoundLooping: false,
            isMuted: true,
            soundUnlocked: false,
            panelsRendered: false
        };

        this.init();
    }

    init() {
        if (typeof Chart !== "undefined" && window.ChartDataLabels) {
            Chart.register(ChartDataLabels);
        }

        this.setupAudio();
        // Panels will be created dynamically when data arrives
        this.startLoops();
    }

    setupAudio() {
        const soundToggleBtn = document.getElementById("sound-toggle-btn");
        
        if (this.state.alertAudio) {
            this.state.alertAudio.addEventListener("ended", () => {
                if (this.state.isSoundLooping) {
                    setTimeout(() => this.playAlertSound(), this.config.SOUND_DELAY);
                }
            });
        }

        soundToggleBtn?.addEventListener("click", () => this.toggleSound(soundToggleBtn));
    }

    startLoops() {
        this.fetchData();
        setInterval(() => this.fetchData(), this.config.REFRESH_INTERVAL);
        this.updateClock();
        setInterval(() => this.updateClock(), 1000);
    }

    async fetchData() {
        try {
            const response = await fetch(`${this.config.API_URL}?t=${Date.now()}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const data = await response.json();
            
            // Logic to handle different data structures
            // Priority: 1. data.lines, 2. root object if keys start with 'line_'
            let linesData = {};
            if (data.lines) {
                linesData = data.lines;
            } else if (data && typeof data === 'object') {
                // Check if keys look like "line_X"
                const hasLineKeys = Object.keys(data).some(k => k.startsWith('line_'));
                if (hasLineKeys) {
                    linesData = data;
                }
            }

            if (Object.keys(linesData).length > 0) {
                this.updateDashboardUI(linesData);
            } else {
                console.warn("Invalid or empty data structure from API", data);
            }
        } catch (error) {
            console.error("Dashboard Data Error:", error);
        }
    }

    updateDashboardUI(linesData) {
        const panelArea = document.getElementById("panel-area");
        
        // 1. Render Panels if not rendered OR if DOM is empty (safety check)
        const isEmpty = panelArea && panelArea.children.length === 0;
        
        if (!this.state.panelsRendered || isEmpty) {
            // Remove loading state if present
            const loadingState = panelArea.querySelector('.animate-pulse');
            if (loadingState && loadingState.innerText.includes('CONNECTING')) {
                panelArea.innerHTML = ''; 
            }
            
            this.renderPanels(linesData);
            this.state.panelsRendered = true;
        }

        let criticalFound = false;

        // 2. Update Data Panel
        Object.keys(linesData).forEach(key => {
            const lineNum = key.split('_')[1]; // "line_1" -> "1"
            const data = linesData[key];
            
            if (lineNum) {
                this.updateSinglePanel(lineNum, data);
                if (data.is_critical_alert) criticalFound = true;
            }
        });

        this.manageAlertSound(criticalFound);
    }

    renderPanels(linesData) {
        const panelArea = document.getElementById("panel-area");
        if (!panelArea) return;

        // Ensure we clear previous content before appending
        // Only clear if we are doing a full re-render (e.g. initial load)
        if (panelArea.children.length === 0 || panelArea.querySelector('.animate-pulse')) {
             panelArea.innerHTML = "";
        }

        const sortedKeys = Object.keys(linesData).sort((a, b) => {
             // Extract numbers for correct numeric sort (line_2 vs line_10)
             const numA = parseInt(a.split('_')[1]) || 0;
             const numB = parseInt(b.split('_')[1]) || 0;
             return numA - numB;
        });

        sortedKeys.forEach(key => {
            const lineNum = key.split('_')[1];
            // Only create if it doesn't exist yet
            if (lineNum && !document.getElementById(`panel_status_${lineNum}`)) {
                panelArea.insertAdjacentHTML('beforeend', this.createPanelHTML(lineNum));
            }
        });

        // Re-attach click events (using delegation on parent is safer than re-attaching)
        // Note: The click listener in init/renderPanels in previous versions might stack if not careful.
        // It's better to add the listener ONCE in init or check here.
        // Since we moved it here, let's ensure we don't duplicate logic. 
        // Ideally, listener should be on panelArea once. 
        // For now, to ensure it works after re-render, we leave it, but delegation is handled in constructor/init usually.
        // Let's rely on the existing listener if the panel-area itself wasn't replaced, just its children.
    }

    updateSinglePanel(num, data) {
        // Safety check if panel exists
        const statusEl = document.getElementById(`panel_status_${num}`);
        if (!statusEl) return; // Panel not found, skip update

        const isCritical = data.is_critical_alert;
        const isActive = data.status !== "INACTIVE";
        
        // Update Status Badge
        statusEl.textContent = data.status;
        statusEl.className = "px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider border shadow-sm transition-all duration-300";

        if (isCritical) {
            statusEl.classList.add("bg-red-500/20", "text-red-400", "border-red-500/50", "animate-pulse");
        } else if (data.status === "Pass") {
            statusEl.classList.add("bg-green-500/20", "text-green-400", "border-green-500/50");
        } else if (isActive) {
            statusEl.classList.add("bg-blue-500/20", "text-blue-400", "border-blue-500/50");
        } else {
            statusEl.classList.add("bg-slate-800", "text-slate-500", "border-slate-700");
        }

        // Update Text Details
        this.setText(`detail_assembly_${num}`, data.kpi.assembly);
        this.setText(`detail_time_${num}`, data.details.time);
        this.setText(`detail_ref_${num}`, data.details.component_ref);
        this.setText(`detail_part_${num}`, data.details.part_number);
        this.setText(`detail_machine_defect_${num}`, data.details.machine_defect);
        this.setText(`detail_inspect_${num}`, data.details.inspection_result);
        this.setText(`detail_review_${num}`, data.details.review_result);

        // Update Image & KPI
        this.updateImageContainer(num, data, isCritical, isActive);
        this.updateKPI(num, data.kpi);
        this.updateComparisonChart(num, data.comparison_data);
    }

    updateImageContainer(num, data, isCritical, isActive) {
        const imgContainer = document.getElementById(`image_container_${num}`);
        if (!imgContainer) return;

        imgContainer.className = "image-container relative h-full w-full bg-slate-950 rounded-lg border flex items-center justify-center overflow-hidden cursor-pointer group hover:border-blue-500/50 transition-all min-h-0";

        if (isCritical) {
            imgContainer.classList.add("border-red-500", "shadow-[0_0_15px_rgba(239,68,68,0.3)]");
        } else if (!isActive) {
            imgContainer.classList.add("border-slate-800", "opacity-60");
        }

        if (data.image_url) {
            imgContainer.innerHTML = `<img src="${data.image_url}" alt="Defect" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">`;
        } else {
            const colorClass = isCritical ? "text-red-500" : "text-blue-500";
            const text = isActive ? "NO IMAGE" : "NO SIGNAL";
            imgContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center ${colorClass}">
                    <svg class="w-8 h-8 mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-[10px] font-bold tracking-[0.2em]">${text}</span>
                </div>`;
        }
    }

    updateKPI(line, kpi) {
        this.setVal(`kpi_pass_rate_${line}`, `${kpi.pass_rate}%`, kpi.pass_rate >= this.config.TARGET_PASS_RATE);
        this.setVal(`kpi_ppm_${line}`, kpi.ppm, kpi.ppm <= this.config.TARGET_PPM);
        this.setVal(`kpi_inspected_${line}`, kpi.total_inspected, null);
        this.setColorVal(`kpi_pass_${line}`, kpi.total_pass, "text-green-500");
        this.setColorVal(`kpi_false_call_${line}`, kpi.total_false_call, "text-yellow-500");
    }

    setVal(id, val, good) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = val;
        el.className = `text-lg font-bold leading-none ${good === true ? "text-green-400" : (good === false ? "text-red-400" : "text-white")}`;
    }

    setColorVal(id, val, color) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = val;
        el.className = `text-lg font-bold leading-none ${color}`;
    }

    updateComparisonChart(num, data) {
        const chartId = `comparisonChart_${num}`;
        const canvas = document.getElementById(chartId);
        if (!canvas) return;

        const beforeVal = parseFloat(data.before.pass_rate) || 0;
        const currentVal = parseFloat(data.current.pass_rate) || 0;

        if (this.state.lineCharts[chartId]) {
            this.state.lineCharts[chartId].data.datasets[0].data = [beforeVal, currentVal];
            this.state.lineCharts[chartId].update("none");
            return;
        }

        this.state.lineCharts[chartId] = new Chart(canvas.getContext("2d"), {
            type: "bar",
            data: {
                labels: ["Before", "Current"],
                datasets: [{
                    data: [beforeVal, currentVal],
                    backgroundColor: ["#475569", "#3b82f6"],
                    borderRadius: 3,
                    barPercentage: 0.65,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false },
                    datalabels: {
                        display: true,
                        color: "#fff",
                        anchor: "end",
                        align: "top",
                        offset: 2,
                        font: { size: 10, weight: "bold" },
                        formatter: (v) => `${v}%`,
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 9 } } },
                    y: { display: false, max: 115 },
                },
                animation: false
            },
        });
    }

    toggleSound(btn) {
        if (!this.state.soundUnlocked) {
            this.state.soundUnlocked = true;
            this.state.alertAudio.play().then(() => this.state.alertAudio.pause()).catch(() => {});
        }
        this.state.isMuted = !this.state.isMuted;
        btn.classList.toggle("muted", this.state.isMuted);
        
        const criticalElement = document.querySelector(".animate-pulse.border-red-500");
        this.manageAlertSound(criticalElement !== null);
    }

    manageAlertSound(shouldPlay) {
        if (shouldPlay && !this.state.isMuted && this.state.soundUnlocked && !this.state.isSoundLooping) {
            this.state.isSoundLooping = true;
            this.playAlertSound();
        } else if ((!shouldPlay || this.state.isMuted) && this.state.isSoundLooping) {
            this.state.isSoundLooping = false;
            if (this.state.alertAudio) {
                this.state.alertAudio.pause();
                this.state.alertAudio.currentTime = 0;
            }
        }
    }

    playAlertSound() {
        if (this.state.isSoundLooping && this.state.alertAudio) {
            this.state.alertAudio.play().catch(() => {
                this.state.isSoundLooping = false;
            });
        }
    }

    setText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text || "N/A";
    }

    updateClock() {
        const now = new Date();
        const clockEl = document.getElementById("clock");
        const dateEl = document.getElementById("date");
        const days = ["MIN", "SEN", "SEL", "RAB", "KAM", "JUM", "SAB"];
        const months = ["JAN", "FEB", "MAR", "APR", "MEI", "JUN", "JUL", "AGU", "SEP", "OKT", "NOV", "DES"];

        if (clockEl) clockEl.textContent = now.toLocaleTimeString("id-ID", { hour12: false });
        if (dateEl) dateEl.textContent = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
    }

    createPanelHTML(num) {
        return `
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-2 flex flex-col gap-2 h-full shadow-lg hover:border-slate-700 transition-colors group min-h-0 animate-[fadeIn_0.5s_ease-out]">
            <div class="flex justify-between items-center bg-slate-950/40 p-1.5 rounded-lg border border-slate-800/50 backdrop-blur-sm shrink-0">
                <h2 class="text-xs font-bold text-white flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span> LINE ${num}
                </h2>
                <span id="panel_status_${num}" class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider bg-slate-800 text-slate-500 border border-slate-700">Connecting...</span>
            </div>
            <div class="flex-grow grid grid-cols-5 gap-3 min-h-0">
                <div class="col-span-2 flex flex-col gap-1 h-full overflow-hidden">
                    <div class="flex flex-col border-b border-slate-800/80 pb-1 mb-0.5 shrink-0">
                        <span class="text-[10px] text-slate-500 uppercase tracking-wider font-bold">Assembly</span>
                        <span id="detail_assembly_${num}" class="font-bold text-white text-xs leading-tight break-words">--</span>
                    </div>
                    <div class="space-y-1 overflow-y-auto custom-scrollbar pr-1 flex-grow min-h-0">
                        ${this.detailRow('Time', `detail_time_${num}`)}
                        ${this.detailRow('Ref', `detail_ref_${num}`)}
                        ${this.detailRow('Part', `detail_part_${num}`)}
                        <div class="flex flex-col bg-red-500/5 border-l-2 border-red-500/50 pl-1.5 py-0.5 my-0.5 rounded-r shrink-0">
                            <span class="text-[10px] text-red-400/70 uppercase tracking-wider font-bold">Defect</span>
                            <span id="detail_machine_defect_${num}" class="font-bold text-red-400 text-xs leading-tight break-words">--</span>
                        </div>
                        ${this.detailRow('Insp', `detail_inspect_${num}`)}
                        ${this.detailRow('Rev', `detail_review_${num}`)}
                    </div>
                </div>
                <div class="col-span-3 h-full min-h-0">
                    <div id="image_container_${num}" class="image-container relative h-full w-full bg-slate-950 rounded-lg border border-slate-800 flex items-center justify-center overflow-hidden cursor-pointer shadow-inner" data-line="${num}">
                        <div class="flex flex-col items-center justify-center text-slate-700">
                            <span class="text-[9px] font-bold tracking-widest animate-pulse">SYNCING...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-5 gap-1 bg-slate-950/40 p-1.5 rounded-lg border border-slate-800/50 backdrop-blur-sm shrink-0">
                ${this.kpiBox(`kpi_pass_rate_${num}`, 'Rate')}
                ${this.kpiBox(`kpi_ppm_${num}`, 'PPM')}
                ${this.kpiBox(`kpi_inspected_${num}`, 'Insp')}
                ${this.kpiBox(`kpi_pass_${num}`, 'Pass', 'text-green-500')}
                ${this.kpiBox(`kpi_false_call_${num}`, 'FC', 'text-yellow-500')}
            </div>
            <div class="h-16 w-full shrink-0">
                <canvas id="comparisonChart_${num}"></canvas>
            </div>
        </div>`;
    }

    detailRow(label, id, isBold = false, colorClass = 'text-slate-300') {
        const fontClass = isBold ? 'font-bold text-white' : colorClass;
        return `
        <div class="flex justify-between items-start text-xs border-b border-slate-800/50 pb-0.5 last:border-0 shrink-0">
            <span class="text-slate-500 uppercase tracking-tight text-[9px] w-8 shrink-0 mt-0.5">${label}</span>
            <span id="${id}" class="${fontClass} text-right ml-1 break-all text-[10px]">--</span>
        </div>`;
    }

    kpiBox(id, label, color = 'text-white') {
        return `
        <div class="flex flex-col items-center justify-center p-1 rounded bg-slate-800/30 hover:bg-slate-800/50 transition-colors">
            <div id="${id}" class="text-sm font-bold leading-none ${color}">0</div>
            <div class="text-[8px] text-slate-500 uppercase leading-none mt-0.5 font-bold tracking-tight">${label}</div>
        </div>`;
    }
}

class DashboardAuth {
    static openLoginModal() {
        const modal = document.getElementById('loginModal');
        const modalBox = document.getElementById('loginModalBox');
        if (!modal || !modalBox) return;

        modal.classList.remove('hidden');
        void modal.offsetWidth; // Force reflow
        modal.classList.remove('opacity-0');
        modalBox.classList.remove('scale-95');
        modalBox.classList.add('scale-100');
        
        const mainContent = document.getElementById('main-content');
        if (mainContent) mainContent.style.filter = 'blur(5px)';
        setTimeout(() => document.getElementById('username')?.focus(), 100);
    }

    static closeLoginModal() {
        const modal = document.getElementById('loginModal');
        const modalBox = document.getElementById('loginModalBox');
        if (!modal || !modalBox) return;

        modal.classList.add('opacity-0');
        modalBox.classList.remove('scale-100');
        modalBox.classList.add('scale-95');
        
        const mainContent = document.getElementById('main-content');
        if (mainContent) mainContent.style.filter = 'none';
        
        setTimeout(() => modal.classList.add('hidden'), 300);

        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('login_error');
            url.searchParams.delete('trigger_login');
            window.history.replaceState(null, '', url);
        }
    }

    static init() {
        document.getElementById('closeModalBtn')?.addEventListener('click', this.closeLoginModal);
        document.getElementById('loginModal')?.addEventListener('click', (e) => {
            if (e.target === e.currentTarget) this.closeLoginModal();
        });
    }
}

// Global scope exposure
window.DashboardAuth = DashboardAuth;

document.addEventListener("DOMContentLoaded", () => {
    new DashboardManager();
    DashboardAuth.init();
});