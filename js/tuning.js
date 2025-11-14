$(document).ready(function () {

  const lineSelect = $('#line_id');
  const assemblySelect = $('#assembly_name');
  const form = $('#tuning_form');
  const statusMessage = $('#status_message');
  const submitButton = $('#submit_button');

  // 1. Ambil daftar assembly saat line dipilih
  lineSelect.on('change', function () {
    const lineId = $(this).val();
    assemblySelect.prop('disabled', true).html('<option value="">Loading...</option>');

    if (!lineId) {
      assemblySelect.html('<option value="">-- Choose Line First --</option>');
      return;
    }

    $.ajax({
      url: 'api/get_assemblies.php',
      type: 'POST',
      data: { line_id: lineId },
      dataType: 'json',
      success: function (response) {
        if (response.error) {
          assemblySelect.html(`<option value="">Error: ${response.error}</option>`);
        } else {
          let options = '<option value="">-- Choose Assembly --</option>';
          const currentAssembly = response.current_assembly;

          response.all_assemblies.forEach(function (assembly) {
            // Tambahkan prefix '(Current)' jika assembly sama dengan yang sedang berjalan
            const isCurrent = assembly === currentAssembly;
            const prefix = isCurrent ? '(Current) ' : '';

            // Pilih assembly yang sedang berjalan secara default
            const selectedAttr = isCurrent ? 'selected' : '';

            options += `<option value="${assembly}" ${selectedAttr}>${prefix}${assembly}</option>`;
          });

          assemblySelect.html(options).prop('disabled', false);
        }
      },
      error: function () {
        assemblySelect.html('<option value="">Failed to load assemblies</option>');
      }
    });
  });

  // 2. Handle form submission
  form.on('submit', function (e) {
    e.preventDefault();
    statusMessage.text('').removeClass('success error');
    submitButton.prop('disabled', true).find('span').text('Saving...');

    const formData = {
      line_id: lineSelect.val(),
      assembly_name: assemblySelect.val(),
      notes: $('#notes').val(),
      user_id: $('#user_id').val()
    };

    $.ajax({
      url: 'api/start_new_cycle.php',
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function (response) {
        if (response.success) {
          statusMessage.text(response.message).addClass('success');
          form[0].reset();
          assemblySelect.prop('disabled', true).html('<option value="">-- Choose Line First --</option>');
          // Refresh assembly list to show new state
          lineSelect.trigger('change');
        } else {
          statusMessage.text(`Error: ${response.message}`).addClass('error');
        }
      },
      error: function () {
        statusMessage.text('Error: A server error occurred.').addClass('error');
      },
      complete: function () {
        submitButton.prop('disabled', false).find('span').text('Start New Cycle & Save');
      }
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
