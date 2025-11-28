/**
 * main.js - Dashboard Core Logic
 * Handles real-time data fetching, chart updates, and sound alerts.
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
    // 1. Setup Chart.js
    if (typeof Chart !== 'undefined' && ChartDataLabels) {
        Chart.register(ChartDataLabels);
    }

    // 2. Setup Audio
    state.alertAudio = document.getElementById('alert-sound');
    const soundToggleBtn = document.getElementById('sound-toggle-btn');

    if (state.alertAudio) {
        state.alertAudio.addEventListener('ended', () => {
            if (state.isSoundLooping) setTimeout(playAlertSound, CONFIG.SOUND_DELAY);
        });
    }

    if (soundToggleBtn) {
        soundToggleBtn.addEventListener('click', () => {
            toggleSound(soundToggleBtn);
        });
    }

    // 3. Setup Panel Grid Layout (Initial Render)
    const panelArea = document.getElementById('panel-area');
    if (panelArea) {
        let content = '';
        for (let i = 1; i <= 6; i++) {
            content += createPanelHTML(i);
        }
        panelArea.innerHTML = content;

        // Event Delegation untuk klik panel -> navigasi ke feedback
        panelArea.addEventListener('click', (event) => {
            const container = event.target.closest('.image-container');
            if (container && container.dataset.line) {
                // Redirect ke feedback.php dengan parameter line
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
        // Jangan redirect di sini karena Dashboard bersifat publik
    }
}

function updateDashboardUI(linesData) {
    let isAnyCriticalAlertActive = false;

    for (let i = 1; i <= 6; i++) {
        const lineKey = `line_${i}`;
        const lineData = linesData[lineKey] || createDefaultLineData();
        
        updateSinglePanel(i, lineData);
        
        if (lineData.is_critical_alert) {
            isAnyCriticalAlertActive = true;
        }
    }

    manageAlertSound(isAnyCriticalAlertActive);
}

function updateSinglePanel(lineNumber, data) {
    const statusNormalized = normalizeStatus(data.is_critical_alert ? 'Defective' : data.status);
    
    // Status Header
    const statusEl = document.getElementById(`panel_status_${lineNumber}`);
    if (statusEl) {
        statusEl.textContent = data.status;
        statusEl.className = `panel-status status-${statusNormalized}`;
    }

    // Text Details
    setText(`detail_assembly_${lineNumber}`, data.kpi.assembly);
    setText(`detail_time_${lineNumber}`, data.details.time);
    setText(`detail_ref_${lineNumber}`, data.details.component_ref);
    setText(`detail_part_${lineNumber}`, data.details.part_number);
    setText(`detail_machine_defect_${lineNumber}`, data.details.machine_defect);
    setText(`detail_inspect_${lineNumber}`, data.details.inspection_result);
    setText(`detail_review_${lineNumber}`, data.details.review_result);

    // Image / Placeholder
    const imgContainer = document.getElementById(`image_container_${lineNumber}`);
    if (imgContainer) {
        imgContainer.className = `image-container status-${statusNormalized}`;
        imgContainer.classList.toggle('critical-alert', data.is_critical_alert);
        
        if (data.image_url) {
            imgContainer.innerHTML = `<img src="${data.image_url}" alt="Defect" class="defect-image" loading="lazy">`;
        } else {
            const displayText = data.status === 'INACTIVE' ? 'NO SIGNAL' : data.status;
            imgContainer.innerHTML = `<div class="pcb-visual-placeholder pcb-visual-${statusNormalized}"><span>${displayText}</span></div>`;
        }
    }

    // KPI Numbers
    updateKPI(lineNumber, data.kpi);

    // Chart
    updateComparisonChart(lineNumber, data.comparison_data);
}

function updateKPI(line, kpi) {
    setKPIValue(`kpi_pass_rate_${line}`, `${kpi.pass_rate}%`, kpi.pass_rate >= CONFIG.TARGET_PASS_RATE);
    setKPIValue(`kpi_ppm_${line}`, kpi.ppm, kpi.ppm <= CONFIG.TARGET_PPM);
    setKPIValue(`kpi_inspected_${line}`, kpi.total_inspected);
    setKPIValue(`kpi_pass_${line}`, kpi.total_pass);
    setKPIValue(`kpi_false_call_${line}`, kpi.total_false_call);
}

function setKPIValue(id, value, isGood = null) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = value;
    if (isGood !== null) {
        el.classList.toggle('kpi-good', isGood);
        el.classList.toggle('kpi-bad', !isGood);
    }
}

// --- Chart Logic ---
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
                    backgroundColor: ['#475569', '#22d3ee'],
                    borderColor: ['#475569', '#22d3ee'],
                    borderWidth: 1,
                    borderRadius: 4,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: {
                    x: { ticks: { color: '#e2e8f0', font: { size: 10 } }, grid: { display: false } },
                    y: { 
                        display: true, 
                        beginAtZero: true, 
                        max: 100, 
                        ticks: { color: '#94a3b8', stepSize: 25 }, 
                        grid: { color: '#ffffff20', drawBorder: false }
                    }
                },
                plugins: { legend: { display: false }, tooltip: { enabled: false }, datalabels: { display: true, color: '#fff', anchor: 'end', align: 'top', formatter: v => v + '%' } }
            }
        });
    }
}

// --- Audio & Helpers ---
function toggleSound(btn) {
    if (!state.soundUnlocked) {
        state.soundUnlocked = true;
        state.alertAudio.play().then(() => state.alertAudio.pause()).catch(() => {});
    }
    state.isMuted = !state.isMuted;
    btn.classList.toggle('muted', state.isMuted);
    
    // Re-check status
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
            console.warn("Audio play blocked:", e);
            state.isSoundLooping = false;
        });
    }
}

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text || 'N/A';
}

function normalizeStatus(status) {
    return (status || 'inactive').toLowerCase().replace(/\s+/g, '_');
}

function updateClock() {
    const now = new Date();
    setText('clock', now.toLocaleTimeString('id-ID', { hour12: false }));
    setText('date', now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
}

// --- HTML Templates ---
const createDefaultLineData = () => ({
    status: 'INACTIVE', is_critical_alert: false, image_url: null,
    details: { time: 'N/A', component_ref: 'N/A', part_number: 'N/A', machine_defect: 'N/A', inspection_result: 'N/A', review_result: 'N/A' },
    kpi: { assembly: 'N/A', total_inspected: 0, total_pass: 0, total_defect: 0, total_false_call: 0, pass_rate: 0, ppm: 0 },
    comparison_data: { before: { pass_rate: 0 }, current: { pass_rate: 0 } }
});

const createPanelHTML = (num) => `
    <div class="card-ui">
        <div class="panel-header">
            <h2 class="panel-title">LINE ${num}</h2>
            <span id="panel_status_${num}" class="panel-status status-inactive">INACTIVE</span>
        </div>
        <div class="panel-content">
            <div class="panel-details">
                <div class="detail-item"><span class="detail-label">Assembly</span><strong id="detail_assembly_${num}" class="detail-value">N/A</strong></div>
                <hr style="border-color: var(--border-color); margin: 0.2rem 0;">
                <div class="detail-item"><span class="detail-label">Time</span><span id="detail_time_${num}" class="detail-value">N/A</span></div>
                <div class="detail-item"><span class="detail-label">Ref</span><span id="detail_ref_${num}" class="detail-value">N/A</span></div>
                <div class="detail-item"><span class="detail-label">Part</span><span id="detail_part_${num}" class="detail-value">N/A</span></div>
                <div class="detail-item"><span class="detail-label">Defect</span><span id="detail_machine_defect_${num}" class="detail-value">N/A</span></div>
                <div class="detail-item"><span class="detail-label">Insp</span><span id="detail_inspect_${num}" class="detail-value">N/A</span></div>
                <div class="detail-item"><span class="detail-label">Rev</span><span id="detail_review_${num}" class="detail-value">N/A</span></div>
            </div>
            <div id="image_container_${num}" class="image-container status-inactive" data-line="${num}"></div>
        </div>
        <div class="panel-kpi-grid">
            <div class="kpi-item"><div id="kpi_pass_rate_${num}" class="kpi-value">0%</div><div class="kpi-label">Pass Rate</div></div>
            <div class="kpi-item"><div id="kpi_ppm_${num}" class="kpi-value">0</div><div class="kpi-label">PPM</div></div>
            <div class="kpi-item"><div id="kpi_inspected_${num}" class="kpi-value">0</div><div class="kpi-label">Inspected</div></div>
            <div class="kpi-item"><div id="kpi_pass_${num}" class="kpi-value kpi-pass-color">0</div><div class="kpi-label">Pass</div></div>
            <div class="kpi-item"><div id="kpi_false_call_${num}" class="kpi-value kpi-false_call-color">0</div><div class="kpi-label">F. Call</div></div>
        </div>
        <div class="chart-container">
            <canvas id="comparisonChart_${num}"></canvas>
        </div>
    </div>`;