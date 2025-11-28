/**
 * tuning.js - Tuning Cycle Management & Preview
 * Revised: Custom Searchable Dropdown for Better UX
 */

// === CUSTOM SELECT COMPONENT ===
class SearchableSelect {
    constructor(selector, placeholder, onChangeCallback = null) {
        this.$originalSelect = $(selector);
        this.placeholder = placeholder;
        this.onChangeCallback = onChangeCallback;
        this.isOpen = false;
        
        // Hide original
        this.$originalSelect.addClass('hidden');
        
        // Create UI Structure
        this.renderUI();
        this.bindEvents();
        this.syncFromOriginal(); // Initial sync
    }

    renderUI() {
        // Container
        this.$wrapper = $('<div class="relative w-full"></div>');
        
        // Trigger Button
        this.$trigger = $(`
            <div class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 flex justify-between items-center cursor-pointer hover:border-indigo-500 transition-all group select-none">
                <span class="text-slate-400 text-sm font-medium truncate">${this.placeholder}</span>
                <svg class="w-4 h-4 text-slate-500 group-hover:text-indigo-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        `);

        // Dropdown Menu
        this.$dropdown = $(`
            <div class="absolute top-full left-0 w-full mt-2 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl z-50 hidden overflow-hidden ring-1 ring-white/5 origin-top scale-95 opacity-0 transition-all duration-200">
                <div class="p-2 border-b border-slate-800 sticky top-0 bg-slate-900 z-10">
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-white focus:ring-1 focus:ring-indigo-500 outline-none placeholder-slate-600" placeholder="Search...">
                    </div>
                </div>
                <div class="options-list max-h-60 overflow-y-auto custom-scrollbar p-1">
                    <!-- Options injected here -->
                </div>
            </div>
        `);

        this.$wrapper.insertAfter(this.$originalSelect);
        this.$wrapper.append(this.$trigger).append(this.$dropdown);
        
        this.$optionsList = this.$dropdown.find('.options-list');
        this.$searchInput = this.$dropdown.find('input');
    }

    bindEvents() {
        // Toggle Dropdown
        this.$trigger.on('click', (e) => {
            if (this.$originalSelect.prop('disabled')) return;
            e.stopPropagation();
            this.toggle();
        });

        // Search Filter
        this.$searchInput.on('input', (e) => {
            const term = e.target.value.toLowerCase();
            this.$optionsList.find('.custom-option').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(term));
            });
        });

        // Option Click
        this.$optionsList.on('click', '.custom-option', (e) => {
            const $opt = $(e.currentTarget);
            const value = $opt.data('value');
            
            // Update Original Select
            this.$originalSelect.val(value).trigger('change');
            
            // Update UI
            this.updateTriggerText($opt.text());
            this.close();
            
            // Callback
            if (this.onChangeCallback) this.onChangeCallback(value);
        });

        // Close when clicking outside
        $(document).on('click', (e) => {
            if (!this.$wrapper.is(e.target) && this.$wrapper.has(e.target).length === 0) {
                this.close();
            }
        });
    }

    syncFromOriginal() {
        // Re-populate options from original select
        this.$optionsList.empty();
        const options = this.$originalSelect.find('option');
        
        if (options.length <= 1) { // Only placeholder
             this.$optionsList.append(`<div class="p-4 text-center text-xs text-slate-500 italic">No data available</div>`);
        } else {
            options.each((i, el) => {
                if ($(el).val() === "") return; // Skip placeholder
                const isSelected = $(el).is(':selected');
                const activeClass = isSelected ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800';
                
                this.$optionsList.append(`
                    <div class="custom-option px-3 py-2 rounded-lg cursor-pointer text-sm mb-1 ${activeClass} transition-colors" data-value="${$(el).val()}">
                        ${$(el).text()}
                    </div>
                `);
                
                if (isSelected) this.updateTriggerText($(el).text());
            });
        }

        // Handle Disabled State
        if (this.$originalSelect.prop('disabled')) {
            this.$wrapper.addClass('opacity-50 cursor-not-allowed');
            this.$trigger.addClass('cursor-not-allowed');
        } else {
            this.$wrapper.removeClass('opacity-50 cursor-not-allowed');
            this.$trigger.removeClass('cursor-not-allowed');
        }
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    open() {
        this.isOpen = true;
        this.$dropdown.removeClass('hidden');
        // Animation
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
        this.$searchInput.val(''); // Clear search
        this.$optionsList.find('.custom-option').show(); // Reset filter
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

    // --- INITIALIZE CUSTOM SELECTS ---
    // Line Select (Trigger Logic utama)
    const lineSelectUI = new SearchableSelect('#line_id', '-- Select Production Line --', (lineId) => {
        handleLineChange(lineId);
    });

    // Assembly Select (Dependent)
    const assemblySelectUI = new SearchableSelect('#assembly_name', '-- Choose Line First --', (assemblyName) => {
        handleAssemblyChange(assemblyName);
    });


    // Helper: Auth Check
    function handleAuthError(xhr) {
        if (xhr.status === 401) {
            const currentUrl = encodeURIComponent(window.location.pathname);
            window.location.href = `index.php?trigger_login=true&redirect=${currentUrl}&login_error=Session expired.`;
            return true;
        }
        return false;
    }

    // 1. Logic saat Line Berubah
    function handleLineChange(lineId) {
        // Reset States
        assemblySelectUI.reset();
        assemblySelectUI.setLoading(true);
        
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
                    const currentAssembly = response.current_assembly;

                    response.all_assemblies.forEach(function (assembly) {
                        const isCurrent = assembly === currentAssembly;
                        // Add marker to text for visibility in custom select
                        const displayText = isCurrent ? `${assembly} (RUNNING)` : assembly;
                        const selectedAttr = isCurrent ? 'selected' : '';
                        options += `<option value="${assembly}" ${selectedAttr}>${displayText}</option>`;
                    });

                    elements.assembly.html(options);
                    assemblySelectUI.setLoading(false);
                    assemblySelectUI.syncFromOriginal(); // Refresh custom UI options
                    
                    // Trigger manual update jika ada yang terpilih otomatis (Running assembly)
                    if(elements.assembly.val()) {
                        // Secara manual update text trigger UI
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

    // 2. Logic saat Assembly Berubah
    function handleAssemblyChange(selectedAssembly) {
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
    }

    // Function: Fetch Preview Data
    async function fetchPreviewData(lineId) {
        try {
            const response = await fetch(`api/get_dashboard_data.php?t=${Date.now()}`);
            if (!response.ok) throw new Error("Network response was not ok");
            const data = await response.json();
            
            if (data.lines && data.lines[`line_${lineId}`]) {
                currentLineData = data.lines[`line_${lineId}`];
                renderPreviewPanel(lineId, currentLineData);
                
                // Re-apply target visual if assembly selected
                if(elements.assembly.val()) handleAssemblyChange(elements.assembly.val());
            } else {
                showPreviewError("No active data for Line " + lineId);
            }
        } catch (error) {
            console.error("Preview Error:", error);
            showPreviewError("Failed to load preview.");
        }
    }

    // Function: Render Preview Panel
    function renderPreviewPanel(lineId, data) {
        const html = createPanelHTML(lineId, data);
        
        elements.preview.placeholder.addClass('hidden');
        elements.preview.container.removeClass('hidden').html(html);
        
        // Update Badge Status
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

        renderPreviewChart(lineId, data.comparison_data);
    }

    function resetPreview() {
        currentLineData = null;
        elements.preview.container.addClass('hidden');
        elements.preview.placeholder.removeClass('hidden').html(`
            <div class="w-16 h-16 bg-slate-800/50 rounded-2xl flex items-center justify-center mb-4 ring-1 ring-white/5 shadow-inner">
                <svg class="w-8 h-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h4 class="text-slate-300 font-bold">Ready to Start</h4>
            <p class="text-slate-500 text-xs mt-2 max-w-[200px]">Select a production line to load the current program context.</p>
        `);
        elements.preview.badge.text('Waiting...').attr('class', 'px-2 py-0.5 bg-slate-800 text-slate-500 text-[10px] rounded uppercase font-bold border border-slate-700');
    }

    function showPreviewError(msg) {
        elements.preview.placeholder.removeClass('hidden').html(`<div class="text-red-400 text-sm font-bold flex flex-col items-center bg-red-500/10 p-4 rounded-lg border border-red-500/20"><svg class="w-8 h-8 mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>${msg}</div>`);
        elements.preview.container.addClass('hidden');
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
                    borderRadius: 4,
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

    // Template HTML (Sama dengan main.js tapi disesuaikan untuk konteks preview)
    function createPanelHTML(num, data) {
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

        return `
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col gap-4 shadow-2xl ring-1 ring-white/5 animate-fade-in-up">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Assembly</span>
                    <div id="detail_assembly_${num}" class="font-bold text-white text-sm leading-tight break-words transition-colors duration-300">${data.kpi.assembly || 'N/A'}</div>
                </div>
                <div class="space-y-1 text-right">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Last Defect</span>
                    <div class="font-bold ${defectColor} text-sm leading-tight break-words">${lastDefect}</div>
                </div>
            </div>
            <div class="relative h-40 bg-slate-950 rounded-lg border border-slate-800 flex items-center justify-center overflow-hidden group">
                ${imageHtml}
            </div>
            <div class="grid grid-cols-4 gap-2 bg-slate-950/50 p-3 rounded-lg border border-slate-800">
                <div class="flex flex-col items-center justify-center"><div class="${kpiColor(kpi.pass_rate, kpi.pass_rate >= 90)}">${kpi.pass_rate}%</div><div class="text-[9px] text-slate-500 uppercase font-bold mt-1">Rate</div></div>
                <div class="flex flex-col items-center justify-center"><div class="${kpiColor(kpi.ppm, kpi.ppm <= 2100)}">${kpi.ppm}</div><div class="text-[9px] text-slate-500 uppercase font-bold mt-1">PPM</div></div>
                <div class="flex flex-col items-center justify-center"><div class="text-lg font-bold text-green-500 leading-none">${kpi.total_pass}</div><div class="text-[9px] text-slate-500 uppercase font-bold mt-1">Pass</div></div>
                <div class="flex flex-col items-center justify-center"><div class="text-lg font-bold text-yellow-500 leading-none">${kpi.total_false_call}</div><div class="text-[9px] text-slate-500 uppercase font-bold mt-1">FC</div></div>
            </div>
            <div class="h-24 w-full"><canvas id="previewChart_${num}"></canvas></div>
        </div>`;
    }

    // 3. Submit New Cycle
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
                    // Reset dropdowns
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

    // Update Clock
    function updateClock() {
        const now = new Date();
        $('#clock').text(now.toLocaleTimeString('id-ID', { hour12: false }));
        $('#date').text(now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
    }
    updateClock();
    setInterval(updateClock, 1000);
});