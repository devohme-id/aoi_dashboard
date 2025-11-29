document.addEventListener("DOMContentLoaded", () => {
    const el = {
        dateRange: document.querySelector("#date_range"),
        lineFilter: document.querySelector("#line_filter"),
        analystFilter: document.querySelector("#analyst_filter"),
        operatorFilter: document.querySelector("#operator_filter"),
        btnView: document.querySelector("#view_data"),
        btnExport: document.querySelector("#export_excel"),
        clock: document.querySelector("#clock"),
        dateDisplay: document.querySelector("#date"),
    };

    const dateRangePicker = flatpickr(el.dateRange, {
        mode: "range",
        dateFormat: "Y-m-d",
    });

    async function populateUserFilters() {
        try {
            const response = await fetch("api/get_users.php");
            const users = await response.json();
            
            users.analysts.forEach(user => {
                el.analystFilter.append(new Option(user.FullName, user.UserID));
            });
            
            users.operators.forEach(user => {
                el.operatorFilter.append(new Option(user.FullName, user.UserID));
            });
        } catch (error) {
            console.error("Failed to populate user filters:", error);
        }
    }

    const table = new DataTable("#summary_table", {
        processing: true,
        serverSide: true,
        ajax: {
            url: "api/get_summary_data.php",
            type: "POST",
            data: (d) => {
                d.date_filter = dateRangePicker.selectedDates.map(date => date.toISOString().slice(0, 10));
                d.line_filter = el.lineFilter.value;
                d.analyst_filter = el.analystFilter.value;
                d.operator_filter = el.operatorFilter.value;
            },
            error: (xhr) => {
                console.error("DataTables Ajax error:", xhr.responseText);
                alert("Failed to load data.");
            }
        },
        columns: [
            { data: "VerificationTimestamp", title: "Verification Time" },
            { data: "AnalystName", title: "Analyst" },
            { data: "OperatorName", title: "Operator" },
            { data: "LineName", title: "Line" },
            { data: "Assembly", title: "Assembly" },
            { data: "LotCode", title: "Lot Code" },
            { data: "MachineDefectCode", title: "Machine Defect" },
            { data: "OperatorResult", title: "Operator Result" },
            { data: "AnalystDecision", title: "Analyst Decision" },
            { data: "AnalystNotes", title: "Notes" }
        ],
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        searching: true,
        language: { search: "", searchPlaceholder: "Search Assembly, Defect..." },
        dom: '<"dataTables_wrapper"lfr>t<"dataTables_wrapper"ip>',
        scrollX: true,
        scrollCollapse: true,
    });

    el.btnView.addEventListener("click", () => table.ajax.reload());

    el.btnExport.addEventListener("click", async () => {
        const btn = el.btnExport;
        btn.disabled = true;
        btn.textContent = "Exporting...";

        const formData = new FormData();
        const dates = dateRangePicker.selectedDates.map(date => date.toISOString().slice(0, 10));

        formData.append("export", "true");
        if (dates.length === 2) formData.append("date_filter", JSON.stringify(dates));
        formData.append("line_filter", el.lineFilter.value);
        formData.append("analyst_filter", el.analystFilter.value);
        formData.append("operator_filter", el.operatorFilter.value);

        const params = new URLSearchParams(formData);

        try {
            const response = await fetch("api/get_summary_data.php", { method: "POST", body: params });
            const data = await response.json();
            if (data.error) throw new Error(data.error);
            if (!data.length) {
                alert("No data to export for the selected filters.");
                return;
            }

            const worksheet = XLSX.utils.json_to_sheet(data);
            const workbook = XLSX.utils.book_new();
            const timestamp = new Date().toISOString().replace(/[-:.]/g, "").slice(0, 15);
            XLSX.utils.book_append_sheet(workbook, worksheet, "Verification Summary");
            XLSX.writeFile(workbook, `Verification_Summary_${timestamp}.xlsx`);
        } catch (error) {
            console.error("Export error:", error);
            alert("Failed to export data.");
        } finally {
            btn.disabled = false;
            btn.textContent = "EXCEL";
        }
    });

    const updateClock = () => {
        const now = new Date();
        const days = ["MIN", "SEN", "SEL", "RAB", "KAM", "JUM", "SAB"];
        const months = ["JAN", "FEB", "MAR", "APR", "MEI", "JUN", "JUL", "AGU", "SEP", "OKT", "NOV", "DES"];

        if (el.clock) el.clock.textContent = now.toLocaleTimeString("id-ID", { hour12: false });
        if (el.dateDisplay) el.dateDisplay.textContent = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
    };

    populateUserFilters();
    updateClock();
    setInterval(updateClock, 1000);
});