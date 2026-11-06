(function () {
  var btn = document.getElementById('sx-skeltoggle');
  if (window.name === 'sx-phone' || /[?&]sxm=1/.test(location.search)) { if (btn) btn.remove(); return; }
  var D = JSON.parse(document.getElementById('sx-devdata').textContent);
  var I = {
    en: { title: 'Developer skeleton', sections: 'Page sections', site: 'Site sections', go: 'Go to page', styles: 'Styles', wp: 'WordPress', ai: 'AI brief', sys: 'System prompt', sysNote: 'Paste into the AI tool\'s system prompt or custom instructions once; then send a brief below as the message. Edit it in the local settings page (/settings/).', aiPage: 'Brief for this page', aiSite: 'Brief for the whole site', openFile: 'Open as a file', loading: 'Loading…', aiNote: 'Paste into Claude, Cursor or another AI coding tool. It describes the structure, content, design tokens, the rules that must not break and an acceptance checklist, and asks for a rebuild in your stack.', wire: 'Wireframe', labels: 'Labels', close: 'Close',
      show: 'Show on page', role: 'Role', variant: 'Variant', variants: 'Available variants', fields: 'Fields', value: 'Value on this page', guard: 'Rules', none: 'not set',
      pages: 'Pages', current: 'this page', tokens: 'Design tokens (CSS custom properties)', fonts: 'Fonts', prefix: 'Class prefix', dialect: 'Code dialect (lib/dialect.php)', copy: 'Copy', copied: 'Copied',
      markup: 'Block markup for the page editor', markupNote: 'Paste into the WordPress code editor with the SX blocks plugin (wp/sx-blocks.php) active. Simple fields are filled in; lists and data-driven content (brands, reviews, articles) come from the site data.',
      spec: 'Page spec (JSON)', blocks: 'blocks', open: 'Open with skeleton' },
    ru: { title: 'Каркас для разработчика', sections: 'Секции страницы', site: 'Секции сайта', go: 'Перейти на страницу', styles: 'Стили', wp: 'WordPress', ai: 'Бриф для ИИ', sys: 'Системный промпт', sysNote: 'Вставьте один раз в системный промпт или пользовательские инструкции ИИ-инструмента, затем отправьте бриф ниже как сообщение. Редактируется на локальной странице настроек (/settings/).', aiPage: 'Бриф для этой страницы', aiSite: 'Бриф для всего сайта', openFile: 'Открыть файлом', loading: 'Загрузка…', aiNote: 'Вставьте в Claude, Cursor или другой ИИ-инструмент для кода. В брифе структура, тексты, дизайн-токены, правила, которые нельзя нарушать, и чек-лист приёмки; ИИ соберёт страницу на вашем стеке.', wire: 'Вайрфрейм', labels: 'Подписи', close: 'Закрыть',
      show: 'Показать на странице', role: 'Роль', variant: 'Вариант', variants: 'Доступные варианты', fields: 'Поля', value: 'Значение на этой странице', guard: 'Правила', none: 'не задано',
      pages: 'Страницы', current: 'эта страница', tokens: 'Дизайн-токены (CSS-переменные)', fonts: 'Шрифты', prefix: 'Префикс классов', dialect: 'Диалект кода (lib/dialect.php)', copy: 'Копировать', copied: 'Скопировано',
      markup: 'Разметка блоков для редактора страниц', markupNote: 'Вставьте в редактор кода WordPress при включённом плагине блоков SX (wp/sx-blocks.php). Простые поля заполнены; списки и данные (бренды, отзывы, статьи) берутся из данных сайта.',
      spec: 'Спецификация страницы (JSON)', blocks: 'блоков', open: 'Открыть с каркасом' },
    pt: { title: 'Esqueleto para devs', sections: 'Seções da página', site: 'Seções do site', go: 'Ir para a página', styles: 'Estilos', wp: 'WordPress', ai: 'Briefing para IA', sys: 'Prompt de sistema', sysNote: 'Cole uma vez no prompt de sistema ou nas instruções personalizadas da ferramenta de IA; depois envie um briefing abaixo como mensagem. Edite na página de configurações local (/settings/).', aiPage: 'Briefing desta página', aiSite: 'Briefing do site inteiro', openFile: 'Abrir como arquivo', loading: 'Carregando…', aiNote: 'Cole no Claude, Cursor ou outra ferramenta de IA para código. Traz a estrutura, os textos, os tokens de design, as regras que não podem ser quebradas e um checklist de aceite, e pede a reconstrução na sua stack.', wire: 'Wireframe', labels: 'Rótulos', close: 'Fechar',
      show: 'Mostrar na página', role: 'Papel', variant: 'Variante', variants: 'Variantes disponíveis', fields: 'Campos', value: 'Valor nesta página', guard: 'Regras', none: 'não definido',
      pages: 'Páginas', current: 'esta página', tokens: 'Tokens de design (variáveis CSS)', fonts: 'Fontes', prefix: 'Prefixo de classes', dialect: 'Dialeto do código (lib/dialect.php)', copy: 'Copiar', copied: 'Copiado',
      markup: 'Marcação de blocos para o editor', markupNote: 'Cole no editor de código do WordPress com o plugin de blocos SX (wp/sx-blocks.php) ativo. Campos simples vêm preenchidos; listas e dados (marcas, avaliações, artigos) vêm dos dados do site.',
      spec: 'Especificação da página (JSON)', blocks: 'blocos', open: 'Abrir com esqueleto' }
  };
  var T = I[D.l] || I.en;
  var root = document.documentElement, host = null, sh = null, open = false, items = [], tab = 'sections', boxes = null, ro = null;

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function ui(el) { return el.closest && el.closest('.vbar,[id^="sx-"]'); }

  // blocks on the page: each <!--sx:N--> marker is followed by the block's element
  function scan() {
    items = [];
    var w = document.createTreeWalker(document.body, NodeFilter.SHOW_COMMENT), c;
    while ((c = w.nextNode())) {
      var m = /^sx:(\d+)$/.exec(c.data);
      if (!m) continue;
      var el = c.nextSibling;
      while (el && el.nodeType !== 1) el = el.nextSibling;
      var d = D.items[+m[1]];
      if (el && d) { el.setAttribute('data-sxdev', m[1]); items.push({ i: +m[1], d: d, el: el }); }
    }
  }

  var CSS = ':host{all:initial}*{box-sizing:border-box}'
    + '.bx{position:absolute;pointer-events:none;border:2px dashed #7C3AED;border-radius:4px}.bx.n{border-color:#0891B2;border-style:dotted}'
    + '.bx b{position:absolute;left:-2px;top:-2px;pointer-events:auto;cursor:pointer;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:3px 8px;border-radius:4px 0 6px 0;background:#7C3AED;color:#fff;font:700 11px/1.3 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}'
    + '.bx.n b{background:#0891B2;left:auto;right:-2px;border-radius:0 4px 0 6px}.bx.hl{background:rgba(124,58,237,.12)}.nolabels .bx b{display:none}'
    + '.bx i{display:none}.wire .bx i{display:flex;position:absolute;left:0;right:0;top:0;height:min(100%,320px);align-items:center;justify-content:center;padding:8px;pointer-events:none;font-style:normal}'
    + '.wire .bx i span{max-width:90%;padding:8px 16px;border-radius:10px;background:rgba(255,255,255,.92);border:1px solid #C9CED8;color:#334155;font:800 clamp(14px,1.8vw,22px)/1.2 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;text-align:center}.wire .bx.n i{display:none}.wire .bx.sm i span{padding:1px 8px;border-radius:6px;font-size:12px}'
    + '.p{position:fixed;top:0;right:0;bottom:0;width:min(440px,100vw);display:flex;flex-direction:column;background:#0F172A;color:#E2E8F0;font:14px/1.45 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;box-shadow:-12px 0 40px rgba(0,0,0,.35);z-index:2}'
    + '.hd{display:flex;align-items:center;gap:10px;padding:14px 16px 10px}.hd h2{flex:1;margin:0;font-size:16px;font-weight:800;color:#fff}.hd h2 span{color:#22D3EE}'
    + '.x{width:32px;height:32px;border:0;border-radius:8px;background:#1E293B;color:#fff;font:700 16px/32px system-ui;cursor:pointer}.x:hover{background:#334155}'
    + '.tg{display:flex;gap:14px;padding:0 16px 10px;font-size:13px;color:#94A3B8}.tg label{display:flex;align-items:center;gap:6px;cursor:pointer}.tg input{accent-color:#22D3EE;width:16px;height:16px;margin:0}'
    + '.tabs{display:flex;flex-wrap:wrap;gap:0 2px;padding:0 10px;border-bottom:1px solid #1E293B}.tabs button{flex:none;padding:9px 10px;border:0;border-bottom:2px solid transparent;background:none;color:#94A3B8;font:600 13px system-ui,sans-serif;cursor:pointer}'
    + '.tabs button[aria-selected=true]{color:#fff;border-bottom-color:#22D3EE}.tabs button:hover{color:#fff}'
    + '.bd{flex:1;overflow:auto;padding:12px 16px 40px}h3{margin:18px 0 8px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748B}h3:first-child{margin-top:4px}'
    + 'code,.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px}'
    + '.it{margin:0 0 6px;border:1px solid #1E293B;border-radius:10px;background:#111C33}.it.n{margin-left:18px;border-style:dashed}.it.sel{border-color:#7C3AED;box-shadow:0 0 0 1px #7C3AED}'
    + '.it>button{display:flex;width:100%;align-items:center;gap:8px;padding:9px 10px;border:0;background:none;color:inherit;font:inherit;text-align:left;cursor:pointer}'
    + '.no{flex:none;display:grid;place-items:center;min-width:24px;height:24px;padding:0 5px;border-radius:6px;background:#7C3AED;color:#fff;font:700 11px ui-monospace,monospace}.it.n .no{background:#0891B2}'
    + '.nm{flex:1;min-width:0}.nm code{color:#fff;font-weight:700}.nm small{display:block;color:#94A3B8;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}'
    + '.chip{flex:none;padding:2px 8px;border-radius:999px;background:#1E293B;color:#A5F3FC;font:600 11px ui-monospace,monospace}'
    + '.dt{display:none;padding:2px 12px 12px;font-size:13px}.it.open .dt{display:block}.dt p{margin:6px 0;color:#CBD5E1}'
    + '.kv{display:grid;grid-template-columns:auto 1fr;gap:4px 12px;margin:6px 0}.kv span:nth-child(odd){color:#94A3B8}.kv span:nth-child(even){min-width:0;overflow-wrap:anywhere}'
    + 'table{width:100%;border-collapse:collapse;margin:6px 0;font-size:12px}th,td{padding:5px 6px;border-bottom:1px solid #1E293B;text-align:left;vertical-align:top;overflow-wrap:anywhere}th{color:#64748B;font-weight:600}'
    + 'td:nth-child(-n+2){white-space:nowrap;overflow-wrap:normal}td.v{color:#E2E8F0}td.e{color:#475569;font-style:italic}.req{color:#F472B6}'
    + 'ul.g{margin:4px 0;padding-left:18px;color:#CBD5E1}.sb{display:inline-flex;margin-top:4px;padding:6px 10px;border:1px solid #334155;border-radius:8px;background:#1E293B;color:#fff;font:600 12px system-ui,sans-serif;cursor:pointer}.sb:hover{border-color:#22D3EE}'
    + '.pg{margin:0 0 8px;padding:10px;border:1px solid #1E293B;border-radius:10px;background:#111C33}.pg.cur{border-color:#22D3EE}.pg a{color:#fff;font-weight:700;text-decoration:none}.pg a:hover{text-decoration:underline}'
    + '.pg .f{color:#94A3B8;font-size:12px}.pg .cs{display:flex;flex-wrap:wrap;gap:4px;margin-top:8px}'
    + '.ph{display:flex;align-items:center;justify-content:space-between;gap:8px}.ph b{color:#fff}.ph .sb{flex:none;margin:0;text-decoration:none}'
    + 'button.chip{border:1px solid transparent;cursor:pointer;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}button.chip:hover{border-color:#22D3EE;color:#fff}'
    + '.sw{display:flex;align-items:center;gap:8px;padding:4px 0;border-bottom:1px solid #16213A}.sw i{flex:none;width:22px;height:22px;border-radius:6px;border:1px solid #334155}.sw code{flex:1;min-width:0;overflow-wrap:anywhere}.sw span{color:#94A3B8;font:12px ui-monospace,monospace;text-align:right;max-width:45%;overflow-wrap:anywhere}'
    + 'textarea{width:100%;height:220px;margin:6px 0;padding:10px;border:1px solid #334155;border-radius:8px;background:#020617;color:#E2E8F0;font:12px/1.45 ui-monospace,Menlo,Consolas,monospace;resize:vertical}'
    + '.note{color:#94A3B8;font-size:12px}a{color:#67E8F9}'
    + '@media (max-width:700px){.p{top:auto;height:70vh;width:100vw;border-radius:16px 16px 0 0}}';

  function blockMeta(b) { return D.blocks[b] || { w: 'sx/' + b, t: b, d: '', r: '', g: [], vs: [], f: [] }; }
  function val(v) {
    if (v === true) return 'true'; if (v === false) return 'false';
    if (v && typeof v === 'object') return v._items != null ? '[' + v._items + ' items]' : JSON.stringify(v);
    return String(v);
  }

  function viewSections() {
    var h = '<h3>' + esc(T.sections) + ' · ' + items.filter(function (x) { return x.d.n === 0; }).length + ' ' + esc(T.blocks) + '</h3>';
    items.forEach(function (x, k) {
      var m = blockMeta(x.d.b), rows = '';
      m.f.forEach(function (f) {
        var has = Object.prototype.hasOwnProperty.call(x.d.p, f[0]);
        rows += '<tr><td><code>' + esc(f[0]) + '</code>' + (f[3] ? '<span class="req"> *</span>' : '') + '</td><td>' + esc(f[1]) + '</td><td class="' + (has ? 'v' : 'e') + '">' + esc(has ? val(x.d.p[f[0]]) : T.none) + '</td></tr>';
      });
      h += '<div class="it' + (x.d.n ? ' n' : '') + '" data-k="' + k + '"><button type="button" aria-expanded="false"><span class="no">' + (k + 1) + '</span>'
        + '<span class="nm"><code>' + esc(m.w) + '</code><small>' + esc(m.s || m.t) + '</small></span><span class="chip">' + esc(x.d.v) + '</span></button>'
        + '<div class="dt">' + (m.d ? '<p>' + esc(m.d) + '</p>' : '')
        + '<div class="kv"><span>' + esc(T.role) + '</span><span><code>' + esc(m.r) + '</code></span><span>' + esc(T.variant) + '</span><span><code>' + esc(x.d.v) + '</code></span>'
        + '<span>' + esc(T.variants) + '</span><span><code>' + esc(m.vs.join(', ')) + '</code></span><span>CSS</span><span><code>' + esc('.' + [].slice.call(x.el.classList, 0, 3).join(' .')) + '</code></span><span>HTML</span><span><code>&lt;' + x.el.tagName.toLowerCase() + '&gt;</code></span></div>'
        + (m.g.length ? '<h3>' + esc(T.guard) + '</h3><ul class="g">' + m.g.map(function (g) { return '<li>' + esc(g) + '</li>'; }).join('') + '</ul>' : '')
        + (rows ? '<h3>' + esc(T.fields) + '</h3><table><tr><th>name</th><th>type</th><th>' + esc(T.value) + '</th></tr>' + rows + '</table>' : '')
        + '<button type="button" class="sb" data-show="' + k + '">' + esc(T.show) + ' ↗</button></div></div>';
    });
    return h;
  }

  function viewSite() {
    var h = '<h3>' + esc(T.site) + ' · ' + D.pages.length + '</h3>';
    D.pages.forEach(function (p) {
      var cur = p.f === D.file;
      h += '<div class="pg' + (cur ? ' cur' : '') + '"><div class="ph"><b>' + esc(p.t) + '</b>'
        + (cur ? '' : '<a class="sb" href="' + esc(p.f) + '?sxdev=1">' + esc(T.go) + ' →</a>') + '</div>'
        + '<div class="f"><code>' + esc(p.f) + '</code>' + (cur ? ' · ' + esc(T.current) : '') + '</div><div class="cs">'
        + p.b.map(function (b, o) {
          var s2 = b.split(':'), m = blockMeta(s2[0]);
          return '<button type="button" class="chip go" data-f="' + esc(p.f) + '" data-o="' + o + '" title="' + esc(m.w + ' · ' + s2[1]) + '">' + (o + 1) + ' · ' + esc(m.s || s2[0]) + '</button>';
        }).join('') + '</div></div>';
    });
    return h;
  }

  function cssVars() {
    var out = {};
    [].forEach.call(document.styleSheets, function (ss) {
      var rules; try { rules = ss.cssRules; } catch (e) { return; }
      [].forEach.call(rules || [], function (r) {
        if (r.selectorText !== ':root' || !r.style) return;
        for (var i = 0; i < r.style.length; i++) { var n = r.style[i]; if (n.indexOf('--') === 0) out[n] = r.style.getPropertyValue(n).trim(); }
      });
    });
    return out;
  }
  function viewStyles() {
    var v = cssVars(), keys = Object.keys(v), cs = getComputedStyle(root), h = '<h3>' + esc(T.prefix) + '</h3><p><code>.' + esc(D.prefix) + '…</code></p>';
    if (D.dialect) h += '<h3>' + esc(T.dialect) + '</h3><p class="note">' + Object.keys(D.dialect).map(function (k) { return esc(k) + ': <code>' + esc(String(D.dialect[k])) + '</code>'; }).join(' · ') + '</p>';
    var fonts = [].map.call(document.querySelectorAll('link[href*="fonts.googleapis"]'), function (l) { return decodeURIComponent((l.href.match(/family=[^&]+/g) || []).join(' · ').replace(/family=/g, '').replace(/\+/g, ' ')); });
    if (fonts.length) h += '<h3>' + esc(T.fonts) + '</h3><p class="note">' + esc(fonts.join(' · ')) + '</p>';
    h += '<h3>' + esc(T.tokens) + ' · ' + keys.length + '</h3>';
    keys.forEach(function (k) {
      var raw = v[k], res = cs.getPropertyValue(k).trim() || raw, isColor = /^(#|rgb|hsl|oklch|color-mix)/i.test(res);
      h += '<div class="sw">' + (isColor ? '<i style="background:' + esc(res) + '"></i>' : '<i style="border-style:dashed;opacity:.4"></i>') + '<code>' + esc(k) + '</code><span>' + esc(raw.slice(0, 60)) + '</span></div>';
    });
    return h;
  }

  function markup() {
    return items.filter(function (x) { return x.d.n === 0; }).map(function (x) {
      var m = blockMeta(x.d.b), data = {}, defs = {};
      m.f.forEach(function (f) { defs[f[0]] = f; });
      Object.keys(x.d.p).forEach(function (k) {
        var v = x.d.p[k], f = defs[k];
        if (!f || (v && typeof v === 'object')) return;
        if (f[4] !== null && f[4] !== undefined && String(f[4]) === String(v) && k !== 'variant') return;
        data[k] = v; data['_' + k] = 'field_' + m.w.replace('/', '_').replace(/-/g, '_') + '_' + k;
      });
      if (!data.variant) { data.variant = x.d.v; data._variant = 'field_' + m.w.replace('/', '_').replace(/-/g, '_') + '_variant'; }
      return '<!-- wp:' + m.w + ' ' + JSON.stringify({ name: m.w, data: data, mode: 'preview' }) + ' /-->';
    }).join('\n\n');
  }
  function spec() {
    return JSON.stringify({ file: D.file, title: document.title, prefix: D.prefix, sections: items.map(function (x) { return { block: blockMeta(x.d.b).w, variant: x.d.v, nested: !!x.d.n, fields: x.d.p }; }), pages: D.pages }, null, 2);
  }
  function viewWp() {
    return '<h3>' + esc(T.markup) + '</h3><p class="note">' + esc(T.markupNote) + '</p><textarea readonly id="mk" spellcheck="false">' + esc(markup()) + '</textarea>'
      + '<button type="button" class="sb" data-copy="mk">' + esc(T.copy) + '</button>'
      + '<h3>' + esc(T.spec) + '</h3><textarea readonly id="sp" spellcheck="false">' + esc(spec()) + '</textarea><button type="button" class="sb" data-copy="sp">' + esc(T.copy) + '</button>';
  }

  var siteText = null;
  function viewAi() {
    var h = '<p class="note">' + esc(T.aiNote) + '</p>'
      + '<h3>' + esc(T.sys) + '</h3><p class="note">' + esc(T.sysNote) + '</p><textarea readonly id="sy" spellcheck="false" style="height:150px">' + esc(D.system || '') + '</textarea>'
      + '<button type="button" class="sb" data-copy="sy">' + esc(T.copy) + '</button>'
      + '<h3>' + esc(T.aiPage) + '</h3><textarea readonly id="ab" spellcheck="false">' + esc(D.brief) + '</textarea>'
      + '<button type="button" class="sb" data-copy="ab">' + esc(T.copy) + '</button>';
    if (D.siteBrief) {
      h += '<h3>' + esc(T.aiSite) + '</h3><textarea readonly id="as" spellcheck="false">' + esc(siteText || T.loading) + '</textarea>'
        + '<button type="button" class="sb" data-copy="as">' + esc(T.copy) + '</button> <a class="sb" href="' + esc(D.siteBrief) + '" style="text-decoration:none">' + esc(T.openFile) + ' ↗</a>';
      if (siteText === null) {
        // the site brief is a file published next to the pages; read it once
        fetch(D.siteBrief).then(function (r) { return r.ok ? r.text() : Promise.reject(); }).then(function (t) {
          siteText = t; var ta = sh.getElementById('as'); if (ta) ta.value = t;
        }, function () { var ta = sh.getElementById('as'); if (ta) ta.value = ''; });
      }
    }
    return h;
  }

  var VIEWS = { sections: viewSections, site: viewSite, styles: viewStyles, wp: viewWp, ai: viewAi };
  function draw() {
    [].forEach.call(sh.querySelectorAll('.tabs button'), function (b) { b.setAttribute('aria-selected', String(b.dataset.t === tab)); });
    sh.querySelector('.bd').innerHTML = VIEWS[tab]();
    sh.querySelector('.bd').scrollTop = 0;
  }

  function place() {
    if (!open) return;
    var h = '', sx = window.scrollX, sy = window.scrollY;
    items.forEach(function (x, k) {
      var r = x.el.getBoundingClientRect();
      if (!r.width || !r.height || getComputedStyle(x.el).position === 'fixed') return; // overlays (age gate, sticky bar) stay in the list only
      var m = blockMeta(x.d.b);
      h += '<div class="bx' + (x.d.n ? ' n' : '') + (r.height < 64 ? ' sm' : '') + '" data-k="' + k + '" style="left:' + (r.left + sx) + 'px;top:' + (r.top + sy) + 'px;width:' + r.width + 'px;height:' + r.height + 'px">'
        + '<b data-k="' + k + '">' + (k + 1) + ' · ' + esc(m.w.replace(/^sx\//, '')) + ' / ' + esc(x.d.v) + '</b>' + (x.d.n ? '' : '<i><span>' + esc(m.s || m.t) + '</span></i>') + '</div>';
    });
    boxes.innerHTML = h;
  }
  var raf = 0;
  function later() { if (!raf) raf = requestAnimationFrame(function () { raf = 0; place(); }); }

  function topIndex(o) { var t = -1; for (var k = 0; k < items.length; k++) { if (items[k].d.n === 0 && ++t === o) return k; } return -1; }

  function select(k, scroll) {
    if (tab !== 'sections') { tab = 'sections'; draw(); }
    [].forEach.call(sh.querySelectorAll('.it'), function (e) { var on = +e.dataset.k === k; e.classList.toggle('sel', on); if (on) { e.classList.add('open'); e.firstChild.setAttribute('aria-expanded', 'true'); e.scrollIntoView({ block: 'nearest' }); } });
    [].forEach.call(boxes.querySelectorAll('.bx'), function (e) { e.classList.toggle('hl', +e.dataset.k === k); });
    if (scroll) items[k].el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function build() {
    host = document.createElement('div');
    host.id = 'sx-devhost';
    host.style.cssText = 'position:absolute;left:0;top:0;width:0;height:0;z-index:2147483500';
    sh = host.attachShadow({ mode: 'open' });
    var tabs = ['sections', 'site', 'styles', 'wp', 'ai'].map(function (t) { return '<button type="button" role="tab" data-t="' + t + '">' + esc(T[t]) + '</button>'; }).join('');
    sh.innerHTML = '<style>' + CSS + '</style><div class="boxes"></div><aside class="p" role="dialog" aria-label="' + esc(T.title) + '">'
      + '<div class="hd"><h2><span>&lt;/&gt;</span> ' + esc(T.title) + '</h2><button type="button" class="x" aria-label="' + esc(T.close) + '">✕</button></div>'
      + '<div class="tg"><label><input type="checkbox" id="wire"> ' + esc(T.wire) + '</label><label><input type="checkbox" id="lab" checked> ' + esc(T.labels) + '</label></div>'
      + '<div class="tabs" role="tablist">' + tabs + '</div><div class="bd"></div></aside>';
    boxes = sh.querySelector('.boxes');
    root.appendChild(host);
    sh.querySelector('.x').onclick = close;
    sh.getElementById('wire').onchange = function () { root.classList.toggle('sxwire', this.checked); boxes.classList.toggle('wire', this.checked); later(); };
    sh.getElementById('lab').onchange = function () { boxes.classList.toggle('nolabels', !this.checked); };
    sh.querySelector('.tabs').onclick = function (e) { var b = e.target.closest('button'); if (b) { tab = b.dataset.t; draw(); } };
    boxes.onclick = function (e) { var b = e.target.closest('b'); if (b) select(+b.dataset.k, false); };
    sh.querySelector('.bd').onclick = function (e) {
      var g = e.target.closest('.go');
      if (g) { // a section of the site: this page scrolls to it, another page opens there
        if (g.dataset.f === D.file) { var k = topIndex(+g.dataset.o); if (k >= 0) select(k, true); }
        else location.href = g.dataset.f + '?sxdev=1&sxs=' + g.dataset.o;
        return;
      }
      var s = e.target.closest('[data-show]'), c = e.target.closest('[data-copy]'), it = e.target.closest('.it>button');
      if (s) { select(+s.dataset.show, true); return; }
      if (c) {
        var ta = sh.getElementById(c.dataset.copy), done = function () { c.textContent = T.copied; setTimeout(function () { c.textContent = T.copy; }, 1500); };
        ta.select();
        if (navigator.clipboard) navigator.clipboard.writeText(ta.value).then(done, function () { try { document.execCommand('copy'); done(); } catch (x) {} });
        else { try { document.execCommand('copy'); done(); } catch (x) {} }
        return;
      }
      if (it) { var p = it.parentNode, o = !p.classList.contains('open'); p.classList.toggle('open', o); it.setAttribute('aria-expanded', String(o)); [].forEach.call(boxes.querySelectorAll('.bx'), function (b) { b.classList.toggle('hl', o && b.dataset.k === p.dataset.k); }); }
    };
  }

  function show() {
    if (!host) { scan(); build(); }
    open = true;
    root.classList.add('sxdev-on');
    if (innerWidth > 1100) root.classList.add('sxdev-side');
    host.style.display = '';
    draw();
    place();
    if (window.ResizeObserver && !ro) { ro = new ResizeObserver(later); ro.observe(document.body); }
    sh.querySelector('.x').focus();
  }
  function close() {
    open = false;
    root.classList.remove('sxdev-on', 'sxdev-side', 'sxwire');
    if (sh) { sh.getElementById('wire').checked = false; boxes.classList.remove('wire'); }
    if (host) host.style.display = 'none';
    if (btn) btn.focus();
  }

  if (btn) btn.addEventListener('click', show);
  addEventListener('resize', later);
  addEventListener('load', later);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && open) close(); });
  // keep the skeleton open when following the site's own page links
  document.addEventListener('click', function (e) {
    if (!open || e.defaultPrevented) return;
    var a = e.target.closest && e.target.closest('a[href]');
    if (!a || ui(a)) return;
    var href = a.getAttribute('href');
    if (/^[\w-]+\.html$/.test(href)) { e.preventDefault(); location.href = href + '?sxdev=1'; }
  }, true);
  if (/[?&]sxdev=1/.test(location.search)) {
    show();
    var want = /[?&]sxs=(\d+)/.exec(location.search);
    // after the review nav script has brought the page top into view
    if (want) setTimeout(function () { var k = topIndex(+want[1]); if (k >= 0) select(k, true); }, 600);
  }
})();
