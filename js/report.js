$(document).ready(function() {
    // ==========================================================================
    // Inisialisasi Komponen
    // ==========================================================================
  
    // Inisialisasi Flatpickr (Date Picker)
    const dateRangePicker = flatpickr("#date_range", {
        mode: "range",
        dateFormat: "Y-m-d",
    });
  
    // Inisialisasi DataTable
    const table = $('#report_table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "api/get_report_data.php",
            "type": "POST",
            "data": function(d) {
                d.date_filter = dateRangePicker.selectedDates.map(date => date.toISOString().slice(0, 10));
                d.line_filter = $('#line_filter').val();
            },
            "error": function(xhr, error, thrown) {
                console.error("DataTables Ajax error:", xhr.responseText);
                alert("Failed to load data from server. Check console for details.");
            }
        },
        "columns": [
            { "data": "EndTime", "title": "Timestamp" },
            { "data": "LineName", "title": "Line" },
            { "data": "Assembly", "title": "Assembly" },
            { "data": "LotCode", "title": "Lot Code" },
            { "data": "TuningCycleID", "title": "Cycle" },
            { "data": "DebuggerFullName", "title": "Debugger" },
            { "data": "Inspected", "title": "Inspected" },
            { "data": "Pass", "title": "Pass" },
            { "data": "Defect", "title": "Defect" },
            { "data": "FalseCall", "title": "False Call" },
            { "data": "PassRate", "title": "Pass Rate (%)" },
            { "data": "PPM", "title": "PPM" },
            { "data": "Notes", "title": "Notes / Change Log" }
        ],
        "columnDefs": [
            { "width": "140px", "targets": 0 }, 
            { "width": "120px", "targets": 5 }, 
            { "width": "250px", "targets": 12 } 
        ],
        "responsive": true,
        "pageLength": 10,
        "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
        "searching": true,
        "language": {
            "search": "",
            "searchPlaceholder": "Search Assembly or Lot..."
        },
        "dom": '<"dataTables_wrapper"lfr>t<"dataTables_wrapper"ip>',
        "scrollX": true,
        // Hapus fixed height agar scroll halaman yang bekerja
        "scrollCollapse": true,
    });
  
    // ==========================================================================
    // Event Listeners
    // ==========================================================================
  
    $('#view_data').on('click', function() {
        table.ajax.reload(); 
    });
  
    $('#export_excel').on('click', function() {
        const params = new URLSearchParams();
        const date_filter = dateRangePicker.selectedDates.map(date => date.toISOString().slice(0, 10));
  
        params.append('export', 'true');
        if (date_filter.length === 2) {
            params.append('date_filter', JSON.stringify(date_filter));
        }
        params.append('line_filter', $('#line_filter').val());
  
        const btn = $(this);
        btn.prop('disabled', true).text('Exporting...');
  
        fetch('api/get_report_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) throw new Error(data.error);
            if (data.length === 0) {
                alert("No data to export for the selected filters.");
                return;
            }
  
            const worksheet = XLSX.utils.json_to_sheet(data);
            const workbook = XLSX.utils.book_new();
            const now = new Date();
            const timestamp = now.getFullYear() +
                ("0" + (now.getMonth() + 1)).slice(-2) + 
                ("0" + now.getDate()).slice(-2) + "_" +
                ("0" + now.getHours()).slice(-2) +
                ("0" + now.getMinutes()).slice(-2) +
                ("0" + now.getSeconds()).slice(-2);
  
            const filename = `AOI_KPI_Report_${timestamp}.xlsx`;
            XLSX.utils.book_append_sheet(workbook, worksheet, "KPI Report");
            XLSX.writeFile(workbook, filename);
        })
        .catch(error => {
            console.error('Export error:', error);
            alert('Failed to export data.');
        })
        .finally(() => {
            btn.prop('disabled', false).text('EXPORT EXCEL');
        });
    });
  
    // REVISI CLOCK
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
        
        const days = ['MIN', 'SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB'];
        const months = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
        
        const dayName = days[now.getDay()];
        const dayNum = now.getDate();
        const monthName = months[now.getMonth()];
        const year = now.getFullYear();
        
        const dateStr = `${dayName}, ${dayNum} ${monthName} ${year}`;
        
        $('#clock').text(timeStr);
        $('#date').text(dateStr);
    }
    
    updateClock();
    setInterval(updateClock, 1000);
  });