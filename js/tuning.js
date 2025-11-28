/**
 * tuning.js - Tuning Cycle Management
 * Updated with Modal Login Redirect support
 */
$(document).ready(function () {
  const elements = {
      line: $('#line_id'),
      assembly: $('#assembly_name'),
      form: $('#tuning_form'),
      status: $('#status_message'),
      submitBtn: $('#submit_button')
  };

  // Helper: Redirect to login modal on session timeout
  function handleAuthError(xhr) {
      if (xhr.status === 401) {
          const currentUrl = encodeURIComponent(window.location.pathname);
          window.location.href = `index.php?trigger_login=true&redirect=${currentUrl}&login_error=Sesi+berakhir.+Silakan+login.`;
          return true;
      }
      return false;
  }

  // 1. Load Assemblies on Line Change
  elements.line.on('change', function () {
      const lineId = $(this).val();
      elements.assembly.prop('disabled', true).html('<option value="">Loading...</option>');

      if (!lineId) {
          elements.assembly.html('<option value="">-- Choose Line First --</option>');
          return;
      }

      $.ajax({
          url: 'api/get_assemblies.php',
          type: 'POST',
          data: { line_id: lineId },
          dataType: 'json',
          success: function (response) {
              if (response.error) {
                  elements.assembly.html(`<option value="">Error: ${response.error}</option>`);
              } else {
                  let options = '<option value="">-- Choose Assembly --</option>';
                  const currentAssembly = response.current_assembly;

                  response.all_assemblies.forEach(function (assembly) {
                      const isCurrent = assembly === currentAssembly;
                      const prefix = isCurrent ? '(Current) ' : '';
                      const selectedAttr = isCurrent ? 'selected' : '';
                      options += `<option value="${assembly}" ${selectedAttr}>${prefix}${assembly}</option>`;
                  });

                  elements.assembly.html(options).prop('disabled', false);
              }
          },
          error: function (xhr, status, error) {
              if (!handleAuthError(xhr)) {
                  console.error("Fetch Assembly Error:", error);
                  elements.assembly.html('<option value="">Failed to load assemblies</option>');
              }
          }
      });
  });

  // 2. Submit New Cycle
  elements.form.on('submit', function (e) {
      e.preventDefault();
      
      elements.status.text('').removeClass('success error');
      elements.submitBtn.prop('disabled', true).find('span').text('Saving...');

      const formData = {
          line_id: elements.line.val(),
          assembly_name: elements.assembly.val(),
          notes: $('#notes').val()
      };

      $.ajax({
          url: 'api/start_new_cycle.php',
          type: 'POST',
          data: formData,
          dataType: 'json',
          success: function (response) {
              if (response.success) {
                  elements.status.text(response.message).addClass('success');
                  elements.form[0].reset();
                  elements.assembly.prop('disabled', true).html('<option value="">-- Choose Line First --</option>');
                  // Trigger change to refresh list (optional)
                  elements.line.trigger('change');
              } else {
                  elements.status.text(`Error: ${response.message}`).addClass('error');
              }
          },
          error: function (xhr, status, error) {
              if (!handleAuthError(xhr)) {
                  console.error("Submit Cycle Error:", error);
                  let msg = "A server error occurred.";
                  if (xhr.responseJSON && xhr.responseJSON.message) {
                      msg = xhr.responseJSON.message;
                  }
                  elements.status.text(`Error: ${msg}`).addClass('error');
              }
          },
          complete: function () {
              elements.submitBtn.prop('disabled', false).find('span').text('Start New Cycle & Save');
          }
      });
  });

  // Clock
  function updateClock() {
      const now = new Date();
      $('#clock').text(now.toLocaleTimeString('id-ID', { hour12: false }));
      $('#date').text(now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
  }
  updateClock();
  setInterval(updateClock, 1000);
});