(function () {
  'use strict';
  var d = document;

  // Sticky header shadow
  var hdr = d.getElementById('hdr');
  if (hdr) addEventListener('scroll', function () { hdr.classList.toggle('scrolled', scrollY > 10); }, { passive: true });

  // Mobile drawer
  var drawer = d.getElementById('drawer');
  function closeDrawer() { if (drawer) { drawer.classList.remove('open'); drawer.setAttribute('aria-hidden', 'true'); } }
  var burger = d.getElementById('burger');
  if (burger && drawer) burger.addEventListener('click', function () { drawer.classList.add('open'); drawer.setAttribute('aria-hidden', 'false'); });
  var dc = d.getElementById('drawerClose');
  if (dc) dc.addEventListener('click', closeDrawer);
  if (drawer) drawer.querySelectorAll('nav a').forEach(function (a) { a.addEventListener('click', closeDrawer); });

  // Forms: timestamp, page, preselected service, error message
  var params = new URLSearchParams(location.search);
  d.querySelectorAll('.qform').forEach(function (f) {
    f.ts.value = String(Math.floor(Date.now() / 1000));
    f.page.value = location.pathname;
    var pre = f.getAttribute('data-preselect') || d.body.getAttribute('data-service') || '';
    if (pre && f.service && !f.service.value) f.service.value = pre;
    if (params.get('error') === '1') { var err = f.querySelector('.form-error'); if (err) err.style.display = 'block'; }
    f.addEventListener('submit', function () {
      var b = f.querySelector('button[type=submit]');
      if (b) { b.disabled = true; b.textContent = 'Sending…'; }
    });
  });

  // Quote popup
  var modal = d.getElementById('modal');
  var lastFocus = null;
  function openQuote() {
    closeDrawer();
    lastFocus = d.activeElement;
    // carry over the page's preselected service
    var pageForm = d.querySelector('#quote .qform');
    var mf = modal.querySelector('.qform');
    if (pageForm && pageForm.service.value && !mf.service.value) mf.service.value = pageForm.service.value;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(function () { var i = modal.querySelector('input:not([type=hidden])'); if (i) i.focus(); }, 50);
  }
  function closeQuote() {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    if (lastFocus) lastFocus.focus();
  }
  d.querySelectorAll('[data-quote]').forEach(function (b) { b.addEventListener('click', openQuote); });
  var mc = d.getElementById('modalClose');
  if (mc) mc.addEventListener('click', closeQuote);
  if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) closeQuote(); });
  addEventListener('keydown', function (e) { if (e.key === 'Escape') { if (modal && modal.classList.contains('open')) closeQuote(); closeDrawer(); } });

  // Homepage service filter tabs
  var grid = d.getElementById('svcGrid');
  d.querySelectorAll('.tab').forEach(function (b) {
    b.addEventListener('click', function () {
      d.querySelectorAll('.tab').forEach(function (x) { x.classList.remove('on'); });
      b.classList.add('on');
      var f = b.getAttribute('data-f');
      if (grid) grid.querySelectorAll('.svc').forEach(function (c) { c.classList.toggle('hide', f !== 'all' && c.getAttribute('data-g') !== f); });
    });
  });
})();
