  /* ---------- Справка (help, in Russian): generator/docs.html, with a table of contents; #/docs/<section> opens a section */
  (function (G) {
    var TOC_TITLE = 'Содержание';
    G.docs = function (h) {
      var tpl = document.getElementById('gen-docs');
      var wrap = document.createElement('div');
      wrap.innerHTML = tpl ? tpl.innerHTML : '';
      var secs = [].slice.call(wrap.querySelectorAll('section[id]'));
      var toc = secs.map(function (s) {
        var t = s.querySelector('h2');
        return '<li><a href="#/docs/' + s.id + '" data-sec="' + s.id + '">' + G.esc(t ? t.textContent : s.id) + '</a></li>';
      }).join('');
      G.main.innerHTML = '<div class="docs"><nav class="dtoc" aria-label="' + TOC_TITLE + '"><details open><summary>' + TOC_TITLE + '</summary><ol>' + toc + '</ol></details></nav>'
        + '<article class="dbody"><p class="dkicker">WebNets · Справка</p><h1 class="dh1">Руководство пользователя</h1>'
        + (G.lang() !== 'ru' ? '<p class="dmuted">This guide is in Russian. The generator itself switches language with the EN / RU selector at the top right.</p>' : '')
        + wrap.innerHTML + '</article></div>';
      var target = h[1] && document.getElementById(h[1]);
      var links = [].slice.call(G.main.querySelectorAll('.dtoc a'));
      function mark(id) { links.forEach(function (a) { if (a.dataset.sec === id) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current'); }); }
      if (window.matchMedia('(max-width: 860px)').matches) { // phones: the contents start folded, the guide comes first
        var d = G.main.querySelector('.dtoc details');
        if (d) d.open = false;
      }
      if (target) {
        target.scrollIntoView();
        mark(h[1]);
      } else {
        window.scrollTo(0, 0);
        mark(secs.length ? secs[0].id : '');
      }
      if ('IntersectionObserver' in window) { // the section being read is highlighted in the contents
        var io = new IntersectionObserver(function (es) {
          es.forEach(function (e) { if (e.isIntersecting) mark(e.target.id); });
        }, { rootMargin: '-20% 0px -70% 0px' });
        [].forEach.call(G.main.querySelectorAll('.dbody section[id]'), function (s) { io.observe(s); });
      }
    };
  })(window.GEN);
