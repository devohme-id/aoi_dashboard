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
              // Mengirim data filter tambahan ke server
              d.date_filter = dateRangePicker.selectedDates.map(date => date.toISOString().slice(0, 10));
              d.line_filter = $('#line_filter').val();
          },
          "error": function(xhr, error, thrown) {
              // Menampilkan error di console jika terjadi masalah Ajax
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
          { "width": "140px", "targets": 0 }, // Timestamp
          { "width": "120px", "targets": 5 }, // Debugger (Full Name)
          { "width": "250px", "targets": 12 } // Notes column
      ],
      "responsive": true,
      "pageLength": 10,
      "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
      "searching": true,
      "language": {
          "search": "",
          "searchPlaceholder": "Search Assembly or Lot..."
      },
      "dom": '<"datatable-header"lfr>t<"datatable-footer"ip>',
      "scrollX": true,
      "scrollY": 'calc(100vh - 350px)', // Tinggi dinamis
      "scrollCollapse": true,
  });

  // ==========================================================================
  // Event Listeners
  // ==========================================================================

  // Tombol "View Data"
  $('#view_data').on('click', function() {
      table.ajax.reload(); // Memuat ulang data tabel dengan filter baru
  });

  // Tombol "Export to Excel"
  $('#export_excel').on('click', function() {
      const params = new URLSearchParams();
      const date_filter = dateRangePicker.selectedDates.map(date => date.toISOString().slice(0, 10));

      params.append('export', 'true');
      if (date_filter.length === 2) {
          params.append('date_filter', JSON.stringify(date_filter));
      }
      params.append('line_filter', $('#line_filter').val());

      // Tampilkan loading state
      const btn = $(this);
      btn.prop('disabled', true).find('span').text('Exporting...');

      fetch('api/get_report_data.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: params
      })
      .then(response => response.json())
      .then(data => {
          if (data.error) {
              throw new Error(data.error);
          }
          if (data.length === 0) {
              alert("No data to export for the selected filters.");
              return;
          }

          const worksheet = XLSX.utils.json_to_sheet(data);
          const workbook = XLSX.utils.book_new();
          const now = new Date();

          // --- ▼▼▼ REVISI KESALAHAN (PENAMBAHAN TANDA '+') ▼▼▼ ---
          const timestamp = now.getFullYear() +
              ("0" + (now.getMonth() + 1)).slice(-2) + // <-- TANDA '+' HILANG DI SINI
              ("0" + now.getDate()).slice(-2) + "_" +
              ("0" + now.getHours()).slice(-2) +
              ("0" + now.getMinutes()).slice(-2) +
              ("0" + now.getSeconds()).slice(-2);
          // --- ▲▲▲ SELESAI ▲▲▲ ---

          const filename = `AOI_KPI_Report_${timestamp}.xlsx`;
          XLSX.utils.book_append_sheet(workbook, worksheet, "KPI Report");
          XLSX.writeFile(workbook, filename);
      })
      .catch(error => {
          console.error('Export error:', error);
          alert('Failed to export data. Please check the console for details.');
      })
      .finally(() => {
          // Kembalikan tombol ke state normal
          btn.prop('disabled', false).find('span').text('Export to Excel');
      });
  });

  // Update Jam
  function updateClock() {
      const now = new Date();
      $('#clock').text(now.toLocaleTimeString('id-ID', { hour12: false }));
      $('#date').text(now.toLocaleDateString('id-ID', {
          weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
      }));
  }
  updateClock();
  setInterval(updateClock, 1000);
});