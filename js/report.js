document.addEventListener("DOMContentLoaded", () => {
    const el = {
        dateRange: document.querySelector("#date_range"),
        lineFilter: document.querySelector("#line_filter"),
        btnView: document.querySelector("#view_data"),
        btnExport: document.querySelector("#export_excel"),
        clock: document.querySelector("#clock"),
        dateDisplay: document.querySelector("#date"),
    };

    const dateRangePicker = flatpickr(el.dateRange, {
        mode: "range",
        dateFormat: "Y-m-d",
    });

    const table = new DataTable("#report_table", {
        processing: true,
        serverSide: true,
        ajax: {
            url: "api/get_report_data.php",
            type: "POST",
            data: (d) => {
                d.date_filter = dateRangePicker.selectedDates.map(date => date.toISOString().slice(0, 10));
                d.line_filter = el.lineFilter.value;
            },
            error: (xhr) => {
                console.error("DataTables error:", xhr.responseText);
                alert("Failed to load data.");
            }
        },
        columns: [
            { data: "EndTime", title: "Timestamp" },
            { data: "LineName", title: "Line" },
            { data: "Assembly", title: "Assembly" },
            { data: "LotCode", title: "Lot Code" },
            { data: "TuningCycleID", title: "Cycle" },
            { data: "DebuggerFullName", title: "Debugger" },
            { data: "Inspected", title: "Inspected" },
            { data: "Pass", title: "Pass" },
            { data: "Defect", title: "Defect" },
            { data: "FalseCall", title: "False Call" },
            { data: "PassRate", title: "Pass Rate (%)" },
            { data: "PPM", title: "PPM" },
            { data: "Notes", title: "Notes / Change Log" }
        ],
        columnDefs: [
            { width: "140px", targets: 0 },
            { width: "120px", targets: 5 },
            { width: "250px", targets: 12 }
        ],
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        searching: true,
        language: { search: "", searchPlaceholder: "Search Assembly or Lot..." },
        dom: '<"dataTables_wrapper"lfr>t<"dataTables_wrapper"ip>',
        scrollX: true,
        scrollCollapse: true,
    });

    el.btnView.addEventListener("click", () => table.ajax.reload());

    el.btnExport.addEventListener("click", async () => {
        const btn = el.btnExport;
        btn.disabled = true;
        btn.textContent = "Exporting...";

        const params = new URLSearchParams();
        const dates = dateRangePicker.selectedDates.map(date => date.toISOString().slice(0, 10));
        
        params.append("export", "true");
        if (dates.length === 2) params.append("date_filter", JSON.stringify(dates));
        params.append("line_filter", el.lineFilter.value);

        try {
            const res = await fetch("api/get_report_data.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: params
            });
            
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            if (!data.length) {
                alert("No data to export.");
                return;
            }

            const worksheet = XLSX.utils.json_to_sheet(data);
            const workbook = XLSX.utils.book_new();
            const timestamp = new Date().toISOString().replace(/[-T:.Z]/g, "").slice(0, 14);
            
            XLSX.utils.book_append_sheet(workbook, worksheet, "KPI Report");
            XLSX.writeFile(workbook, `AOI_KPI_Report_${timestamp}.xlsx`);
        } catch (err) {
            console.error("Export error:", err);
            alert("Failed to export data.");
        } finally {
            btn.disabled = false;
            btn.textContent = "EXPORT EXCEL";
        }
    });

    const updateClock = () => {
        const now = new Date();
        const days = ["MIN", "SEN", "SEL", "RAB", "KAM", "JUM", "SAB"];
        const months = ["JAN", "FEB", "MAR", "APR", "MEI", "JUN", "JUL", "AGU", "SEP", "OKT", "NOV", "DES"];

        if (el.clock) el.clock.textContent = now.toLocaleTimeString("id-ID", { hour12: false });
        if (el.dateDisplay) el.dateDisplay.textContent = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
    };

    updateClock();
    setInterval(updateClock, 1000);
});