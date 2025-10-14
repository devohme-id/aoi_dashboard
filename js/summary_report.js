$(document).ready(function() {
    // Inisialisasi Komponen & Variabel
    const dateRangePicker = flatpickr("#date_range", {
        mode: "range",
        dateFormat: "Y-m-d",
    });

    // Fungsi untuk mengisi Filter Dropdowns
    async function populateUserFilters() {
        try {
            const response = await fetch('api/get_users.php');
            const users = await response.json();

            const analystFilter = $('#analyst_filter');
            users.analysts.forEach(user => {
                analystFilter.append(new Option(user.FullName, user.UserID));
            });

            const operatorFilter = $('#operator_filter');
            users.operators.forEach(user => {
                operatorFilter.append(new Option(user.FullName, user.UserID));
            });
        } catch (error) {
            console.error("Failed to populate user filters:", error);
        }
    }

    // Inisialisasi DataTable
    const table = $('#summary_table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "api/get_summary_data.php",
            "type": "POST",
            "data": function(d) {
                d.date_filter = dateRangePicker.selectedDates.map(date => date.toISOString().slice(0, 10));
                d.line_filter = $('#line_filter').val();
                d.analyst_filter = $('#analyst_filter').val();
                d.operator_filter = $('#operator_filter').val();
            },
            "error": function(xhr, error, thrown) {
                console.error("DataTables Ajax error:", xhr.responseText);
                alert("Failed to load data from server. Check console for details.");
            }
        },
        // PERUBAHAN: Menambahkan kolom LotCode
        "columns": [
            { "data": "VerificationTimestamp", "title": "Verification Time" },
            { "data": "AnalystName", "title": "Analyst" },
            { "data": "OperatorName", "title": "Operator" },
            { "data": "LineName", "title": "Line" },
            { "data": "Assembly", "title": "Assembly" },
            { "data": "LotCode", "title": "Lot Code" },
            { "data": "MachineDefectCode", "title": "Machine Defect" },
            { "data": "OperatorResult", "title": "Operator Result" },
            { "data": "AnalystDecision", "title": "Analyst Decision" },
            { "data": "AnalystNotes", "title": "Notes", "className": "notes-column" }
        ],
        "responsive": true,
        "pageLength": 10,
        "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
        "searching": true,
        "language": { "search": "", "searchPlaceholder": "Search Assembly, Defect..." },
        "dom": '<"datatable-header"lfr>t<"datatable-footer"ip>',
        "scrollX": true,
        "scrollY": 'calc(100vh - 380px)',
        "scrollCollapse": true,
    });

    // Event Listeners
    $('#view_data').on('click', () => table.ajax.reload());

    $('#export_excel').on('click', function() {
        const btn = $(this).prop('disabled', true).text('Exporting...');
        const formData = new FormData();
        const date_filter = dateRangePicker.selectedDates.map(date => date.toISOString().slice(0, 10));

        formData.append('export', 'true');
        if (date_filter.length === 2) formData.append('date_filter', JSON.stringify(date_filter));
        formData.append('line_filter', $('#line_filter').val());
        formData.append('analyst_filter', $('#analyst_filter').val());
        formData.append('operator_filter', $('#operator_filter').val());
        
        const params = new URLSearchParams(formData);

        fetch('api/get_summary_data.php', { method: 'POST', body: params })
        .then(response => response.json())
        .then(data => {
            if (data.error) throw new Error(data.error);
            if (data.length === 0) return alert("No data to export for the selected filters.");
            
            const worksheet = XLSX.utils.json_to_sheet(data);
            const workbook = XLSX.utils.book_new();
            const timestamp = new Date().toISOString().replace(/[-:.]/g, "").slice(0, 15);
            XLSX.utils.book_append_sheet(workbook, worksheet, "Verification Summary");
            XLSX.writeFile(workbook, `Verification_Summary_${timestamp}.xlsx`);
        })
        .catch(error => {
            console.error('Export error:', error);
            alert('Failed to export data.');
        })
        .finally(() => btn.prop('disabled', false).text('Export to Excel'));
    });

    function updateClock() {
        const now = new Date();
        $('#clock').text(now.toLocaleTimeString('id-ID', { hour12: false }));
        $('#date').text(now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
    }
    
    // Inisialisasi
    populateUserFilters();
    updateClock();
    setInterval(updateClock, 1000);
});

