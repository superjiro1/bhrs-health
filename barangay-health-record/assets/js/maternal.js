(function () {
  'use strict';
  var sel = document.getElementById('record_type');
  if (!sel) return;
  function show(cls, on) { document.querySelectorAll(cls).forEach(function (el) { el.style.display = on ? '' : 'none'; }); }
  function update() {
    var t = sel.value;
    show('.grp-pre', t === 'Prenatal');
    show('.grp-mom', t === 'Prenatal' || t === 'Postnatal');
    show('.grp-child', t === 'Child Growth');
  }
  sel.addEventListener('change', update);
  update();
})();
