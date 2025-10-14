// ==========================================================================
// Konfigurasi Aplikasi
// ==========================================================================
const API_URL = 'api/get_dashboard_data.php';
const REFRESH_INTERVAL = 5000; // 5 detik
const TARGET_PASS_RATE = 90;
const TARGET_PPM = 2100;
const SOUND_DELAY = 1000; // Jeda 2 detik antar pemutaran suara
const lineCharts = {}; // Objek untuk menyimpan instance chart
let alertAudio; // Variabel untuk elemen audio
let isSoundLooping = false; // Flag untuk mengontrol loop suara kustom
let isMuted = true; // Suara dimulai dalam keadaan 'muted'
let soundUnlocked = false; // Flag untuk menandai interaksi pertama pengguna

// ==========================================================================
// Inisialisasi Aplikasi
// ==========================================================================
document.addEventListener('DOMContentLoaded', () => {
    Chart.register(ChartDataLabels);
    alertAudio = document.getElementById('alert-sound');
    const soundToggleBtn = document.getElementById('sound-toggle-btn');

    if (alertAudio) {
        alertAudio.addEventListener('ended', () => {
            if (isSoundLooping) {
                setTimeout(playAlertSound, SOUND_DELAY);
            }
        });
    }

    if (soundToggleBtn) {
        soundToggleBtn.addEventListener('click', () => {
            // Interaksi pertama untuk 'membuka kunci' audio
            if (!soundUnlocked) {
                soundUnlocked = true;
                // Coba putar dan jeda audio untuk memenuhi kebijakan browser
                alertAudio.play().then(() => alertAudio.pause()).catch(()=>{});
            }

            // Toggle status mute
            isMuted = !isMuted;
            soundToggleBtn.classList.toggle('muted', isMuted);
            
            // Segarkan status suara berdasarkan kondisi alert saat ini
            const isCriticalActive = document.querySelector('.critical-alert') !== null;
            manageAlertSound(isCriticalActive);
        });
    }

    const startDashboard = () => {
        fetchData();
        setInterval(fetchData, REFRESH_INTERVAL);
        updateClock();
        setInterval(updateClock, 1000);
    };
    startDashboard();
});

// ==========================================================================
// Fungsi Pengambilan & Pembaruan Data
// ==========================================================================
async function fetchData() {
    try {
        const response = await fetch(`${API_URL}?t=${new Date().getTime()}`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.json();
        if (data.error) throw new Error(data.error);
        updateDashboardUI(data.lines || {});
    } catch (error) {
        console.error('Gagal mengambil data:', error);
    }
}

function updateDashboardUI(linesData) {
    const panelArea = document.getElementById('panel-area');
    if (panelArea.children.length === 0) {
        let content = '';
        for (let i = 1; i <= 6; i++) {
            content += createPanelHTML(i);
        }
        panelArea.innerHTML = content;
    }

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

// ==========================================================================
// Manajemen Suara dengan Jeda dan Kontrol Mute
// ==========================================================================
function manageAlertSound(shouldPlay) {
    // Mulai loop hanya jika: harus main, tidak di-mute, audio sudah 'unlocked', dan loop belum berjalan
    if (shouldPlay && !isMuted && soundUnlocked && !isSoundLooping) {
        isSoundLooping = true;
        playAlertSound();
    } 
    // Hentikan loop jika: tidak harus main, ATAU di-mute
    else if ((!shouldPlay || isMuted) && isSoundLooping) {
        isSoundLooping = false;
        if (alertAudio) {
            alertAudio.pause();
            alertAudio.currentTime = 0;
        }
    }
}

function playAlertSound() {
    if (isSoundLooping && alertAudio) {
        alertAudio.play().catch(e => {
            console.warn("Gagal memutar audio:", e.name);
            // Jika gagal karena belum di-unlock, hentikan loop agar tidak mencoba terus-menerus
            if (e.name === 'NotAllowedError') {
                isSoundLooping = false;
            }
        });
    }
}


// ==========================================================================
// Fungsi Pembaruan UI per Panel
// ==========================================================================
function updateSinglePanel(lineNumber, data) {
    let status_normalized;
    let status_text = data.status;

    if (data.is_critical_alert) {
        status_normalized = 'defective'; 
    } else {
        status_normalized = normalizeStatus(data.status);
    }

    const statusElement = document.getElementById(`panel_status_${lineNumber}`);
    statusElement.textContent = status_text;
    statusElement.className = `panel-status status-${status_normalized}`;

    const details = data.details;
    document.getElementById(`detail_assembly_${lineNumber}`).textContent = data.kpi.assembly;
    document.getElementById(`detail_time_${lineNumber}`).textContent = details.time;
    document.getElementById(`detail_ref_${lineNumber}`).textContent = details.component_ref;
    document.getElementById(`detail_part_${lineNumber}`).textContent = details.part_number;
    document.getElementById(`detail_machine_defect_${lineNumber}`).textContent = details.machine_defect;
    document.getElementById(`detail_inspect_${lineNumber}`).textContent = details.inspection_result;
    document.getElementById(`detail_review_${lineNumber}`).textContent = details.review_result;

    const imageContainer = document.getElementById(`image_container_${lineNumber}`);
    imageContainer.className = `image-container status-${status_normalized}`; 
    const imageContent = data.image_url
        ? `<img src="${data.image_url}" alt="Defect" class="defect-image">`
        : `<div class="pcb-visual-placeholder pcb-visual-${status_normalized}"><span>${data.status === 'INACTIVE' ? 'NO SIGNAL' : data.status}</span></div>`;
    imageContainer.innerHTML = imageContent;

    const kpi = data.kpi;
    const passRateEl = document.getElementById(`kpi_pass_rate_${lineNumber}`);
    const ppmEl = document.getElementById(`kpi_ppm_${lineNumber}`);
    
    passRateEl.textContent = `${kpi.pass_rate}%`;
    passRateEl.className = 'kpi-value';
    passRateEl.classList.toggle('kpi-good', parseFloat(kpi.pass_rate) >= TARGET_PASS_RATE);
    passRateEl.classList.toggle('kpi-bad', parseFloat(kpi.pass_rate) < TARGET_PASS_RATE);

    ppmEl.textContent = kpi.ppm;
    ppmEl.className = 'kpi-value';
    ppmEl.classList.toggle('kpi-good', kpi.ppm <= TARGET_PPM);
    ppmEl.classList.toggle('kpi-bad', kpi.ppm > TARGET_PPM);

    document.getElementById(`kpi_inspected_${lineNumber}`).textContent = kpi.total_inspected;
    document.getElementById(`kpi_pass_${lineNumber}`).textContent = kpi.total_pass;
    document.getElementById(`kpi_false_call_${lineNumber}`).textContent = kpi.total_false_call;
    
    imageContainer.classList.toggle('critical-alert', data.is_critical_alert);

    updateChart(lineNumber, data.pass_rate_history);
}

function updateChart(lineNumber, chartData) {
    const canvas = document.getElementById(`passRateChart_${lineNumber}`);
    if (!canvas) return;

    if (lineCharts[lineNumber]) {
        lineCharts[lineNumber].data.labels = chartData.labels;
        lineCharts[lineNumber].data.datasets[0].data = chartData.data;
        lineCharts[lineNumber].update();
    } else {
        lineCharts[lineNumber] = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Pass Rate (%)', data: chartData.data,
                    backgroundColor: 'rgba(34, 211, 238, 0.2)', borderColor: 'rgba(34, 211, 238, 1)',
                    borderWidth: 2, pointBackgroundColor: '#fff', tension: 0.3
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { ticks: { display: false }, grid: { display: false }, offset: true },
                    y: { min: 0, max: 100, ticks: { stepSize: 20, color: '#94a3b8' }, grid: { color: '#ffffff20', drawBorder: false } }
                },
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Pass Rate Trend (%)', color: '#e2e8f0', padding: { bottom: 10 } },
                    datalabels: {
                        display: true, align: 'top', color: '#e2e8f0',
                        font: { size: 12, family: 'MyFontText' },
                        formatter: (value) => `${value}%`
                    }
                }
            }
        });
    }
}

// ==========================================================================
// Fungsi Utilitas
// ==========================================================================
function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent = now.toLocaleTimeString('id-ID', { hour12: false });
    document.getElementById('date').textContent = now.toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });
}

function normalizeStatus(status) {
    return (status || 'inactive').toLowerCase().replace(' ', '_');
}

function createDefaultLineData() {
    return {
        status: 'INACTIVE',
        details: { time: 'N/A', component_ref: 'N/A', part_number: 'N/A', machine_defect: 'N/A', inspection_result: 'N/A', review_result: 'N/A' },
        kpi: { assembly: 'N/A', total_inspected: 0, total_pass: 0, total_false_call: 0, pass_rate: '0', ppm: 0 },
        pass_rate_history: { labels: [], data: [] },
        image_url: null,
        is_critical_alert: false
    };
}

function createPanelHTML(lineNumber) {
    return `
    <div class="card-ui">
        <div class="panel-header">
            <h2 class="panel-title">LINE ${lineNumber}</h2>
            <span id="panel_status_${lineNumber}" class="panel-status status-inactive">INACTIVE</span>
        </div>
        <div class="panel-content">
            <div class="panel-details">
                <div class="detail-item"><span class="detail-label">Assembly</span><strong id="detail_assembly_${lineNumber}" class="detail-value">N/A</strong></div>
                <hr style="border-color: var(--border-color); margin: 0.2rem 0;">
                <div class="detail-item"><span class="detail-label">Time</span><span id="detail_time_${lineNumber}" class="detail-value">N/A</span></div>
                <div class="detail-item"><span class="detail-label">Reference</span><span id="detail_ref_${lineNumber}" class="detail-value">N/A</span></div>
                <div class="detail-item"><span class="detail-label">Partnumber</span><span id="detail_part_${lineNumber}" class="detail-value">N/A</span></div>
                <div class="detail-item"><span class="detail-label">Defect</span><span id="detail_machine_defect_${lineNumber}" class="detail-value">N/A</span></div>
                <div class="detail-item"><span class="detail-label">Inspection</span><span id="detail_inspect_${lineNumber}" class="detail-value">N/A</span></div>
                <div class="detail-item"><span class="detail-label">Review</span><span id="detail_review_${lineNumber}" class="detail-value">N/A</span></div>
            </div>
            <div id="image_container_${lineNumber}" class="image-container status-inactive"></div>
        </div>
        <div class="panel-kpi-grid">
            <div class="kpi-item"><div id="kpi_pass_rate_${lineNumber}" class="kpi-value">0%</div><div class="kpi-label">Pass Rate</div></div>
            <div class="kpi-item"><div id="kpi_ppm_${lineNumber}" class="kpi-value">0</div><div class="kpi-label">PPM</div></div>
            <div class="kpi-item"><div id="kpi_inspected_${lineNumber}" class="kpi-value">0</div><div class="kpi-label">Inspected</div></div>
            <div class="kpi-item"><div id="kpi_pass_${lineNumber}" class="kpi-value kpi-pass-color">0</div><div class="kpi-label">Pass</div></div>
            <div class="kpi-item"><div id="kpi_false_call_${lineNumber}" class="kpi-value kpi-false_call-color">0</div><div class="kpi-label">False Call</div></div>
        </div>
        <div class="chart-container">
            <canvas id="passRateChart_${lineNumber}"></canvas>
        </div>
    </div>`;
}