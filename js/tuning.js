class SearchableSelect {
    constructor(selector, placeholder, onChangeCallback = null) {
        this.originalSelect = document.querySelector(selector);
        this.placeholder = placeholder;
        this.onChangeCallback = onChangeCallback;
        this.isOpen = false;

        if (this.originalSelect) {
            this.originalSelect.classList.add("hidden");
            this.renderUI();
            this.bindEvents();
            this.syncFromOriginal();
        }
    }

    renderUI() {
        this.wrapper = document.createElement("div");
        this.wrapper.className = "relative w-full";
        this.originalSelect.insertAdjacentElement("afterend", this.wrapper);

        this.trigger = document.createElement("div");
        this.trigger.className = "w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 flex justify-between items-center cursor-pointer hover:border-indigo-500 transition-all group select-none shadow-sm";
        this.trigger.innerHTML = `<span class="text-slate-400 text-sm font-medium truncate">${this.placeholder}</span>
        <svg class="w-4 h-4 text-slate-500 group-hover:text-indigo-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>`;

        this.dropdown = document.createElement("div");
        this.dropdown.className = "absolute top-full left-0 w-full mt-2 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl z-50 hidden overflow-hidden ring-1 ring-white/5 origin-top scale-95 opacity-0 transition-all duration-200";
        this.dropdown.innerHTML = `
        <div class="p-2 border-b border-slate-800 sticky top-0 bg-slate-900 z-10">
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-white focus:ring-1 focus:ring-indigo-500 outline-none placeholder-slate-600" placeholder="Search...">
            </div>
        </div>
        <div class="options-list max-h-60 overflow-y-auto custom-scrollbar p-1"></div>`;

        this.wrapper.append(this.trigger, this.dropdown);
        this.optionsList = this.dropdown.querySelector(".options-list");
        this.searchInput = this.dropdown.querySelector("input");
    }

    bindEvents() {
        this.trigger.addEventListener("click", (e) => {
            if (this.originalSelect.disabled) return;
            e.stopPropagation();
            this.toggle();
        });

        this.searchInput.addEventListener("input", (e) => {
            const term = e.target.value.toLowerCase();
            this.optionsList.querySelectorAll(".custom-option").forEach((opt) => {
                opt.style.display = opt.textContent.toLowerCase().includes(term) ? "flex" : "none";
            });
        });

        this.optionsList.addEventListener("click", (e) => {
            const optionDiv = e.target.closest('.custom-option');
            if (!optionDiv) return;
            
            const value = optionDiv.dataset.value;
            this.originalSelect.value = value;
            this.updateTriggerText(optionDiv.querySelector('span').textContent);
            this.close();
            if (this.onChangeCallback) this.onChangeCallback(value);
            
            this.syncFromOriginal(); 
        });

        document.addEventListener("click", (e) => {
            if (!this.wrapper.contains(e.target)) this.close();
        });
    }

    syncFromOriginal() {
        this.optionsList.innerHTML = "";
        const options = this.originalSelect.options;

        if (options.length <= 1) {
            this.optionsList.innerHTML = `<div class="p-4 text-center text-xs text-slate-500 italic">No data available</div>`;
        } else {
            Array.from(options).forEach((opt) => {
                if (!opt.value) return;
                const isSelected = opt.selected;
                
                const div = document.createElement("div");
                div.className = `custom-option px-3 py-2.5 rounded-lg cursor-pointer text-sm mb-1 flex items-center justify-between group transition-all ${isSelected ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800'}`;
                div.dataset.value = opt.value;
                
                div.innerHTML = `
                    <span class="truncate font-medium">${opt.text}</span>
                    ${isSelected ? `
                        <span class="flex items-center justify-center w-5 h-5 bg-indigo-500 rounded-full text-white shadow-lg shadow-indigo-500/40">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </span>` : ''}
                `;
                
                this.optionsList.appendChild(div);
                
                if (isSelected) this.updateTriggerText(opt.text);
            });
        }
        this.wrapper.classList.toggle("opacity-50", this.originalSelect.disabled);
    }

    toggle() { this.isOpen ? this.close() : this.open(); }

    open() {
        this.isOpen = true;
        this.dropdown.classList.remove("hidden");
        requestAnimationFrame(() => {
            this.dropdown.classList.add("scale-100", "opacity-100");
            this.trigger.querySelector("svg").classList.add("rotate-180");
            this.searchInput.focus();
        });
    }

    close() {
        this.isOpen = false;
        this.dropdown.classList.remove("scale-100", "opacity-100");
        this.trigger.querySelector("svg").classList.remove("rotate-180");
        setTimeout(() => this.dropdown.classList.add("hidden"), 200);
        this.searchInput.value = "";
        this.optionsList.querySelectorAll(".custom-option").forEach((opt) => (opt.style.display = "flex"));
    }

    updateTriggerText(text) {
        const span = this.trigger.querySelector("span");
        span.textContent = text;
        span.classList.remove("text-slate-400");
        span.classList.add("text-white");
    }

    setLoading(isLoading) {
        if (isLoading) {
            this.trigger.querySelector("span").innerHTML = '<div class="flex items-center gap-2"><div class="w-4 h-4 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div><span class="text-slate-400">Loading...</span></div>';
            this.originalSelect.disabled = true;
            this.wrapper.classList.add("opacity-75", "pointer-events-none");
        } else {
            this.originalSelect.disabled = false;
            this.wrapper.classList.remove("opacity-75", "pointer-events-none");
        }
    }

    reset() {
        this.originalSelect.value = "";
        this.trigger.querySelector("span").textContent = this.placeholder;
        this.syncFromOriginal();
    }
}

class TuningApp {
    constructor() {
        this.el = {
            line: document.querySelector("#line_id"),
            assembly: document.querySelector("#assembly_name"),
            preview: {
                container: document.querySelector("#preview_content"),
                placeholder: document.querySelector("#preview_placeholder"),
                badge: document.querySelector("#preview_status_badge"),
            },
            form: document.getElementById("tuning_form"),
            submitBtn: document.getElementById("submit_button"),
            statusMsg: document.getElementById("status_message"),
            clock: document.getElementById("clock"),
            date: document.getElementById("date")
        };

        this.state = {
            currentLineId: null,
            currentLineData: null,
            currentHistoryData: [],
            chartInstance: null
        };

        this.init();
    }

    init() {
        this.lineSelectUI = new SearchableSelect("#line_id", "-- Select Production Line --", (id) => this.handleLineChange(id));
        this.assemblySelectUI = new SearchableSelect("#assembly_name", "-- Choose Line First --", (name) => this.handleAssemblyChange(name));
        
        if(this.el.form) {
            this.el.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }

        this.startClock();
    }

    startClock() {
        const update = () => {
            const now = new Date();
            const days = ["MIN", "SEN", "SEL", "RAB", "KAM", "JUM", "SAB"];
            const months = ["JAN", "FEB", "MAR", "APR", "MEI", "JUN", "JUL", "AGU", "SEP", "OKT", "NOV", "DES"];
            
            if (this.el.clock) this.el.clock.textContent = now.toLocaleTimeString("id-ID", { hour12: false });
            if (this.el.date) this.el.date.textContent = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
        };
        update();
        setInterval(update, 1000);
    }

    handleLineChange(lineId) {
        this.state.currentLineId = lineId;
        this.assemblySelectUI.reset();
        this.assemblySelectUI.setLoading(true);
        this.state.currentHistoryData = [];
        this.el.preview.badge.textContent = lineId ? `LINE ${lineId}` : 'NO SELECTION';
        this.el.preview.badge.className = `px-3 py-1 rounded-md text-[10px] uppercase font-bold tracking-widest ${lineId ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30 shadow-[0_0_10px_rgba(59,130,246,0.2)]' : 'bg-slate-800 text-slate-500 border border-slate-700'}`;

        this.el.preview.container.classList.add("hidden");
        this.el.preview.placeholder.classList.remove("hidden");
        this.el.preview.placeholder.innerHTML = `<div class="animate-pulse flex flex-col items-center justify-center py-10"><div class="w-12 h-12 border-4 border-slate-800 border-t-indigo-500 rounded-full animate-spin mb-4 shadow-lg shadow-indigo-500/20"></div><span class="text-xs text-indigo-300 font-bold uppercase tracking-widest">Fetching Data...</span></div>`;

        if (!lineId) {
            this.assemblySelectUI.setLoading(false);
            this.el.preview.placeholder.innerHTML = `
                <div class="w-20 h-20 bg-slate-800/50 rounded-full flex items-center justify-center mb-4 ring-1 ring-white/5"><svg class="w-10 h-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg></div>
                <h4 class="text-slate-300 font-bold text-sm tracking-wide">Ready to Tune</h4>
                <p class="text-slate-500 text-xs mt-2 max-w-[220px] leading-relaxed">Select a line to view performance metrics and debugging history.</p>`;
            return;
        }

        fetch("api/get_assemblies.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ line_id: lineId }),
        })
            .then((res) => res.json())
            .then((response) => {
                const opts = response.all_assemblies || [];
                const current = response.current_assembly || "";
                
                this.el.assembly.innerHTML = `<option value="">-- Select Assembly --</option>` + 
                    opts.map((a) => `<option value="${a}" ${a === current ? "selected" : ""}>${a}</option>`).join("");
                
                this.assemblySelectUI.setLoading(false);
                this.assemblySelectUI.syncFromOriginal();
                
                if (this.el.assembly.value) {
                    this.handleAssemblyChange(this.el.assembly.value);
                }
            })
            .catch(err => {
                this.assemblySelectUI.setLoading(false);
                console.error(err);
            });

        this.fetchPreviewData(lineId);
    }

    handleAssemblyChange(selected) {
        if (selected && this.state.currentLineId) {
            this.fetchTuningHistory(this.state.currentLineId, selected);
        }
    }

    async fetchPreviewData(lineId) {
        try {
            const res = await fetch(`api/get_dashboard_data.php?t=${Date.now()}`);
            const data = await res.json();
            
            let lineData = null;
            if (data.lines && data.lines[`line_${lineId}`]) {
                lineData = data.lines[`line_${lineId}`];
            } else if (data[`line_${lineId}`]) {
                lineData = data[`line_${lineId}`];
            }

            this.state.currentLineData = lineData;
            
            if (lineData) {
                this.renderPreviewPanel();
            } else {
                this.el.preview.placeholder.innerHTML = `<p class="text-red-400 text-xs font-bold bg-red-500/10 px-3 py-2 rounded-lg border border-red-500/20">System Offline / No Data</p>`;
            }
        } catch (e) { 
            console.error("Preview Data Error", e);
            this.el.preview.placeholder.innerHTML = `<p class="text-red-400 text-xs font-bold">Connection Failed</p>`;
        }
    }

    fetchTuningHistory(lineId, assembly) {
        const formData = new URLSearchParams();
        formData.append('line_filter', lineId);
        formData.append('search[value]', assembly);
        formData.append('start', 0);
        formData.append('length', 5);

        fetch("api/get_report_data.php", { method: "POST", body: formData })
            .then((res) => res.json())
            .then((data) => {
                const map = new Map();
                (data.data || []).forEach((row) => {
                    if (row.TuningCycleID && !map.has(row.TuningCycleID)) {
                        map.set(row.TuningCycleID, {
                            version: row.TuningCycleID,
                            date: row.EndTime,
                            user: row.DebuggerFullName,
                            notes: row.Notes,
                        });
                    }
                });
                this.state.currentHistoryData = [...map.values()].sort((a, b) => b.version - a.version);
                this.renderPreviewPanel();
            })
            .catch(e => console.error("History Error", e));
    }

    renderPreviewPanel() {
        const data = this.state.currentLineData;
        const history = this.state.currentHistoryData;
        
        if (!data) return;

        this.el.preview.placeholder.classList.add("hidden");
        this.el.preview.container.classList.remove("hidden");

        const passRate = parseFloat(data.kpi.pass_rate) || 0;
        const beforeRate = parseFloat(data.comparison_data?.before?.pass_rate) || 0;
        const isImproved = passRate >= beforeRate;
        const diff = (passRate - beforeRate).toFixed(2);
        
        const isCritical = data.is_critical_alert;
        const statusColor = isCritical ? "text-red-400" : (data.status === "Pass" ? "text-green-400" : "text-blue-400");
        const borderColor = isCritical ? "border-red-500/50 shadow-red-500/10" : "border-slate-800";

        const historyHtml = history.length > 0 
            ? history.map(h => `
                <div class="group flex flex-col border-b border-slate-800/50 pb-2 mb-2 last:border-0 last:mb-0 last:pb-0 hover:bg-slate-800/30 p-2 rounded-lg transition-colors">
                    <div class="flex justify-between items-start">
                        <span class="text-xs font-bold text-indigo-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></span> Cycle #${h.version}
                        </span>
                        <span class="text-[9px] text-slate-500 font-mono">${new Date(h.date).toLocaleDateString()}</span>
                    </div>
                    <p class="text-[10px] text-slate-300 italic line-clamp-2 mt-1 pl-2 border-l-2 border-slate-700">"${h.notes || 'No notes'}"</p>
                    <div class="text-[9px] text-slate-500 mt-1 pl-2">By: <span class="text-slate-400 font-medium">${h.user || 'Unknown'}</span></div>
                </div>
              `).join('')
            : `<div class="text-center text-slate-600 text-xs py-6 bg-slate-900/50 rounded-lg border border-slate-800 border-dashed">No cycle history available.</div>`;

        this.el.preview.container.innerHTML = `
        <div class="space-y-4 animate-[fadeIn_0.3s_ease-out]">
            <div class="bg-slate-950 border ${borderColor} rounded-2xl p-5 shadow-xl relative overflow-hidden">
                ${isCritical ? '<div class="absolute top-0 right-0 p-2"><span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-red-400 opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span></div>' : ''}
                
                <div class="flex justify-between items-start mb-6 relative z-10">
                    <div>
                        <div class="text-[10px] uppercase text-slate-500 font-bold tracking-wider mb-1">Running Assembly</div>
                        <div class="text-xl font-bold text-white leading-tight tracking-tight">${data.kpi.assembly || 'N/A'}</div>
                        <div class="text-xs text-indigo-400 mt-0.5 font-mono">${data.kpi.lot_code || '-'}</div>
                    </div>
                    <div class="text-right">
                         <div class="inline-block px-3 py-1 rounded-full bg-slate-900 border border-slate-700 text-xs font-bold ${statusColor} shadow-inner">
                            ${data.status}
                         </div>
                         <div class="text-[10px] text-slate-600 mt-2 font-mono">${data.details.time}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-800">
                        <div class="text-[10px] text-slate-500 uppercase font-bold">Pass Rate</div>
                        <div class="flex items-end gap-2">
                            <div class="text-2xl font-bold ${passRate >= 90 ? 'text-green-400' : 'text-yellow-400'}">${passRate}%</div>
                            <div class="text-[10px] font-bold mb-1 ${isImproved ? 'text-green-500' : 'text-red-500'}">
                                ${isImproved ? '▲' : '▼'} ${Math.abs(diff)}%
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-800">
                        <div class="text-[10px] text-slate-500 uppercase font-bold">Total Defects</div>
                        <div class="text-2xl font-bold text-red-400">${data.kpi.total_defect}</div>
                    </div>
                </div>

                <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-800 relative">
                    <h5 class="text-[9px] text-slate-500 uppercase font-bold tracking-wider mb-2 absolute top-3 left-3">Performance Trend</h5>
                    <div class="h-32 w-full mt-2">
                        <canvas id="previewComparisonChart"></canvas>
                    </div>
                </div>

                ${data.image_url ? 
                    `<div class="w-full h-32 bg-slate-950 rounded-lg overflow-hidden border border-slate-800 relative group mt-4">
                        <img src="${data.image_url}" class="w-full h-full object-contain">
                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-xs text-white font-bold">Last Inspection</span>
                        </div>
                    </div>` 
                    : ''
                }
            </div>

            <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5 shadow-lg">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2 border-b border-slate-800 pb-2">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Recent Cycle Logs
                </h4>
                <div class="max-h-48 overflow-y-auto custom-scrollbar pr-1 space-y-1">
                    ${historyHtml}
                </div>
            </div>
        </div>`;

        this.renderChart(beforeRate, passRate);
    }

    renderChart(before, current) {
        if (this.state.chartInstance) {
            this.state.chartInstance.destroy();
        }

        const ctx = document.getElementById('previewComparisonChart');
        if (!ctx) return;

        const ChartDataLabels = window.ChartDataLabels;

        this.state.chartInstance = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Before', 'Current'],
                datasets: [{
                    data: [before, current],
                    backgroundColor: ['#334155', '#4f46e5'],
                    borderRadius: 6,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false },
                    datalabels: {
                        color: '#fff',
                        anchor: 'end',
                        align: 'bottom',
                        offset: -20,
                        font: { weight: 'bold', size: 10 },
                        formatter: (val) => val > 0 ? val + '%' : ''
                    }
                },
                scales: {
                    y: { 
                        display: false, 
                        max: 110 
                    },
                    x: { 
                        grid: { display: false, drawBorder: false },
                        ticks: { color: '#94a3b8', font: { size: 10, weight: 'bold' } }
                    }
                },
                animation: { duration: 800, easing: 'easeOutQuart' }
            },
            plugins: [ChartDataLabels]
        });
    }

    handleSubmit(e) {
        e.preventDefault();
        
        const btn = this.el.submitBtn;
        const msg = this.el.statusMsg;
        const originalText = btn.innerHTML;

        if (!this.el.line.value || !this.el.assembly.value) {
            alert("Please select Line and Assembly first.");
            return;
        }

        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Starting...`;
        
        const formData = new FormData(this.el.form);

        fetch("api/start_new_cycle.php", {
            method: "POST",
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                msg.innerHTML = `<span class="px-3 py-1 bg-green-500/10 border border-green-500/20 rounded-full text-green-400 text-xs font-bold flex items-center gap-2"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> ${data.message}</span>`;
                this.el.form.reset();
                this.lineSelectUI.reset();
                this.assemblySelectUI.reset();
                this.state.currentLineData = null;
                this.state.currentHistoryData = [];
                
                this.el.preview.container.classList.add('hidden');
                this.el.preview.placeholder.classList.remove('hidden');
                this.el.preview.placeholder.innerHTML = `<div class="w-16 h-16 bg-green-500/10 rounded-full flex items-center justify-center mb-4 ring-1 ring-green-500/20 animate-bounce"><svg class="w-8 h-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><h4 class="text-white font-bold tracking-wide">Cycle Started Successfully!</h4><p class="text-xs text-slate-500 mt-2">Ready for next instruction.</p>`;
            } else {
                throw new Error(data.message || "Failed to start cycle");
            }
        })
        .catch(err => {
            msg.innerHTML = `<span class="text-red-400 text-xs font-bold bg-red-500/10 px-2 py-1 rounded border border-red-500/20">${err.message}</span>`;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            setTimeout(() => { msg.innerHTML = ''; }, 5000);
        });
    }
}

document.addEventListener("DOMContentLoaded", () => new TuningApp());