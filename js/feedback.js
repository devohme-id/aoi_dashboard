document.addEventListener("DOMContentLoaded", () => {
  // === Konfigurasi & Variabel Global ===
  // const API_URL = "api/feedback_handler.php";
  const API_URL = "/aoi_dashboard/api/feedback_handler.php";

  const CURRENT_ANALYST_ID = 8;
  let verificationData = [];
  let selectedDefectId = null;

  // === Elemen DOM ===
  const tableBody = document.getElementById("feedback-table-body");
  const loadingIndicator = document.getElementById("loading-indicator");
  const detailPlaceholder = document.getElementById("detail-view-placeholder");
  const detailContent = document.getElementById("detail-view-content");
  const lineFilter = document.getElementById("line-filter");
  const defectFilter = document.getElementById("defect-filter");

  const dateRangePicker = flatpickr("#date_range", {
    mode: "range",
    dateFormat: "Y-m-d",
  });

  // // === Fungsi Utama ===
  async function fetchFeedbackData() {
    showLoading(true);
    try {
      const searchQuery = document.getElementById("search-input").value.trim();
      var dateQuery = dateRangePicker.selectedDates.map(date => date.toISOString().slice(0, 10));
      const url = new URL(API_URL, window.location.origin);
      if (searchQuery !== "") {
        url.searchParams.append("search-input", searchQuery);
      }
      if(dateQuery !== "")
      {
        url.searchParams.append("date-range", dateQuery);
      }

      const response = await fetch(url);
      if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);
      const data = await response.json();
      if (data.error) throw new Error(data.error);

      verificationData = data;
      populateFilters();
      applyFilters();
    } catch (error) {
      console.error("Gagal mengambil data feedback:", error);
      showErrorState("Failed to load data. Please try again later.");
    } finally {
      showLoading(false);
    }
  }

  function renderTable(dataToRender) {
    if (dataToRender.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 2rem; color: var(--text-light);">No data matches the current filters.</td></tr>`;
      return;
    }
    let html = "";
    dataToRender.forEach((item, index) => {
      const resultBadge =
        item.FinalResult === "False Fail"
          ? '<span class="badge badge-yellow">FALSE FAIL</span>'
          : `<span class="badge badge-red">DEFECTIVE</span>`;
      const defectCellClass = item.is_critical ? "critical-defect" : "";
      html += `
                <tr data-id="${item.DefectID}">
                    <td>${index + 1}</td>
                    <td>${new Date(item.EndTime).toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
      })}</td>
                    <td>${item.LineName || "N/A"}</td>
                    <td class="${defectCellClass}">${item.MachineDefectCode || "N/A"
        }</td>
                    <td>${resultBadge}</td>
                </tr>
            `;
    });
    tableBody.innerHTML = html;
    updateSelectedRowVisuals();
  }

  function renderDetailView(defectId) {
    const item = verificationData.find((d) => d.DefectID == defectId);
    if (!item) {
      detailPlaceholder.classList.remove("hidden");
      detailContent.classList.add("hidden");
      return;
    }
    const criticalBanner = item.is_critical
      ? '<div class="critical-banner">CRITICAL DEFECT</div>'
      : "";
    const resultBadge =
      item.FinalResult === "False Fail"
        ? '<span class="badge badge-yellow">FALSE FAIL</span>'
        : '<span class="badge badge-red">DEFECTIVE</span>';

    // =================================================================
    // PERUBAHAN: HTML untuk card-style radio button
    // =================================================================
    detailContent.innerHTML = `
            <div class="detail-content-wrapper">
                <div class="detail-image-container">
                    <img src="${item.image_url || "assets/images/placeholder.png"
      }" alt="Defect Image">
                    ${criticalBanner}
                </div>
                <div class="detail-grid">
                    <div class="detail-info-column">
                        <div class="info-row"><span class="info-label">Timestamp</span><span class="info-value">${new Date(
        item.EndTime
      ).toLocaleString("id-ID")}</span></div>
                        <div class="info-row"><span class="info-label">Line</span><span class="info-value">${item.LineName || "N/A"
      }</span></div>
                        <div class="info-row"><span class="info-label">Operator</span><span class="info-value">${item.OperatorName || "N/A"
      }</span></div>
                        <div class="info-row"><span class="info-label">Assembly</span><span class="info-value">${item.Assembly || "N/A"
      }</span></div>
                        <div class="info-row"><span class="info-label">Lot Code</span><span class="info-value">${item.LotCode || "N/A"
      }</span></div>
                    </div>
                    <div class="separator"></div>
                    <div class="detail-info-column">
                        <div class="info-row"><span class="info-label">Component Ref</span><span class="info-value">${item.ComponentRef || "N/A"
      }</span></div>
                        <div class="info-row"><span class="info-label">Part Number</span><span class="info-value">${item.PartNumber || "N/A"
      }</span></div>
                        <div class="info-row"><span class="info-label">Machine Defect</span><span class="info-value">${item.MachineDefectCode || "N/A"
      }</span></div>
                        <div class="info-row"><span class="info-label">Operator Result</span><span class="info-value">${resultBadge}</span></div>
                    </div>
                </div>
                <div class="feedback-form-container">
                     <form id="verification-form">
                        <h3>Analyst Verification</h3>
                        <div class="form-group">
                            <label>Decision</label>
                            <div class="radio-card-group">
                                <label class="radio-card">
                                    <input type="radio" name="decision" value="Confirm False Fail">
                                    <span class="card-body">
                                        <span class="card-title">Confirm False Fail</span>
                                        <span class="card-subtitle">(Operator OK)</span>
                                    </span>
                                </label>
                                <label class="radio-card">
                                    <input type="radio" name="decision" value="Confirm Defect">
                                    <span class="card-body">
                                        <span class="card-title">Confirm Defect</span>
                                        <span class="card-subtitle">(Operator OK)</span>
                                    </span>
                                </label>
                                <label class="radio-card">
                                    <input type="radio" name="decision" value="Operator Error - Defect Missed">
                                    <span class="card-body">
                                        <span class="card-title">Operator Error</span>
                                        <span class="card-subtitle">(Defect Missed)</span>
                                    </span>
                                </label>
                                <label class="radio-card">
                                    <input type="radio" name="decision" value="Operator Error - Wrong Classification">
                                    <span class="card-body">
                                        <span class="card-title">Operator Error</span>
                                        <span class="card-subtitle">(Wrong Classification)</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <!-- <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" placeholder="Add notes if necessary..."></textarea>
                        </div> -->
                        <button type="submit" class="btn-submit-feedback">Submit Verification</button>
                    </form>
                </div>
            </div>
        `;
    detailPlaceholder.classList.add("hidden");
    detailContent.classList.remove("hidden");
  }

  async function handleFormSubmit(e) {
    e.preventDefault();

    if (!selectedDefectId) {
      //alert("Error: No defect selected. Please select an item from the list.");
      Notiflix.Notify.warning("Error: No defect selected. Please select an item from the list.");
      return;
    }

    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = "Submitting...";

    const formData = new FormData(e.target);
    const decision = formData.get("decision");

    if (!decision) {
      //alert("Please select a decision.");
      Notiflix.Notify.warning("Please select a decision.");
      btn.disabled = false;
      btn.textContent = "Submit Verification";
      return;
    }

    const payload = {
      defect_id: selectedDefectId,
      analyst_user_id: CURRENT_ANALYST_ID,
      decision: decision,
      notes: null,
    };

    try {
      const response = await fetch(API_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const result = await response.json();
      if (!result.success)
        throw new Error(result.error || "Unknown submission error");

      showVerifiedState(payload.decision);
      verificationData = verificationData.filter(
        (item) => item.DefectID != selectedDefectId
      );
      selectedDefectId = null;
      Notiflix.Notify.success("Defect berhasil diverifikasi.");
      applyFilters();
      populateFilters();
    } catch (error) {
      console.error("Submission failed:", error);
      Notiflix.Notify.failure(`Submission failed: ${error}`);
      //alert("Failed to submit feedback. Check console for details.");
      btn.disabled = false;
      btn.textContent = "Submit Verification";
    }
  }

  function populateFilters() {
    const lines = [
      ...new Set(verificationData.map((item) => item.LineName).filter(Boolean)),
    ];
    const defects = [
      ...new Set(
        verificationData.map((item) => item.MachineDefectCode).filter(Boolean)
      ),
    ];

    const currentLine = lineFilter.value;
    const currentDefect = defectFilter.value;

    lineFilter.innerHTML = '<option value="">All Lines</option>';
    defectFilter.innerHTML = '<option value="">All Defects</option>';

    lines
      .sort()
      .forEach(
        (line) =>
          (lineFilter.innerHTML += `<option value="${line}">${line}</option>`)
      );
    defects
      .sort()
      .forEach(
        (defect) =>
          (defectFilter.innerHTML += `<option value="${defect}">${defect}</option>`)
      );

    lineFilter.value = currentLine;
    defectFilter.value = currentDefect;
  }

  function applyFilters() {
    const selectedLine = lineFilter.value;
    const selectedDefect = defectFilter.value;
    const filteredData = verificationData.filter((item) => {
      const lineMatch = !selectedLine || item.LineName === selectedLine;
      const defectMatch =
        !selectedDefect || item.MachineDefectCode === selectedDefect;
      return lineMatch && defectMatch;
    });

    const isSelectedVisible = filteredData.some(
      (item) => item.DefectID == selectedDefectId
    );
    if (!isSelectedVisible) {
      selectedDefectId = null;
      renderDetailView(null);
    }
    renderTable(filteredData);
  }

  function showLoading(isLoading) {
    loadingIndicator.style.display = isLoading ? "flex" : "none";
    if (isLoading) {
      tableBody.innerHTML = "";
      detailPlaceholder.classList.remove("hidden");
      detailContent.classList.add("hidden");
    }
  }

  function showErrorState(message) {
    tableBody.innerHTML = "";
    loadingIndicator.querySelector("span").textContent = message;
    loadingIndicator.querySelector(".spinner").style.display = "none";
    loadingIndicator.style.display = "flex";
    detailPlaceholder.classList.remove("hidden");
    detailContent.classList.add("hidden");
  }

  function showVerifiedState(decision) {
    detailContent.innerHTML = `
        <div class="verified-overlay">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
            <h3>VERIFIED</h3>
            <p>${decision}</p>
        </div>`;
  }

  function updateSelectedRowVisuals() {
    document.querySelectorAll("#feedback-table-body tr").forEach((row) => {
      row.classList.toggle("selected", row.dataset.id == selectedDefectId);
    });
  }

  tableBody.addEventListener("click", (e) => {
    const row = e.target.closest("tr");
    if (row && row.dataset.id) {
      const newId = row.dataset.id;
      selectedDefectId = newId;
      updateSelectedRowVisuals();
      renderDetailView(newId);
    }
  });

  detailContent.addEventListener("submit", (e) => {
    if (e.target.id === "verification-form") handleFormSubmit(e);
  });

  lineFilter.addEventListener("change", applyFilters);
  defectFilter.addEventListener("change", applyFilters);

  function updateClock() {
    const now = new Date();
    const clockEl = document.getElementById("clock");
    const dateEl = document.getElementById("date");
    if (clockEl)
      clockEl.textContent = now.toLocaleTimeString("id-ID", { hour12: false });
    if (dateEl)
      dateEl.textContent = now.toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
      });
  }

  updateClock();
  setInterval(updateClock, 1000);
  fetchFeedbackData();

  document.getElementById("search-form").addEventListener("submit", function (e) {
    e.preventDefault();
    fetchFeedbackData();
  });

});
