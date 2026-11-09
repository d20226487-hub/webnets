<?php /** Phone menu behaviour, once per page: closes on the backdrop, on a link and on Escape. */ ?>
<script>
(function () {
  var menus = document.querySelectorAll('[data-menu]');
  [].forEach.call(menus, function (m) {
    var close = function () { m.removeAttribute('open'); };
    [].forEach.call(m.querySelectorAll('[data-menu-close], a'), function (x) { x.addEventListener('click', close); });
    m.addEventListener('toggle', function () { document.documentElement.style.overflow = m.open && !m.className.match(/dropdown/) ? 'hidden' : ''; });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    [].forEach.call(menus, function (m) { if (m.open) { m.removeAttribute('open'); m.querySelector('summary').focus(); } });
  });
})();
</script>
