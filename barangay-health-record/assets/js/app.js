(function () {
  'use strict';

  // Mobile sidebar toggle
  var sb = document.getElementById('sidebar'), bd = document.getElementById('sidebarBackdrop'), tg = document.getElementById('sidebarToggle');
  function toggle(open) { if (!sb) return; sb.classList.toggle('open', open); bd.classList.toggle('show', open); }
  if (tg) tg.addEventListener('click', function () { toggle(!sb.classList.contains('open')); });
  if (bd) bd.addEventListener('click', function () { toggle(false); });

  // Confirmation dialog for forms/buttons marked with data-confirm
  var modalEl = document.getElementById('confirmModal'), pendingForm = null, modal = null;
  if (modalEl && window.bootstrap) {
    modal = new bootstrap.Modal(modalEl);
    document.addEventListener('submit', function (ev) {
      var f = ev.target;
      if (f.dataset && f.dataset.confirm && !f.dataset.confirmed) {
        ev.preventDefault();
        ev.stopPropagation();
        pendingForm = f;
        document.getElementById('confirmTitle').textContent = f.dataset.confirmTitle || 'Are you sure?';
        document.getElementById('confirmText').textContent = f.dataset.confirm;
        modal.show();
      }
    }, true);
    document.getElementById('confirmYes').addEventListener('click', function () {
      if (pendingForm) { pendingForm.dataset.confirmed = '1'; modal.hide(); pendingForm.requestSubmit(); }
    });
  }

  // Client-side validation + loading state + duplicate-submit protection
  document.querySelectorAll('form.needs-validation').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!form.checkValidity()) {
        ev.preventDefault(); ev.stopPropagation();
        form.classList.add('was-validated');
        return;
      }
      if (form.dataset.submitting) { ev.preventDefault(); return; }
      form.dataset.submitting = '1';
      var btn = form.querySelector('button[type=submit]');
      if (btn) {
        btn.classList.add('btn-loading');
        btn.dataset.label = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
      }
    });
  });

  // Auto-dismiss success alerts
  setTimeout(function () {
    document.querySelectorAll('.alert-success').forEach(function (a) {
      if (window.bootstrap) bootstrap.Alert.getOrCreateInstance(a).close();
    });
  }, 5000);
})();
