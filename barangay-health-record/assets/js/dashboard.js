(function () {
  'use strict';
  if (!window.Chart || !window.BHRS_CHARTS) {
    document.querySelectorAll('.chart-box').forEach(function (b) {
      b.innerHTML = '<div class="empty">Charts could not load (Chart.js unavailable offline).</div>';
    });
    return;
  }
  var d = window.BHRS_CHARTS, sage = '#4f8a7b';
  Chart.defaults.font.family = 'Segoe UI, system-ui, sans-serif';
  new Chart(document.getElementById('chartMonths'), {
    type: 'bar',
    data: { labels: d.months.labels, datasets: [{ label: 'Consultations', data: d.months.data, backgroundColor: sage, borderRadius: 6 }] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
  });
  new Chart(document.getElementById('chartAge'), {
    type: 'doughnut',
    data: { labels: d.age.labels, datasets: [{ data: d.age.data, backgroundColor: ['#9ccabd', sage, '#2f5d52', '#c9d6d2'], borderWidth: 0 }] },
    options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } }
  });
})();
