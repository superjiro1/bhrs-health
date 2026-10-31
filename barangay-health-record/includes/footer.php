    </main>
    <footer class="app-footer">© <?= date('Y') ?> <?= e(APP_NAME) ?> · Confidential health information</footer>
  </div>
</div>

<!-- Confirmation dialog (used by every destructive action) -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-body text-center p-4">
        <i class="bi bi-exclamation-circle text-warning fs-1"></i>
        <h6 class="mt-2" id="confirmTitle">Are you sure?</h6>
        <p class="small text-muted mb-3" id="confirmText"></p>
        <button type="button" class="btn btn-light btn-sm me-1" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" id="confirmYes">Confirm</button>
      </div>
    </div>
  </div>
</div>

<script src="<?= e(vendor('bootstrap/bootstrap.bundle.min.js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js')) ?>"></script>
<?php if (!empty($useCharts)): ?>
<script src="<?= e(vendor('chartjs/chart.umd.js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.js')) ?>"></script>
<?php endif; ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<?php if (!empty($extraScript)) { echo $extraScript; } ?>
</body>
</html>
