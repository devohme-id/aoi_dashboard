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

  // === HELPER: Redirect to Login Modal ===
  function handleSessionTimeout() {
      const currentUrl = encodeURIComponent(window.location.pathname + window.location.search);
      window.location.href = `index.php?trigger_login=true&redirect=${currentUrl}&login_error=Sesi+berakhir.+Silakan+login+kembali.`;
  }

  // === INITIALIZATION ===
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
      if(elements.detailContent) elements.detailContent.addEventListener("submit", handleVerificationSubmit);

      startClock();
      fetchFeedbackData();
  }

  // === DATA FETCHING ===
  async function fetchFeedbackData() {
      toggleLoading(true);
      try {
          const response = await fetch(API_URL);
          
          // Cek Session Timeout
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

  // === RENDERING (Logic sama seperti sebelumnya) ===
  function renderTable(data) {
      if (!elements.tableBody) return;
      if (data.length === 0) {
          elements.tableBody.innerHTML = `<tr><td colspan="5" class="empty-state">No data matches current filters.</td></tr>`;
          return;
      }

      elements.tableBody.innerHTML = data.map((item, index) => {
          const badge = item.FinalResult === "False Fail" 
              ? '<span class="badge badge-yellow">FALSE FAIL</span>' 
              : '<span class="badge badge-red">DEFECTIVE</span>';
          const rowClass = item.DefectID == state.selectedDefectId ? 'selected' : '';
          const cellClass = item.is_critical ? 'critical-defect' : '';

          return `
              <tr data-id="${item.DefectID}" class="${rowClass}">
                  <td>${index + 1}</td>
                  <td>${formatTime(item.EndTime)}</td>
                  <td>${item.LineName || "N/A"}</td>
                  <td class="${cellClass}">${item.MachineDefectCode || "N/A"}</td>
                  <td>${badge}</td>
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

      const criticalBanner = item.is_critical ? '<div class="critical-banner">CRITICAL DEFECT</div>' : '';
      const operatorResultBadge = item.FinalResult === "False Fail" 
          ? '<span class="badge badge-yellow">FALSE FAIL</span>' 
          : '<span class="badge badge-red">DEFECTIVE</span>';

      elements.detailContent.innerHTML = `
          <div class="detail-content-wrapper">
              <div class="detail-image-container">
                  <img src="${item.image_url || 'assets/images/placeholder.png'}" alt="Defect Image" onerror="this.src='assets/images/placeholder.png'">
                  ${criticalBanner}
              </div>
              <div class="detail-grid">
                  <div class="detail-info-column">
                      ${detailRow('Timestamp', formatDateTime(item.EndTime))}
                      ${detailRow('Line', item.LineName)}
                      ${detailRow('Operator', item.OperatorName)}
                      ${detailRow('Assembly', item.Assembly)}
                      ${detailRow('Lot Code', item.LotCode)}
                  </div>
                  <div class="separator"></div>
                  <div class="detail-info-column">
                      ${detailRow('Comp Ref', item.ComponentRef)}
                      ${detailRow('Part Num', item.PartNumber)}
                      ${detailRow('Defect', item.MachineDefectCode)}
                      <div class="info-row"><span class="info-label">Result</span><span class="info-value">${operatorResultBadge}</span></div>
                  </div>
              </div>
              <div class="feedback-form-container">
                   <form id="verification-form">
                      <h3>Analyst Verification</h3>
                      <div class="form-group">
                          <label>Decision</label>
                          <div class="radio-card-group">
                              ${radioCard('Confirm False Fail', 'Operator OK')}
                              ${radioCard('Confirm Defect', 'Operator OK')}
                              ${radioCard('Operator Error - Defect Missed', 'Defect Missed')}
                              ${radioCard('Operator Error - Wrong Classification', 'Wrong Class')}
                          </div>
                      </div>
                      <button type="submit" class="btn-submit-feedback">Submit Verification</button>
                  </form>
              </div>
          </div>`;

      elements.detailPlaceholder.classList.add("hidden");
      elements.detailContent.classList.remove("hidden");
  }

  // === LOGIC ===
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
          renderDetailView(null);
      }

      renderTable(filtered);
  }

  async function handleVerificationSubmit(e) {
      e.preventDefault();
      if (e.target.id !== "verification-form") return;

      if (!state.selectedDefectId) {
          if(window.Notiflix) Notiflix.Notify.warning("Error: No defect selected.");
          return;
      }

      const formData = new FormData(e.target);
      const decision = formData.get("decision");
      const btn = e.target.querySelector('button[type="submit"]');

      if (!decision) {
          if(window.Notiflix) Notiflix.Notify.warning("Please select a decision.");
          return;
      }

      btn.disabled = true;
      btn.textContent = "Submitting...";

      try {
          const payload = {
              defect_id: state.selectedDefectId,
              decision: decision,
              notes: null
          };

          const response = await fetch(API_URL, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify(payload)
          });

          // Cek Session Timeout saat Submit
          if (response.status === 401) {
              handleSessionTimeout();
              return;
          }

          const result = await response.json();
          if (!result.success) throw new Error(result.error || "Submission failed");

          if(window.Notiflix) Notiflix.Notify.success("Verification submitted successfully.");
          
          state.verificationData = state.verificationData.filter(i => i.DefectID != state.selectedDefectId);
          state.selectedDefectId = null;
          
          renderDetailView(null);
          populateDropdownFilters();
          applyFilters();

      } catch (error) {
          console.error("Submit Error:", error);
          if(window.Notiflix) Notiflix.Notify.failure(error.message);
          btn.disabled = false;
          btn.textContent = "Submit Verification";
      }
  }

  function handleRowClick(e) {
      const row = e.target.closest("tr");
      if (!row || !row.dataset.id) return;

      state.selectedDefectId = row.dataset.id;
      document.querySelectorAll("#feedback-table-body tr").forEach(r => r.classList.remove("selected"));
      row.classList.add("selected");
      renderDetailView(state.selectedDefectId);
  }

  // === HELPERS ===
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
          elements.loading.innerHTML = `<div class="error-msg">${msg}</div>`;
          elements.loading.style.display = "flex";
      }
  }

  const detailRow = (label, value) => `<div class="info-row"><span class="info-label">${label}</span><span class="info-value">${value || "N/A"}</span></div>`;
  const radioCard = (val, sub) => `
      <label class="radio-card">
          <input type="radio" name="decision" value="${val}">
          <span class="card-body">
              <span class="card-title">${val}</span>
              <span class="card-subtitle">(${sub})</span>
          </span>
      </label>`;

  const formatTime = (iso) => new Date(iso).toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
  const formatDateTime = (iso) => new Date(iso).toLocaleString("id-ID");

  function startClock() {
      const update = () => {
          const now = new Date();
          const clock = document.getElementById("clock");
          const date = document.getElementById("date");
          if(clock) clock.textContent = now.toLocaleTimeString("id-ID", { hour12: false });
          if(date) date.textContent = now.toLocaleDateString("id-ID", { weekday: "long", year: "numeric", month: "long", day: "numeric" });
      };
      update();
      setInterval(update, 1000);
  }

  init();
});