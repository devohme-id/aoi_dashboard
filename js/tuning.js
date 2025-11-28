/**
 * tuning.js - Tuning Cycle Management & Preview
 * Features: Searchable Dropdowns, Live Database Preview, Dynamic Charts, Tuning History, Real-time Clock
 */

// === CUSTOM SELECT COMPONENT (Searchable) ===
class SearchableSelect {
    constructor(selector, placeholder, onChangeCallback = null) {
        this.$originalSelect = $(selector);
        this.placeholder = placeholder;
        this.onChangeCallback = onChangeCallback;
        this.isOpen = false;
        
        // Hide original select
        this.$originalSelect.addClass('hidden');
        
        // Render Custom UI
        this.renderUI();
        this.bindEvents();
        this.syncFromOriginal(); 
    }

    renderUI() {
        this.$wrapper = $('<div class="relative w-full"></div>');
        
        this.$trigger = $(`
            <div class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 flex justify-between items-center cursor-pointer hover:border-indigo-500 transition-all group select-none">
                <span class="text-slate-400 text-sm font-medium truncate">${this.placeholder}</span>
                <svg class="w-4 h-4 text-slate-500 group-hover:text-indigo-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        `);

        this.$dropdown = $(`
            <div class="absolute top-full left-0 w-full mt-2 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl z-50 hidden overflow-hidden ring-1 ring-white/5 origin-top scale-95 opacity-0 transition-all duration-200">
                <div class="p-2 border-b border-slate-800 sticky top-0 bg-slate-900 z-10">
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-white focus:ring-1 focus:ring-indigo-500 outline-none placeholder-slate-600" placeholder="Search...">
                    </div>
                </div>
                <div class="options-list max-h-60 overflow-y-auto custom-scrollbar p-1"></div>
            </div>
        `);

        this.$wrapper.insertAfter(this.$originalSelect);
        this.$wrapper.append(this.$trigger).append(this.$dropdown);
        
        this.$optionsList = this.$dropdown.find('.options-list');
        this.$searchInput = this.$dropdown.find('input');
    }

    bindEvents() {
        this.$trigger.on('click', (e) => {
            if (this.$originalSelect.prop('disabled')) return;
            e.stopPropagation();
            this.toggle();
        });

        this.$searchInput.on('input', (e) => {
            const term = e.target.value.toLowerCase();
            this.$optionsList.find('.custom-option').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(term));
            });
        });

        this.$optionsList.on('click', '.custom-option', (e) => {
            const $opt = $(e.currentTarget);
            const value = $opt.data('value');
            this.$originalSelect.val(value).trigger('change');
            this.updateTriggerText($opt.text());
            this.close();
            if (this.onChangeCallback) this.onChangeCallback(value);
        });

        $(document).on('click', (e) => {
            if (!this.$wrapper.is(e.target) && this.$wrapper.has(e.target).length === 0) {
                this.close();
            }
        });
    }

    syncFromOriginal() {
        this.$optionsList.empty();
        const options = this.$originalSelect.find('option');
        
        if (options.length <= 1) { 
             this.$optionsList.append(`<div class="p-4 text-center text-xs text-slate-500 italic">No data available</div>`);
        } else {
            options.each((i, el) => {
                if ($(el).val() === "") return;
                const isSelected = $(el).is(':selected');
                const activeClass = isSelected ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800';
                this.$optionsList.append(`<div class="custom-option px-3 py-2 rounded-lg cursor-pointer text-sm mb-1 ${activeClass} transition-colors" data-value="${$(el).val()}">${$(el).text()}</div>`);
                if (isSelected) this.updateTriggerText($(el).text());
            });
        }

        if (this.$originalSelect.prop('disabled')) {
            this.$wrapper.addClass('opacity-50 cursor-not-allowed');
            this.$trigger.addClass('cursor-not-allowed');
        } else {
            this.$wrapper.removeClass('opacity-50 cursor-not-allowed');
            this.$trigger.removeClass('cursor-not-allowed');
        }
    }

    toggle() { this.isOpen ? this.close() : this.open(); }

    open() {
        this.isOpen = true;
        this.$dropdown.removeClass('hidden');
        setTimeout(() => {
            this.$dropdown.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            this.$trigger.find('svg').addClass('rotate-180');
            this.$searchInput.focus();
        }, 10);
    }

    close() {
        this.isOpen = false;
        this.$dropdown.removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        this.$trigger.find('svg').removeClass('rotate-180');
        setTimeout(() => this.$dropdown.addClass('hidden'), 200);
        this.$searchInput.val('');
        this.$optionsList.find('.custom-option').show();
    }

    updateTriggerText(text) {
        this.$trigger.find('span').text(text).removeClass('text-slate-400').addClass('text-white');
    }

    setLoading(isLoading) {
        if (isLoading) {
            this.$trigger.find('span').html('<div class="flex items-center gap-2"><div class="w-4 h-4 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div> Loading...</div>');
            this.$originalSelect.prop('disabled', true);
            this.$wrapper.addClass('opacity-75');
        } else {
            this.$originalSelect.prop('disabled', false);
            this.$wrapper.removeClass('opacity-75');
        }
    }

    reset() {
        this.$originalSelect.val('');
        this.$trigger.find('span').text(this.placeholder).removeClass('text-white').addClass('text-slate-400');
        this.syncFromOriginal();
    }
}

// === MAIN LOGIC ===
$(document).ready(function () {
    const elements = {
        line: $('#line_id'),
        assembly: $('#assembly_name'),
        form: $('#tuning_form'),
        status: $('#status_message'),
        submitBtn: $('#submit_button'),
        preview: {
            container: $('#preview_content'),
            placeholder: $('#preview_placeholder'),
            badge: $('#preview_status_badge')
        }
    };

    let previewChart = null; 
    let currentLineData = null;
    let currentHistoryData = [];

    // Initialize Custom Dropdowns
    const lineSelectUI = new SearchableSelect('#line_id', '-- Select Production Line --', (lineId) => handleLineChange(lineId));
    const assemblySelectUI = new SearchableSelect('#assembly_name', '-- Choose Line First --', (name) => handleAssemblyChange(name));

    // Helper: Auth Check
    function handleAuthError(xhr) {
        if (xhr.status === 401) {
            const currentUrl = encodeURIComponent(window.location.pathname);
            window.location.href = `index.php?trigger_login=true&redirect=${currentUrl}&login_error=Session expired.`;
            return true;
        }
        return false;
    }

    // 1. Line Change Logic
    function handleLineChange(lineId) {
        assemblySelectUI.reset();
        assemblySelectUI.setLoading(true);
        currentHistoryData = [];
        
        elements.preview.container.addClass('hidden');
        elements.preview.placeholder.removeClass('hidden').html('<div class="animate-pulse flex flex-col items-center"><div class="w-10 h-10 border-4 border-slate-700 border-t-indigo-500 rounded-full animate-spin mb-3"></div><span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Loading Data...</span></div>');
        elements.preview.badge.text('Connecting...').attr('class', 'px-2 py-0.5 bg-slate-800 text-slate-500 text-[10px] rounded uppercase font-bold');

        if (!lineId) {
            assemblySelectUI.setLoading(false);
            resetPreview();
            return;
        }

        // A. Fetch Assemblies
        $.ajax({
            url: 'api/get_assemblies.php',
            type: 'POST',
            data: { line_id: lineId },
            dataType: 'json',
            success: function (response) {
                if (response.error) {
                    elements.assembly.html(`<option value="">Error: ${response.error}</option>`);
                } else {
                    let options = '<option value="">-- Select Assembly --</option>';
                    response.all_assemblies.forEach(function (assembly) {
                        const isCurrent = assembly === response.current_assembly;
                        const displayText = isCurrent ? `${assembly} (RUNNING)` : assembly;
                        const selectedAttr = isCurrent ? 'selected' : '';
                        options += `<option value="${assembly}" ${selectedAttr}>${displayText}</option>`;
                    });

                    elements.assembly.html(options);
                    assemblySelectUI.setLoading(false);
                    assemblySelectUI.syncFromOriginal();
                    
                    if(elements.assembly.val()) {
                        const selectedText = elements.assembly.find('option:selected').text();
                        assemblySelectUI.updateTriggerText(selectedText);
                        handleAssemblyChange(elements.assembly.val());
                    }
                }
            },
            error: function (xhr) {
                assemblySelectUI.setLoading(false);
                if(!handleAuthError(xhr)) elements.assembly.html('<option value="">Failed to load</option>');
            }
        });

        // B. Fetch Dashboard Data
        fetchPreviewData(lineId);
    }

    // 2. Assembly Change Logic
    function handleAssemblyChange(selectedAssembly) {
        // Visual update on preview card
        if (selectedAssembly && currentLineData) {
            const assemblyEl = $(`#detail_assembly_${elements.line.val()}`);
            const labelEl = assemblyEl.prev('span');
            
            if (assemblyEl.length) {
                assemblyEl.text(selectedAssembly)
                    .removeClass('text-white')
                    .addClass('text-indigo-400 animate-pulse');
                
                labelEl.text('TARGET ASSEMBLY').addClass('text-indigo-500');
                setTimeout(() => assemblyEl.removeClass('animate-pulse'), 1000);
            }
        }

        // Fetch History
        if (selectedAssembly && elements.line.val()) {
            fetchTuningHistory(elements.line.val(), selectedAssembly);
        }
    }

    // 3. Fetch Preview Data
    async function fetchPreviewData(lineId) {
        try {
            const response = await fetch(`api/get_dashboard_data.php?t=${Date.now()}`);
            if (!response.ok) throw new Error("Network response was not ok");
            const data = await response.json();
            
            if (data.lines && data.lines[`line_${lineId}`]) {
                currentLineData = data.lines[`line_${lineId}`];
                renderPreviewPanel(lineId, currentLineData, []);
                
                if(elements.assembly.val()) {
                    handleAssemblyChange(elements.assembly.val());
                }
            } else {
                showPreviewError("No active data for Line " + lineId);
            }
        } catch (error) {
            console.error("Preview Error:", error);
            showPreviewError("Failed to load preview.");
        }
    }

    // 4. Fetch Tuning History
    function fetchTuningHistory(lineId, assemblyName) {
        const params = new URLSearchParams();
        params.append('line_filter', lineId);
        params.append('search[value]', assemblyName);
        params.append('start', 0);
        params.append('length', 50);

        fetch('api/get_report_data.php', {
            method: 'POST',
            body: params
        })
        .then(res => res.json())
        .then(data => {
            if (data.data) {
                const uniqueCycles = new Map();
                data.data.forEach(row => {
                    const ver = row.TuningCycleID;
                    if (!uniqueCycles.has(ver)) {
                        uniqueCycles.set(ver, {
                            version: ver,
                            date: row.EndTime,
                            user: row.DebuggerFullName,
                            notes: row.Notes
                        });
                    }
                });
                currentHistoryData = Array.from(uniqueCycles.values()).sort((a, b) => b.version - a.version);
                if(currentLineData) renderPreviewPanel(lineId, currentLineData, currentHistoryData);
            }
        })
        .catch(err => console.error("History fetch error:", err));
    }

    function renderPreviewPanel(lineId, data, history = []) {
        const html = createPanelHTML(lineId, data, history);
        elements.preview.placeholder.addClass('hidden');
        elements.preview.container.removeClass('hidden').html(html);
        
        const status = data.status || 'INACTIVE';
        let badgeClass = 'bg-slate-800 text-slate-500 border-slate-700';
        let badgeText = status;

        if (status === 'INACTIVE') {
            badgeText = 'LAST LOG';
            badgeClass = 'bg-slate-700 text-slate-300 border-slate-600';
        } else if (status === 'Pass') {
            badgeText = 'RUNNING (PASS)';
            badgeClass = 'bg-green-500/10 text-green-400 border-green-500/20';
        } else if (status === 'Defective') {
            badgeText = 'RUNNING (FAIL)';
            badgeClass = 'bg-red-500/10 text-red-400 border-red-500/20';
        } else {
            badgeClass = 'bg-blue-500/10 text-blue-400 border-blue-500/20';
        }
        
        elements.preview.badge.text(badgeText).attr('class', `px-2 py-0.5 border text-[10px] rounded uppercase font-bold ${badgeClass}`);
        
        const chartData = data.comparison_data || { before: { pass_rate: 0 }, current: { pass_rate: 0 } };
        renderPreviewChart(lineId, chartData);
    }

    function renderPreviewChart(lineId, data) {
        const ctx = document.getElementById(`previewChart_${lineId}`);
        if (!ctx) return;
        if (previewChart) previewChart.destroy();

        const beforeVal = parseFloat(data.before.pass_rate) || 0;
        const currentVal = parseFloat(data.current.pass_rate) || 0;

        previewChart = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Before', 'Current'],
                datasets: [{
                    label: 'Pass Rate',
                    data: [beforeVal, currentVal],
                    backgroundColor: ['#475569', '#6366f1'],
                    borderRadius: 3,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 20 } },
                plugins: { 
                    legend: { display: false },
                    datalabels: { 
                        display: true, color: '#fff', anchor: 'end', align: 'top', 
                        font: { size: 10, weight: 'bold' },
                        formatter: v => v + '%' 
                    }
                },
                scales: {
                    x: { display: true, ticks: { color: '#94a3b8', font: { size: 10 } }, grid: { display: false } },
                    y: { display: false, max: 115 }
                }
            }
        });
    }

    // HTML Template Generator
    function createPanelHTML(num, data, history = []) {
        const isCritical = data.is_critical_alert;
        const isActive = data.status !== 'INACTIVE';
        const lastDefect = data.details.machine_defect || '-';
        const defectColor = (lastDefect !== '-' && lastDefect !== 'Pass' && lastDefect !== 'N/A') ? 'text-red-400' : 'text-slate-300';
        
        let imageHtml = '';
        if (data.image_url) {
            imageHtml = `<img src="${data.image_url}" alt="Defect" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">`;
        } else {
            imageHtml = `<div class="flex flex-col items-center justify-center text-slate-600"><svg class="w-10 h-10 mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span class="text-[10px] font-bold tracking-widest">NO IMAGE</span></div>`;
        }

        const kpiColor = (val, good) => `text-lg font-bold leading-none ${good === true ? 'text-green-400' : (good === false ? 'text-red-400' : 'text-white')}`;
        const kpi = data.kpi;

        // History UI
        let historyHtml = '';
        if (history.length > 0) {
            history.forEach(item => {
                const dateStr = new Date(item.date).toLocaleDateString('id-ID', { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'});
                historyHtml += `
                <div class="relative pl-4 border-l-2 border-slate-800 pb-4 last:pb-0 last:border-0">
                    <div class="absolute -left-[5px] top-1 w-2.5 h-2.5 rounded-full bg-slate-700 border border-slate-900"></div>
                    <div class="flex justify-between items-start">
                        <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider">Cycle v${item.version}</span>
                        <span class="text-[9px] text-slate-500 font-mono">${dateStr}</span>
                    </div>
                    <p class="text-xs text-slate-300 mt-1 leading-snug">${item.notes || 'No notes.'}</p>
                    <div class="text-[9px] text-slate-500 mt-1 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> 
                        ${item.user || 'Unknown'}
                    </div>
                </div>`;
            });
        } else {
            historyHtml = `<div class="text-center py-6 text-slate-500 text-xs italic">No previous tuning logs found for this assembly.</div>`;
        }

        return `
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-3 flex flex-col gap-3 shadow-2xl ring-1 ring-white/5 animate-fade-in-up h-full">
            <div class="flex justify-between items-center bg-slate-950/40 p-2 rounded-lg border border-slate-800/50">
                <h2 class="text-sm font-bold text-white flex items-center gap-2"><span class="w-1.5 h-4 bg-indigo-500 rounded-full"></span> PREVIEW LINE ${num}</h2>
                <span class="text-[10px] text-slate-500 font-mono">LIVE DATA</span>
            </div>

            <div class="grid grid-cols-5 gap-3">
                <div class="col-span-2 flex flex-col gap-1 overflow-hidden">
                    <div class="flex flex-col border-b border-slate-800/80 pb-1 mb-0.5 shrink-0">
                        <span class="text-[10px] text-slate-500 uppercase tracking-wider font-bold">Assembly</span>
                        <div id="detail_assembly_${num}" class="font-bold text-white text-xs leading-tight break-words">${data.kpi.assembly || 'N/A'}</div>
                    </div>
                    <div class="space-y-1 pr-1">
                        ${detailRow('Time', data.details.time)}
                        <div class="flex flex-col bg-red-500/5 border-l-2 border-red-500/50 pl-1.5 py-0.5 my-0.5 rounded-r shrink-0">
                            <span class="text-[10px] text-red-400/70 uppercase tracking-wider font-bold">Defect</span>
                            <span class="font-bold ${defectColor} text-xs leading-tight break-words">${lastDefect}</span>
                        </div>
                        ${detailRow('Insp', data.details.inspection_result)}
                    </div>
                </div>
                <div class="col-span-3">
                    <div class="relative w-full bg-slate-950 rounded-lg border border-slate-800 flex items-center justify-center overflow-hidden group shadow-inner h-[120px]">
                        ${imageHtml}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-1 bg-slate-950/40 p-1.5 rounded-lg border border-slate-800/50">
                <div class="flex flex-col items-center justify-center"><div class="${kpiColor(kpi.pass_rate, kpi.pass_rate >= 90)}">${kpi.pass_rate}%</div><div class="text-[9px] text-slate-500 uppercase font-bold mt-0.5">Rate</div></div>
                <div class="flex flex-col items-center justify-center"><div class="${kpiColor(kpi.ppm, kpi.ppm <= 2100)}">${kpi.ppm}</div><div class="text-[9px] text-slate-500 uppercase font-bold mt-0.5">PPM</div></div>
                <div class="flex flex-col items-center justify-center"><div class="text-lg font-bold text-green-500 leading-none">${kpi.total_pass}</div><div class="text-[9px] text-slate-500 uppercase font-bold mt-0.5">Pass</div></div>
                <div class="flex flex-col items-center justify-center"><div class="text-lg font-bold text-yellow-500 leading-none">${kpi.total_false_call}</div><div class="text-[9px] text-slate-500 uppercase font-bold mt-0.5">FC</div></div>
            </div>

            <div class="h-20 w-full shrink-0"><canvas id="previewChart_${num}"></canvas></div>

            <div class="border-t border-slate-800 pt-3 mt-1">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Recent Tuning Logs
                </h3>
                <div class="max-h-40 overflow-y-auto custom-scrollbar pr-2 space-y-3">
                    ${historyHtml}
                </div>
            </div>
        </div>`;
    }

    function detailRow(label, value) {
        return `<div class="flex justify-between items-start text-xs border-b border-slate-800/50 pb-0.5 last:border-0 shrink-0"><span class="text-slate-500 uppercase tracking-tight text-[9px] w-8 shrink-0 mt-0.5">${label}</span><span class="font-bold text-slate-300 text-right ml-1 break-all text-[10px]">${value || '-'}</span></div>`;
    }

    function showPreviewError(msg) {
        elements.preview.placeholder.removeClass('hidden').html(`<div class="text-red-400 text-sm font-bold flex flex-col items-center"><svg class="w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>${msg}</div>`);
        elements.preview.container.addClass('hidden');
    }

    function resetPreview() {
        currentLineData = null;
        elements.preview.container.addClass('hidden');
        elements.preview.placeholder.removeClass('hidden');
        elements.preview.badge.text('No Selection').attr('class', 'px-2 py-0.5 bg-slate-800 text-slate-500 text-[10px] rounded uppercase font-bold');
    }

    // 5. Submit Form
    elements.form.on('submit', function (e) {
        e.preventDefault();
        elements.status.text('').removeClass('text-green-400 text-red-400');
        elements.submitBtn.prop('disabled', true).html('<div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div> Processing...');

        const formData = {
            line_id: elements.line.val(),
            assembly_name: elements.assembly.val(),
            notes: $('#notes').val()
        };

        $.ajax({
            url: 'api/start_new_cycle.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    elements.status.text(response.message).addClass('text-green-400');
                    elements.form[0].reset();
                    lineSelectUI.reset();
                    assemblySelectUI.reset();
                    resetPreview();
                } else {
                    elements.status.text(`Error: ${response.message}`).addClass('text-red-400');
                }
            },
            error: function (xhr) {
                if(!handleAuthError(xhr)) {
                    elements.status.text('Error: Server connection failed.').addClass('text-red-400');
                }
            },
            complete: function () {
                elements.submitBtn.prop('disabled', false).html('START NEW CYCLE');
            }
        });
    });

    // --- CLOCK LOGIC (FIXED) ---
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
        // Format Tanggal: "JUM, 29 NOV 2025" (Singkatan 3 huruf, Uppercase)
        const days = ['MIN', 'SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB'];
        const months = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
        
        const dayName = days[now.getDay()];
        const dayNum = now.getDate();
        const monthName = months[now.getMonth()];
        const year = now.getFullYear();
        
        const dateStr = `${dayName}, ${dayNum} ${monthName} ${year}`;
        
        // Target elemen berdasarkan ID di navbar.php
        $('#clock').text(timeStr);
        $('#date').text(dateStr);
    }
    
    // Start Clock immediately
    updateClock();
    setInterval(updateClock, 1000);
});