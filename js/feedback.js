document.addEventListener("DOMContentLoaded", () => {
    // === CONFIGURATION ===
    const API_URL = "api/feedback_handler.php";
    
    // Elements
    const elements = {
        userId: document.getElementById("userId"),
        tableBody: document.getElementById("feedback-table-body"),
        loading: document.getElementById("loading-indicator"),
        detailPlaceholder: document.getElementById("detail-view-placeholder"),
        detailContent: document.getElementById("detail-view-content"),
        filters: {
            line: document.getElementById("line-filter"),
            defect: document.getElementById("defect-filter"),
            assembly: document.getElementById("assembly-filter"),
            date: document.getElementById("date-range-filter")
        }
    };

    // State
    const state = {
        currentAnalystId: parseInt(elements.userId?.dataset.id || 0),
        verificationData: [],
        allLinesData: [],
        selectedDefectId: null,
        datePicker: null
    };

    // ... (Helper & Init logic tetap sama, langsung ke bagian updateClock) ...
    // HELPER: Redirect to Login Modal
    function handleSessionTimeout() {
        const currentUrl = encodeURIComponent(window.location.pathname + window.location.search);
        window.location.href = `index.php?trigger_login=true&redirect=${currentUrl}&login_error=Sesi+berakhir.+Silakan+login.`;
    }

    function init() {
        if(elements.filters.date) {
            state.datePicker = flatpickr(elements.filters.date, {
                mode: "range",
                dateFormat: "Y-m-d",
                onChange: applyFilters
            });
        }

        if(elements.filters.line) elements.filters.line.addEventListener("change", applyFilters);
        if(elements.filters.defect) elements.filters.defect.addEventListener("change", applyFilters);
        if(elements.filters.assembly) elements.filters.assembly.addEventListener("keyup", applyFilters);
        
        if(elements.tableBody) elements.tableBody.addEventListener("click", handleRowClick);
        
        if(elements.detailContent) {
            elements.detailContent.addEventListener("submit", handleVerificationSubmit);
        }

        startClock();
        fetchFeedbackData();
    }

    async function fetchFeedbackData() {
        toggleLoading(true);
        try {
            const response = await fetch(API_URL);
            
            if (response.status === 401) {
                handleSessionTimeout();
                return;
            }

            if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);
            
            const data = await response.json();
            if (data.error) throw new Error(data.error);

            state.verificationData = data.verification_queue || [];
            state.allLinesData = data.all_lines || [];

            populateDropdownFilters();
            applyInitialUrlFilter();
            applyFilters();

        } catch (error) {
            console.error("Fetch Error:", error);
            showErrorState(error.message);
        } finally {
            toggleLoading(false);
        }
    }

    function renderTable(data) {
        if (!elements.tableBody) return;
        if (data.length === 0) {
            elements.tableBody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-slate-500 text-sm">No verification items found matching filters.</td></tr>`;
            return;
        }

        elements.tableBody.innerHTML = data.map((item, index) => {
            const rowClass = item.DefectID == state.selectedDefectId ? 'bg-blue-600/10 border-l-2 border-blue-500' : 'hover:bg-slate-800/50 cursor-pointer transition-colors border-l-2 border-transparent';
            const textClass = item.DefectID == state.selectedDefectId ? 'text-blue-100' : 'text-slate-400';
            
            let badgeClass = "bg-slate-800 text-slate-400 border-slate-700";
            if (item.FinalResult === 'False Fail') badgeClass = "bg-yellow-500/10 text-yellow-500 border-yellow-500/20";
            if (item.FinalResult === 'Defective') badgeClass = "bg-red-500/10 text-red-500 border-red-500/20";

            return `
                <tr data-id="${item.DefectID}" class="${rowClass} border-b border-slate-800/50 last:border-0">
                    <td class="px-4 py-3 text-center ${textClass}">${index + 1}</td>
                    <td class="px-4 py-3 ${textClass} font-mono text-xs">${formatTime(item.EndTime)}</td>
                    <td class="px-4 py-3 text-center ${textClass}">${item.LineName || "-"}</td>
                    <td class="px-4 py-3 font-medium ${item.is_critical ? 'text-red-400' : 'text-slate-300'}">${item.MachineDefectCode || "N/A"}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide border ${badgeClass}">${item.FinalResult}</span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderDetailView(defectId) {
        const item = state.verificationData.find(d => d.DefectID == defectId);
        
        if (!item) {
            elements.detailPlaceholder.classList.remove("hidden");
            elements.detailContent.classList.add("hidden");
            return;
        }

        const imageUrl = item.image_url || 'assets/images/placeholder.png';
        const criticalBanner = item.is_critical ? 
            `<div class="absolute top-3 right-3 bg-red-600 text-white text-[10px] font-bold px-3 py-1 rounded-full shadow-lg z-10 animate-pulse border border-red-400">CRITICAL DEFECT</div>` : '';

        const machineResultColor = (item.MachineDefectCode && item.MachineDefectCode !== 'Pass') 
            ? 'text-red-400 font-bold bg-red-500/10 px-2 py-0.5 rounded' 
            : 'text-green-400 font-bold';

        elements.detailContent.innerHTML = `
            <div class="flex flex-col h-full animate-fade-in-up">
                <div class="relative w-full h-72 bg-slate-950 rounded-xl overflow-hidden border border-slate-800 flex items-center justify-center group mb-6 shrink-0 shadow-lg">
                    <img src="${imageUrl}" alt="Defect Image" class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-125 cursor-zoom-in" onerror="this.onerror=null; this.src='assets/images/placeholder.png'">
                    ${criticalBanner}
                    <div class="absolute bottom-3 left-3 bg-black/60 backdrop-blur-sm px-2 py-1 rounded text-[10px] text-white font-mono border border-white/10">
                        ${item.ImageFileName ? item.ImageFileName.split(/[\\/]/).pop() : 'No Filename'}
                    </div>
                </div>

                <div class="bg-slate-950/50 border border-slate-800 rounded-xl p-4 mb-6 shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2">
                        <div class="space-y-1">
                            ${infoRow('Timestamp', formatDateTime(item.EndTime))}
                            ${infoRow('Line / Station', item.LineName)}
                            ${infoRow('Operator', item.OperatorName)}
                        </div>
                        <div class="space-y-1 border-t md:border-t-0 md:border-l border-slate-800 pt-2 md:pt-0 md:pl-4 mt-2 md:mt-0">
                            ${infoRow('Assembly', item.Assembly, true)}
                            ${infoRow('Part Number', item.PartNumber)}
                            ${infoRow('Machine Result', item.MachineDefectCode, false, machineResultColor)}
                        </div>
                    </div>
                </div>

                <div class="mt-auto border-t border-slate-800 pt-6 pb-2">
                     <form id="verification-form" class="space-y-5">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                <span class="w-1.5 h-4 bg-blue-500 rounded-full"></span> Analyst Decision
                            </h3>
                            <span class="text-[10px] text-slate-500 italic">Select one option below</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="decision" value="Confirm False Fail" class="peer sr-only" required>
                                <div class="p-3.5 rounded-xl border border-slate-700 bg-slate-800/40 hover:bg-slate-800 transition-all peer-checked:bg-yellow-500/10 peer-checked:border-yellow-500 peer-checked:ring-1 peer-checked:ring-yellow-500/50 flex items-center gap-3 h-full">
                                    <div class="w-4 h-4 shrink-0 rounded-full border border-slate-500 peer-checked:bg-yellow-500 peer-checked:border-yellow-500 flex items-center justify-center">
                                        <div class="w-1.5 h-1.5 bg-slate-900 rounded-full hidden peer-checked:block"></div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-200 peer-checked:text-yellow-400 leading-tight">False Fail</div>
                                        <div class="text-[10px] text-slate-500 group-hover:text-slate-400 mt-0.5">Unit OK, Machine Wrong</div>
                                    </div>
                                </div>
                            </label>

                            <label class="relative cursor-pointer group">
                                <input type="radio" name="decision" value="Confirm Defect" class="peer sr-only">
                                <div class="p-3.5 rounded-xl border border-slate-700 bg-slate-800/40 hover:bg-slate-800 transition-all peer-checked:bg-red-500/10 peer-checked:border-red-500 peer-checked:ring-1 peer-checked:ring-red-500/50 flex items-center gap-3 h-full">
                                    <div class="w-4 h-4 shrink-0 rounded-full border border-slate-500 peer-checked:bg-red-500 peer-checked:border-red-500 flex items-center justify-center">
                                        <div class="w-1.5 h-1.5 bg-slate-900 rounded-full hidden peer-checked:block"></div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-200 peer-checked:text-red-400 leading-tight">Confirm Defect</div>
                                        <div class="text-[10px] text-slate-500 group-hover:text-slate-400 mt-0.5">Unit NG, Repair Needed</div>
                                    </div>
                                </div>
                            </label>

                            <label class="relative cursor-pointer group">
                                <input type="radio" name="decision" value="Operator Error - Defect Missed" class="peer sr-only">
                                <div class="p-3.5 rounded-xl border border-slate-700 bg-slate-800/40 hover:bg-slate-800 transition-all peer-checked:bg-orange-500/10 peer-checked:border-orange-500 peer-checked:ring-1 peer-checked:ring-orange-500/50 flex items-center gap-3 h-full">
                                    <div class="w-4 h-4 shrink-0 rounded-full border border-slate-500 peer-checked:bg-orange-500 peer-checked:border-orange-500 flex items-center justify-center">
                                        <div class="w-1.5 h-1.5 bg-slate-900 rounded-full hidden peer-checked:block"></div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-200 peer-checked:text-orange-400 leading-tight">Op. Missed</div>
                                        <div class="text-[10px] text-slate-500 group-hover:text-slate-400 mt-0.5">Defect passed by Op</div>
                                    </div>
                                </div>
                            </label>

                            <label class="relative cursor-pointer group">
                                <input type="radio" name="decision" value="Operator Error - Wrong Classification" class="peer sr-only">
                                <div class="p-3.5 rounded-xl border border-slate-700 bg-slate-800/40 hover:bg-slate-800 transition-all peer-checked:bg-purple-500/10 peer-checked:border-purple-500 peer-checked:ring-1 peer-checked:ring-purple-500/50 flex items-center gap-3 h-full">
                                    <div class="w-4 h-4 shrink-0 rounded-full border border-slate-500 peer-checked:bg-purple-500 peer-checked:border-purple-500 flex items-center justify-center">
                                        <div class="w-1.5 h-1.5 bg-slate-900 rounded-full hidden peer-checked:block"></div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-200 peer-checked:text-purple-400 leading-tight">Wrong Class</div>
                                        <div class="text-[10px] text-slate-500 group-hover:text-slate-400 mt-0.5">Incorrect Defect Code</div>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="relative">
                            <input type="text" name="notes" placeholder="Add optional notes here..." class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all placeholder-slate-600 shadow-inner">
                        </div>

                        <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-blue-900/20 transform transition-all active:scale-[0.98] text-sm tracking-wide flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            SUBMIT DECISION
                        </button>
                    </form>
                </div>
            </div>`;

        elements.detailPlaceholder.classList.add("hidden");
        elements.detailContent.classList.remove("hidden");
    }

    function infoRow(label, value, isBold = false, colorClass = '') {
        const textStyle = isBold ? 'font-bold text-white' : 'font-medium text-slate-300';
        const finalColor = colorClass ? colorClass : textStyle;
        
        return `
        <div class="flex flex-row justify-between items-start py-2 border-b border-slate-800/50 last:border-0 hover:bg-slate-800/30 px-2 rounded transition-colors group">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mt-1 shrink-0 group-hover:text-slate-400 transition-colors w-24">${label}</span>
            <span class="text-sm ${finalColor} text-right break-words w-full pl-2 leading-snug">${value || "N/A"}</span>
        </div>`;
    }

    function applyFilters() {
        if (!state.datePicker) return;
        const lineVal = elements.filters.line.value;
        const defectVal = elements.filters.defect.value;
        const assemblyVal = elements.filters.assembly.value.toLowerCase();
        const dates = state.datePicker.selectedDates;

        const filtered = state.verificationData.filter(item => {
            const matchesLine = !lineVal || item.LineName === lineVal;
            const matchesDefect = !defectVal || item.MachineDefectCode === defectVal;
            const matchesAssembly = !assemblyVal || (item.Assembly && item.Assembly.toLowerCase().includes(assemblyVal));
            
            let matchesDate = true;
            if (dates.length === 2) {
                const d = new Date(item.EndTime);
                d.setHours(0,0,0,0);
                const start = new Date(dates[0]).setHours(0,0,0,0);
                const end = new Date(dates[1]).setHours(0,0,0,0);
                matchesDate = d >= start && d <= end;
            }

            return matchesLine && matchesDefect && matchesAssembly && matchesDate;
        });

        if (state.selectedDefectId && !filtered.find(i => i.DefectID == state.selectedDefectId)) {
            state.selectedDefectId = null;
            elements.detailPlaceholder.classList.remove("hidden");
            elements.detailContent.classList.add("hidden");
        }

        renderTable(filtered);
    }

    async function handleVerificationSubmit(e) {
        e.preventDefault();
        
        if (!state.selectedDefectId) {
            if(window.Notiflix) Notiflix.Notify.warning("Error: No defect selected.");
            return;
        }

        const formData = new FormData(e.target);
        const decision = formData.get("decision");
        const notes = formData.get("notes");
        const btn = e.target.querySelector('button[type="submit"]');

        if (!decision) {
            if(window.Notiflix) Notiflix.Notify.warning("Please select a decision.");
            return;
        }

        btn.disabled = true;
        btn.innerHTML = `<div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div> Processing...`;

        try {
            const payload = {
                defect_id: state.selectedDefectId,
                decision: decision,
                notes: notes
            };

            const response = await fetch(API_URL, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });

            if (response.status === 401) {
                handleSessionTimeout();
                return;
            }

            const result = await response.json();
            if (!result.success) throw new Error(result.error || "Submission failed");

            elements.detailContent.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-center p-8 animate-fade-in">
                    <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Verified!</h3>
                    <p class="text-slate-400 mb-6">Decision recorded successfully.</p>
                    <div class="px-4 py-2 bg-slate-800 rounded-lg border border-slate-700 text-sm text-slate-300">
                        ${decision}
                    </div>
                </div>`;
            
            if(window.Notiflix) Notiflix.Notify.success("Verification submitted successfully.");
            
            state.verificationData = state.verificationData.filter(i => i.DefectID != state.selectedDefectId);
            state.selectedDefectId = null;
            
            populateDropdownFilters(); 
            applyFilters();

        } catch (error) {
            console.error("Submit Error:", error);
            if(window.Notiflix) Notiflix.Notify.failure(error.message);
            btn.disabled = false;
            btn.textContent = "SUBMIT DECISION";
        }
    }

    function handleRowClick(e) {
        const row = e.target.closest("tr");
        if (!row || !row.dataset.id) return;

        state.selectedDefectId = row.dataset.id;
        
        const allRows = document.querySelectorAll("#feedback-table-body tr");
        allRows.forEach(r => {
            r.classList.remove("bg-blue-600/10", "border-blue-500", "border-l-2");
            r.classList.add("border-transparent", "hover:bg-slate-800/50");
            r.querySelector('td:nth-child(1)').classList.remove('text-blue-100');
            r.querySelector('td:nth-child(1)').classList.add('text-slate-400');
        });
        
        row.classList.remove("border-transparent", "hover:bg-slate-800/50");
        row.classList.add("bg-blue-600/10", "border-l-2", "border-blue-500");
        row.querySelector('td:nth-child(1)').classList.remove('text-slate-400');
        row.querySelector('td:nth-child(1)').classList.add('text-blue-100');
        
        renderDetailView(state.selectedDefectId);
    }

    function populateDropdownFilters() {
        const currLine = elements.filters.line.value;
        const currDefect = elements.filters.defect.value;

        const lineOpts = state.allLinesData.map(l => `<option value="${l.LineName}">${l.LineName}</option>`).join('');
        elements.filters.line.innerHTML = `<option value="">All Lines</option>${lineOpts}`;
        elements.filters.line.value = currLine;

        const uniqueDefects = [...new Set(state.verificationData.map(i => i.MachineDefectCode).filter(Boolean))].sort();
        const defectOpts = uniqueDefects.map(d => `<option value="${d}">${d}</option>`).join('');
        elements.filters.defect.innerHTML = `<option value="">All Defects</option>${defectOpts}`;
        elements.filters.defect.value = currDefect;
    }

    function applyInitialUrlFilter() {
        const params = new URLSearchParams(window.location.search);
        const lineId = params.get('line');
        if (lineId) {
            const line = state.allLinesData.find(l => l.LineID == lineId);
            if (line) elements.filters.line.value = line.LineName;
        }
    }

    function toggleLoading(show) {
        if(elements.loading) elements.loading.style.display = show ? "flex" : "none";
        if (show && elements.tableBody) {
            elements.tableBody.innerHTML = "";
            elements.detailPlaceholder.classList.remove("hidden");
            elements.detailContent.classList.add("hidden");
        }
    }

    function showErrorState(msg) {
        if(elements.loading) {
            elements.loading.innerHTML = `<div class="text-red-400 font-bold flex flex-col items-center"><svg class="w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>${msg}</div>`;
            elements.loading.style.display = "flex";
        }
    }

    const formatTime = (iso) => new Date(iso).toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
    const formatDateTime = (iso) => new Date(iso).toLocaleString("id-ID", { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });

    // REVISI CLOCK
    function startClock() {
        const update = () => {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
            
            const days = ['MIN', 'SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB'];
            const months = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
            
            const dayName = days[now.getDay()];
            const dayNum = now.getDate();
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();
            
            const dateStr = `${dayName}, ${dayNum} ${monthName} ${year}`;
            
            // Perbaikan Selektor: Pastikan mencari ID di document, bukan jQuery selector yang mungkin belum ready
            const clockEl = document.getElementById('clock');
            const dateEl = document.getElementById('date');
            
            if (clockEl) clockEl.textContent = timeStr;
            if (dateEl) dateEl.textContent = dateStr;
        };
        update();
        setInterval(update, 1000);
    }

    init();
});