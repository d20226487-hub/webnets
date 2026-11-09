/* Site types pages of the generator: list, copy, edit a type's blueprint (pages, sections, order rules, angles, frame)
   with what each part does, test builds, history. Inlined by index.php; uses window.GEN (api, esc, L, fail, main, RU). */
(function () {
  var G = window.GEN, esc = G.esc, L = G.L, main = G.main;
  Object.assign(G.RU, {
    'Site types': 'Типы сайтов', 'Copy a type': 'Скопировать тип', 'Type': 'Тип', 'Pages': 'Страницы', 'Sections': 'Секции', 'Angles': 'Углы подачи',
    'copy of {0}': 'копия: {0}', 'hidden': 'скрыт', 'needs {0}': 'нужно: {0}',
    'A site type is the plan every site of that type follows: its pages, the sections on each page, which are required, which designs they may use, the rules for their order, and the angles (themes) a site can take. Built-in types can be changed and hidden; copies can also be deleted. A copy works like the type it was copied from, with its own plan.':
      'Тип сайта — это план, по которому собирается каждый сайт этого типа: страницы, секции на каждой странице, какие из них обязательны, какие дизайны им можно использовать, правила порядка и углы подачи (темы), которые может выбрать сайт. Встроенные типы можно менять и скрывать; копии можно ещё и удалять. Копия работает как тип, с которого её сняли, но со своим планом.',
    'New site type': 'Новый тип сайта', 'Type id': 'Код типа', 'Lowercase letters, digits and dashes, e.g. satellite-short. It cannot be changed later.': 'Строчные латинские буквы, цифры и дефисы, например satellite-short. Потом не изменить.',
    'Name in the job form': 'Название в форме задания', 'The copy starts with every page, section, rule and angle of this type, and its AI notes. It builds from the same demo content, so it can drop and rearrange what that type has.':
      'Копия начинается со всех страниц, секций, правил и углов подачи этого типа и его заметок для ИИ. Она собирается из того же демо-контента, поэтому может убирать и переставлять то, что есть у исходного типа.',
    'Hidden from the job form': 'Скрыт в форме задания', 'Create type': 'Создать тип', '← Site types': '← Типы сайтов',
    'Hide from the job form': 'Скрыть в форме задания', 'Show in the job form': 'Показать в форме задания', 'Test build now': 'Пробная сборка', 'Delete type': 'Удалить тип',
    'Built-in types cannot be deleted. Hide it instead.': 'Встроенные типы нельзя удалить. Скройте его.',
    'Delete type {0}? Its plan and AI notes are kept in the history.': 'Удалить тип {0}? План и заметки для ИИ останутся в истории.',
    'Every save is test-built first (demo market, every option of the job form). If the type no longer builds, nothing is saved.':
      'Каждое сохранение сначала проверяется пробной сборкой (демо-рынок, каждый вариант формы задания). Если тип перестаёт собираться, ничего не сохраняется.',
    'Saving: test builds running…': 'Сохраняем: идут пробные сборки…', 'Test builds running…': 'Идут пробные сборки…', 'Builds: OK': 'Сборка: OK', 'These options no longer build: {0}': 'Эти варианты больше не собираются: {0}',
    'Markets': 'Рынки', 'not checked yet': 'ещё не проверено', '{0} built sites were made before the last change. Rebuild their jobs to apply it.': 'Сайтов, собранных до последнего изменения: {0}. Пересоберите их задания, чтобы применить изменения.',
    'Name and requirements': 'Название и требования', 'Title': 'Заголовок', 'Internal title of the plan (shown in docs and AI briefs).': 'Внутреннее название плана (в документации и ИИ-брифах).',
    'Shown in the job form and the job page.': 'Показывается в форме задания и на странице задания.', 'Needs vertical': 'Нужна вертикаль',
    'The whole type is built only in markets that allow it.': 'Весь тип собирается только на рынках, где она разрешена.', 'nothing': 'ничего',
    'Page': 'Страница', 'Home page': 'Главная', 'Required or optional': 'Обязательна или нет', 'required': 'обязательна', 'optional': 'по желанию',
    'Optional pages are built when the site\'s angle asks for them, or when a market requires them.': 'Необязательные страницы создаются, если их просит угол подачи сайта или требует рынок.',
    'Address': 'Адрес', 'The page\'s file name: {0}.html': 'Имя файла страницы: {0}.html', 'Conditions': 'Условия', 'Required in markets': 'Обязательна на рынках',
    'needs the brand to have an app': 'нужно приложение бренда', 'needs an allowed promotion': 'нужна разрешённая акция', 'one page per item': 'по странице на каждый элемент',
    'Remove page': 'Убрать страницу', 'Remove this page? Links to it disappear from every site of this type.': 'Убрать эту страницу? Ссылки на неё пропадут со всех сайтов этого типа.',
    'Section': 'Секция', 'Block': 'Блок', 'Zone': 'Зона', 'Rule': 'Правило', 'Designs': 'Дизайны', 'top': 'верх', 'middle': 'середина', 'bottom': 'низ',
    'Top: the first screen, in this order. Middle: the order is picked per site, by the rules below. Bottom: last, in this order. Optional sections appear when the angle asks for them.':
      'Верх — первый экран, в этом порядке. Середина — порядок выбирается для каждого сайта по правилам ниже. Низ — в конце, в этом порядке. Необязательные секции появляются, если их просит угол подачи.',
    'fixed: {0}': 'фиксирован: {0}', 'all designs': 'все дизайны', 'Remove': 'Убрать', 'Move up': 'Выше', 'Move down': 'Ниже',
    'Add a section back': 'Вернуть секцию', 'Sections this type had, or its base type has, that are not on this page.': 'Секции, которые были у этого типа или есть у базового, но отсутствуют на этой странице.',
    'Order rules': 'Правила порядка', 'before': 'раньше, чем', 'not first': 'не первой', 'Add rule': 'Добавить правило',
    '{0} possible orders of the middle sections (as saved).': 'Возможных порядков секций середины: {0} (по сохранённому плану).',
    'On top of these rules: no two conversion blocks in a row and no two tinted bands in a row, and one pattern per site.': 'Сверх этих правил: не две конверсионные секции подряд, не две тонированные полосы подряд и один шаблон на сайт.',
    'An angle is a theme a site can take: which optional sections it shows, which designs its sections may use, which pages it adds. Each site gets one, picked by its seed among the angles its market allows.':
      'Угол подачи — тема сайта: какие необязательные секции он показывает, какие дизайны разрешены его секциям, какие страницы добавляет. Каждый сайт получает один угол, выбранный по seed среди разрешённых на его рынке.',
    'Home page angles': 'Углы подачи главной', 'Shows optional sections': 'Показывает необязательные секции', 'Limits designs': 'Ограничивает дизайны', 'Adds pages': 'Добавляет страницы',
    'Needs': 'Нужно', 'limit designs of…': 'ограничить дизайны секции…', 'Remove angle': 'Убрать угол', 'Copy as a new angle': 'Скопировать как новый угол', 'New angle id': 'Код нового угла',
    'Frame': 'Рамка', 'Header and footer designs this type may use. The rest of the frame (compliance bar, age check, sticky button) follows the market.':
      'Дизайны шапки и подвала, которые может использовать тип. Остальная рамка (полоса соответствия, проверка возраста, плавающая кнопка) зависит от рынка.',
    'Header': 'Шапка', 'Footer': 'Подвал', 'Added to every AI brief for sites of this type.': 'Добавляется в каждый ИИ-бриф для сайтов этого типа.',
    'Every field of the plan. Edit here for anything the form does not cover, then use this JSON and save.': 'Все поля плана. Редактируйте здесь то, чего нет в форме, затем примените JSON и сохраните.',
    'Recent changes': 'Последние изменения', 'Built-in': 'Встроенный', 'Used by': 'Где используется', 'Last change': 'Последнее изменение', 'Jobs': 'Задания', 'Demos': 'Демо', 'None.': 'Нет.',
    'History': 'История', 'Save changes': 'Сохранить изменения', 'Unsaved changes': 'Есть несохранённые изменения', 'Saved.': 'Сохранено.', 'Changed: {0}': 'Изменено: {0}', 'No changes to save.': 'Нет изменений.',
    'Use this JSON': 'Применить JSON', 'Not valid JSON: {0}': 'Неверный JSON: {0}', 'JSON applied to the form. Save to keep it.': 'JSON применён к форме. Сохраните, чтобы закрепить.',
    'All of it as JSON': 'Всё в виде JSON', 'AI notes': 'Заметки для ИИ', 'Time': 'Время', 'Action': 'Действие', 'What changed': 'Что изменилось', 'No changes yet.': 'Изменений пока нет.',
    'created from {0}': 'создан из {0}', 'edited': 'изменён', 'deleted': 'удалён', 'Show': 'Показать', 'Hide': 'Скрыть', 'Version before': 'Версия до',
    'Differs from now in: {0}': 'Отличается от текущей: {0}', 'Same as now.': 'Совпадает с текущей.', 'Put this version back': 'Вернуть эту версию',
    'Put this version back? The current one is kept in the history.': 'Вернуть эту версию? Текущая сохранится в истории.',
    'Every save keeps the previous version. Open one to see what differs from now, or put it back.': 'Каждое сохранение хранит предыдущую версию. Откройте её, чтобы увидеть отличия от текущей, или верните.'
  });
  var M = null, D = null, dirty = false, page = null;
  var chip = function (t, cls) { return '<span class="chip' + (cls ? ' ' + cls : '') + '">' + t + '</span>'; };
  var pagesOf = function (bp) { return bp.pages ? bp.pages : { '': bp }; };
  var act = function (a) { return a.replace(/^created from (.+)$/, function (m, x) { return L('created from {0}', x); }).replace(/^(edited|deleted)$/, function (m) { return L(m); }); };
  var api = function (path, body, method) {
    return fetch('/generator/api/' + path, body || method ? { method: method || 'POST', headers: { 'Content-Type': 'application/json' }, body: body ? JSON.stringify(body) : undefined } : {})
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) { var e = new Error(j.error || r.statusText); e.fields = j.fields; e.probe = j.probe; throw e; } return j; }); });
  };
  function logTable(log, withType) {
    if (!log.length) return '<p class="muted">' + L('No changes yet.') + '</p>';
    return '<div class="card" style="padding:0;overflow-x:auto"><table><thead><tr><th>' + L('Time') + '</th>' + (withType ? '<th>' + L('Type') + '</th>' : '') + '<th>' + L('Action') + '</th><th>' + L('What changed') + '</th></tr></thead><tbody>'
      + log.map(function (x) {
        return '<tr><td class="muted" style="white-space:nowrap">' + esc(x.time.replace('T', ' ').slice(0, 16)) + '</td>' + (withType ? '<td><a href="#/types/' + esc(x.type) + '">' + esc(x.type) + '</a></td>' : '')
          + '<td>' + esc(act(x.action)) + '</td><td style="font-family:ui-monospace,monospace;font-size:12px">' + esc(x.changes.slice(0, 10).join(', ') + (x.changes.length > 10 ? ' …' : '')) + '</td></tr>';
      }).join('') + '</tbody></table></div>';
  }

  /* ---------- list */
  function list() {
    api('types').then(function (d) {
      main.innerHTML = '<div class="row"><h2>' + L('Site types') + '</h2><span class="sp"></span><a class="btn primary" href="#/types/new">' + L('Copy a type') + '</a></div>'
        + '<p class="muted" style="max-width:860px">' + L('A site type is the plan every site of that type follows: its pages, the sections on each page, which are required, which designs they may use, the rules for their order, and the angles (themes) a site can take. Built-in types can be changed and hidden; copies can also be deleted. A copy works like the type it was copied from, with its own plan.') + '</p>'
        + '<div class="card" style="padding:0;overflow-x:auto"><table><thead><tr><th>' + L('Type') + '</th><th>' + L('Pages') + '</th><th>' + L('Sections') + '</th><th>' + L('Angles') + '</th><th>' + L('Used by') + '</th><th>' + L('Last change') + '</th></tr></thead><tbody>'
        + d.types.map(function (x) {
          return '<tr><td><a href="#/types/' + x.id + '"><b>' + esc(L(x.label)) + '</b></a> ' + chip(esc(x.id)) + (x.copy ? ' ' + chip(L('copy of {0}', esc(x.base)), 'auto') : '') + (x.hidden ? ' ' + chip(L('hidden'), 'warn') : '')
            + (x.requires_vertical ? ' ' + chip(L('needs {0}', esc(x.requires_vertical))) : '') + '</td><td>' + x.pages + '</td><td>' + x.sections + '</td><td>' + x.angles + '</td>'
            + '<td>' + x.jobs + ' / ' + x.sites + (x.demos ? '<br><span class="muted">' + L('Demos') + ': ' + x.demos + '</span>' : '') + '</td><td class="muted">' + esc(x.changed) + '</td></tr>';
        }).join('') + '</tbody></table></div><p class="muted" style="font-size:13px">' + L('Used by') + ': ' + L('Jobs').toLowerCase() + ' / sites</p>'
        + '<h3>' + L('Recent changes') + '</h3>' + logTable(d.log, true);
    }).catch(G.fail);
  }

  /* ---------- copy */
  function create() {
    api('types').then(function (d) {
      main.innerHTML = '<p><a href="#/types">' + L('← Site types') + '</a></p><h2>' + L('New site type') + '</h2>'
        + '<form class="card" id="tnew" style="max-width:640px">'
        + '<div class="field"><label class="lbl" for="tid">' + L('Type id') + '</label><input type="text" id="tid" required style="width:240px"><span class="muted">' + L('Lowercase letters, digits and dashes, e.g. satellite-short. It cannot be changed later.') + '</span></div>'
        + '<div class="field"><label class="lbl" for="tlabel">' + L('Name in the job form') + '</label><input type="text" id="tlabel" required></div>'
        + '<div class="field"><label class="lbl" for="tfrom">' + L('Copy a type') + '</label><select id="tfrom">' + d.types.map(function (x) { return '<option value="' + x.id + '">' + esc(L(x.label)) + ' (' + x.id + ')</option>'; }).join('') + '</select><span class="muted">' + L('The copy starts with every page, section, rule and angle of this type, and its AI notes. It builds from the same demo content, so it can drop and rearrange what that type has.') + '</span></div>'
        + '<div class="field"><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" id="thid"> <span class="lbl" style="margin:0">' + L('Hidden from the job form') + '</span></label></div>'
        + '<div class="row"><button class="primary">' + L('Create type') + '</button></div><p class="err" id="terr" hidden></p></form>';
      document.getElementById('tnew').onsubmit = function (e) {
        e.preventDefault();
        var id = document.getElementById('tid').value.trim().toLowerCase();
        api('types', { id: id, label: document.getElementById('tlabel').value, from: document.getElementById('tfrom').value, hidden: document.getElementById('thid').checked })
          .then(function () { location.hash = '#/types/' + id; })
          .catch(function (x) { var el = document.getElementById('terr'); el.hidden = false; el.textContent = L(x.message); });
      };
    }).catch(G.fail);
  }

  /* ---------- edit */
  function designs(block) { return (D.blocks[block] || {}).variants || []; }
  function needs(block, v) { var r = (D.blocks[block] || {}).requires || {}; return r[v] ? ' <small class="muted">(' + esc(r[v]) + ')</small>' : ''; }
  function sectionRows(pid, p) {
    var secs = p.sections || [];
    return '<div class="card tsec" style="padding:0;overflow-x:auto"><table><thead><tr><th></th><th>' + L('Section') + '</th><th>' + L('Zone') + '</th><th>' + L('Rule') + '</th><th>' + L('Designs') + '</th><th></th></tr></thead><tbody>'
      + secs.map(function (s, i) {
        var b = D.blocks[s.block] || { title: s.block }, all = designs(s.block), on = s.variants || all;
        var dz = s.variant ? chip(L('fixed: {0}', esc(s.variant))) : all.length ? '<div class="tdes">' + all.map(function (v) {
          return '<label class="inl"><input type="checkbox" data-a="var" data-pg="' + pid + '" data-i="' + i + '" data-v="' + esc(v) + '"' + (on.indexOf(v) >= 0 ? ' checked' : '') + '> ' + esc(v) + needs(s.block, v) + '</label>';
        }).join('') + '</div>' : '<span class="muted">—</span>';
        return '<tr data-f="' + (pid === '' ? '' : 'pages.' + pid + '.') + 'sections.' + i + '"><td style="white-space:nowrap"><button type="button" data-a="up" data-pg="' + pid + '" data-i="' + i + '" aria-label="' + L('Move up') + '">↑</button><button type="button" data-a="down" data-pg="' + pid + '" data-i="' + i + '" aria-label="' + L('Move down') + '">↓</button></td>'
          + '<td><b>' + esc(s.id) + '</b><div class="muted" style="font-size:12px">' + esc(b.title) + ' · <code>' + esc(s.block) + '</code></div><span class="ferr" hidden></span></td>'
          + '<td><select data-a="zone" data-pg="' + pid + '" data-i="' + i + '">' + ['top', 'middle', 'bottom'].map(function (z) { return '<option value="' + z + '"' + (s.zone === z ? ' selected' : '') + '>' + L(z) + '</option>'; }).join('') + '</select></td>'
          + '<td><select data-a="rule" data-pg="' + pid + '" data-i="' + i + '">' + ['required', 'optional'].map(function (z) { return '<option value="' + z + '"' + (s.rule === z ? ' selected' : '') + '>' + L(z) + '</option>'; }).join('') + '</select></td>'
          + '<td>' + dz + '</td><td><button type="button" class="danger" data-a="rm" data-pg="' + pid + '" data-i="' + i + '">' + L('Remove') + '</button></td></tr>';
      }).join('') + '</tbody></table></div>';
  }
  function ruleRows(pid, p) {
    var mid = (p.sections || []).filter(function (s) { return s.zone === 'middle'; }).map(function (s) { return s.id; });
    var opt = function (v) { return mid.map(function (id) { return '<option value="' + esc(id) + '"' + (id === v ? ' selected' : '') + '>' + esc(id) + '</option>'; }).join(''); };
    return '<div class="trules" data-f="' + (pid === '' ? '' : 'pages.' + pid + '.') + 'order_rules">' + (p.order_rules || []).map(function (r, i) {
      return '<div class="row" data-f="' + (pid === '' ? '' : 'pages.' + pid + '.') + 'order_rules.' + i + '"><select data-a="ra" data-pg="' + pid + '" data-i="' + i + '">' + opt(r.a) + '</select>'
        + '<select data-a="rr" data-pg="' + pid + '" data-i="' + i + '"><option value="before"' + (r.rule === 'before' ? ' selected' : '') + '>' + L('before') + '</option><option value="not_first"' + (r.rule === 'not_first' ? ' selected' : '') + '>' + L('not first') + '</option></select>'
        + (r.rule === 'before' ? '<select data-a="rb" data-pg="' + pid + '" data-i="' + i + '">' + opt(r.b) + '</select>' : '')
        + '<button type="button" data-a="rrm" data-pg="' + pid + '" data-i="' + i + '">✕</button><span class="ferr" hidden></span></div>';
    }).join('') + '<span class="ferr" hidden></span></div>' + (mid.length > 1 ? '<button type="button" data-a="radd" data-pg="' + pid + '">' + L('Add rule') + '</button>' : '');
  }
  function pagePanel(pid) {
    var p = pagesOf(M)[pid], multi = !!M.pages, pre = multi ? 'pages.' + pid + '.' : '';
    var conds = [];
    if (p.requires_vertical) conds.push(L('needs {0}', esc(p.requires_vertical)));
    if (p.requires_app) conds.push(L('needs the brand to have an app'));
    if (p.requires_promo) conds.push(L('needs an allowed promotion'));
    if (p.requires_platform) conds.push(L('needs {0}', esc(p.requires_platform)));
    if (p.generate) conds.push(L('one page per item'));
    var inMarkets = Object.keys(D.market_pages).filter(function (m) { return D.market_pages[m].indexOf(pid) >= 0; });
    var spares = (D.spares[pid] || []).filter(function (s) { return !(p.sections || []).some(function (x) { return x.id === s.id; }); });
    var head = multi && pid !== 'home' ? '<div class="mgrid">'
      + '<div class="field" data-f="' + pre + 'rule"><span class="lbl">' + L('Required or optional') + '</span><select data-a="prule" data-pg="' + pid + '"><option value="required"' + (p.rule === 'required' ? ' selected' : '') + '>' + L('required') + '</option><option value="optional"' + (p.rule === 'optional' ? ' selected' : '') + '>' + L('optional') + '</option></select><span class="ferr" hidden></span></div>'
      + '<div class="field" data-f="' + pre + 'slug"><span class="lbl">' + L('Address') + '</span><input type="text" data-a="slug" data-pg="' + pid + '" value="' + esc(p.slug || '') + '" style="width:220px"><span class="muted mhelp">' + L('The page\'s file name: {0}.html', esc(p.slug || pid)) + '</span><span class="ferr" hidden></span></div></div>'
      + '<p class="muted mhelp">' + L('Optional pages are built when the site\'s angle asks for them, or when a market requires them.') + '</p>' : '';
    return '<section class="card msec" id="t-page"><div class="row"><h3 style="margin:0">' + (multi ? L('Page') + ': ' + esc(pid === 'home' ? L('Home page') : G.pageName(pid)) + ' <code class="muted">' + esc(pid) + '</code>' : L('Sections')) + '</h3><span class="sp"></span>'
      + (multi && pid !== 'home' && !p.generate ? '<button type="button" class="danger" data-a="prm" data-pg="' + pid + '">' + L('Remove page') + '</button>' : '') + '</div>'
      + (conds.length || inMarkets.length ? '<p>' + (conds.length ? '<b>' + L('Conditions') + ':</b> ' + conds.join(', ') + ' ' : '') + (inMarkets.length ? '<b>' + L('Required in markets') + ':</b> ' + esc(inMarkets.join(', ').toUpperCase()) : '') + '</p>' : '')
      + head
      + '<p class="muted mhelp">' + L('Top: the first screen, in this order. Middle: the order is picked per site, by the rules below. Bottom: last, in this order. Optional sections appear when the angle asks for them.') + '</p>'
      + sectionRows(pid, p)
      + (spares.length ? '<div class="row" style="margin-top:10px"><select id="tspare">' + spares.map(function (s) { return '<option value="' + esc(s.id) + '">' + esc(s.id) + ' (' + esc((D.blocks[s.block] || {}).title || s.block) + ')</option>'; }).join('') + '</select><button type="button" data-a="spare" data-pg="' + pid + '">' + L('Add a section back') + '</button><span class="muted mhelp">' + L('Sections this type had, or its base type has, that are not on this page.') + '</span></div>' : '')
      + '<h4>' + L('Order rules') + '</h4><p class="muted mhelp">' + (D.orders[pid] >= 0 ? L('{0} possible orders of the middle sections (as saved).', D.orders[pid]) + ' ' : '') + L('On top of these rules: no two conversion blocks in a row and no two tinted bands in a row, and one pattern per site.') + '</p>'
      + ruleRows(pid, p) + '</section>';
  }
  function anglePanel(where, angles, secs, label) {
    var ids = (secs || []).map(function (s) { return s.id; }), byId = {};
    (secs || []).forEach(function (s) { byId[s.id] = s; });
    var pageIds = M.pages ? Object.keys(M.pages).filter(function (x) { return x !== 'home'; }) : [];
    return '<h4>' + label + '</h4><div class="tangles">' + Object.keys(angles || {}).map(function (a) {
      var x = angles[a], pre = where + '.' + a;
      var opt = (secs || []).filter(function (s) { return s.rule === 'optional'; });
      var lim = Object.keys(x.variants || {});
      return '<div class="card tangle" data-f="' + pre + '"><div class="row"><b>' + esc(a) + '</b><span class="sp"></span>'
        + '<label class="inl">' + L('Needs') + ' <select data-a="areq" data-w="' + where + '" data-n="' + esc(a) + '"><option value="">' + L('nothing') + '</option><option value="sports"' + (x.requires_vertical === 'sports' ? ' selected' : '') + '>sports</option><option value="casino"' + (x.requires_vertical === 'casino' ? ' selected' : '') + '>casino</option></select></label>'
        + '<button type="button" data-a="acopy" data-w="' + where + '" data-n="' + esc(a) + '">' + L('Copy as a new angle') + '</button><button type="button" class="danger" data-a="arm" data-w="' + where + '" data-n="' + esc(a) + '">' + L('Remove angle') + '</button></div>'
        + (x.requires_promo ? '<p class="muted mhelp">' + L('needs an allowed promotion') + ': ' + esc(x.requires_promo.join(', ')) + '</p>' : '')
        + (opt.length ? '<div class="field" data-f="' + pre + '.optional"><span class="lbl">' + L('Shows optional sections') + '</span><div class="mchecks">' + opt.map(function (s) {
          return '<label class="inl"><input type="checkbox" data-a="aopt" data-w="' + where + '" data-n="' + esc(a) + '" data-s="' + esc(s.id) + '"' + ((x.optional || []).indexOf(s.id) >= 0 ? ' checked' : '') + '> ' + esc(s.id) + '</label>';
        }).join('') + '</div><span class="ferr" hidden></span></div>' : '')
        + (secs ? '<div class="field"><span class="lbl">' + L('Limits designs') + '</span>' + lim.map(function (sid) {
          var all = byId[sid] ? designs(byId[sid].block) : [], on = [].concat(x.variants[sid]);
          return '<div class="row tlim" data-f="' + pre + '.variants.' + sid + '"><code>' + esc(sid) + '</code>' + all.map(function (v) {
            return '<label class="inl"><input type="checkbox" data-a="avar" data-w="' + where + '" data-n="' + esc(a) + '" data-s="' + esc(sid) + '" data-v="' + esc(v) + '"' + (on.indexOf(v) >= 0 ? ' checked' : '') + '> ' + esc(v) + '</label>';
          }).join('') + '<button type="button" data-a="avrm" data-w="' + where + '" data-n="' + esc(a) + '" data-s="' + esc(sid) + '">✕</button><span class="ferr" hidden></span></div>';
        }).join('') + '<select data-a="avadd" data-w="' + where + '" data-n="' + esc(a) + '"><option value="">' + L('limit designs of…') + '</option>' + ids.filter(function (sid) { return lim.indexOf(sid) < 0 && designs(byId[sid].block).length > 1 && !byId[sid].variant; }).map(function (sid) { return '<option>' + esc(sid) + '</option>'; }).join('') + '</select></div>' : '')
        + (x.pages && pageIds.length ? '<div class="field" data-f="' + pre + '.pages"><span class="lbl">' + L('Adds pages') + '</span><div class="mchecks">' + pageIds.map(function (pid) {
          return '<label class="inl"><input type="checkbox" data-a="apage" data-w="' + where + '" data-n="' + esc(a) + '" data-s="' + esc(pid) + '"' + (x.pages.indexOf(pid) >= 0 ? ' checked' : '') + '> ' + esc(G.pageName(pid)) + '</label>';
        }).join('') + '</div><span class="ferr" hidden></span></div>' : '')
        + '</div>';
    }).join('') + '</div>';
  }
  function angleHolder(where) { return where === 'angles' ? (M.angles = M.angles || {}) : M.pages[where.split('.')[1]].angles; }
  function framePanel() {
    var f = M.frame || {};
    var row = function (key, label) {
      var x = f[key]; if (!x || !x.block) return '';
      var all = designs(x.block), on = x.variants || all;
      return '<div class="field"><span class="lbl">' + label + ' <code class="muted">' + esc(x.block) + '</code></span><div class="mchecks">' + all.map(function (v) {
        return '<label class="inl"><input type="checkbox" data-a="frame" data-k="' + key + '" data-v="' + esc(v) + '"' + (on.indexOf(v) >= 0 ? ' checked' : '') + '> ' + esc(v) + '</label>';
      }).join('') + '</div></div>';
    };
    return row('header', L('Header')) + row('footer', L('Footer'));
  }
  function draw(keep) {
    var scroll = window.scrollY, u = D.usage, c = D.catalog, t = D.type, pids = Object.keys(pagesOf(M));
    if (page === null || pids.indexOf(page) < 0) page = pids[0];
    M.generator = M.generator || {};
    main.innerHTML = '<p><a href="#/types">' + L('← Site types') + '</a></p>'
      + '<div class="row"><h2>' + esc(L(M.generator.label || c.label)) + '</h2>' + chip(esc(t)) + (c.base !== t ? chip(L('copy of {0}', esc(c.base)), 'auto') : chip(L('Built-in'))) + (c.hidden ? chip(L('hidden'), 'warn') : '') + '<span class="sp"></span>'
      + '<a class="btn" href="#/types/' + t + '/history">' + L('History') + ' (' + D.log.length + ')</a>'
      + '<button id="tvis">' + (c.hidden ? L('Show in the job form') : L('Hide from the job form')) + '</button><button id="ttest">' + L('Test build now') + '</button>'
      + '<button class="danger" id="tdel"' + (c.base === t ? ' disabled title="' + L('Built-in types cannot be deleted. Hide it instead.') + '"' : '') + '>' + L('Delete type') + '</button></div>'
      + '<p class="muted">' + L('Last change') + ': ' + esc(D.changed) + '</p><p id="tprobe" class="muted"></p>'
      + (D.stale ? '<p class="note">' + L('{0} built sites were made before the last change. Rebuild their jobs to apply it.', D.stale) + '</p>' : '')
      + '<div class="mtop"><section class="card"><h3>' + L('Used by') + '</h3><p><b>' + L('Jobs') + ':</b> ' + (u.jobs.length ? u.jobs.slice(0, 10).map(function (j) { return '<a href="#/j/' + j.id + '">' + esc(j.project) + ' / ' + esc(j.name) + '</a> <span class="muted">(' + j.sites + ')</span>'; }).join(', ') + (u.jobs.length > 10 ? ' …' : '') : L('None.')) + '</p>'
      + '<p><b>' + L('Demos') + ':</b> ' + (u.demos.length ? esc(u.demos.join(', ')) : L('None.')) + '</p></section>'
      + '<section class="card"><h3>' + L('Markets') + '</h3><table><tbody>' + Object.keys(D.markets).map(function (m) {
        var x = D.markets[m];
        return '<tr><td><a href="#/markets/' + m + '">' + esc(m.toUpperCase()) + '</a></td><td>' + (!x ? '<span class="muted">' + L('not checked yet') + '</span>' : x.ok ? chip('OK', 'ok') : chip('✕', 'bad') + ' <span class="muted" style="font-size:12px">' + esc(x.reason || '') + '</span>') + '</td></tr>';
      }).join('') + '</tbody></table></section></div>'
      + '<p class="note">' + L('Every save is test-built first (demo market, every option of the job form). If the type no longer builds, nothing is saved.') + '</p>'
      + '<nav class="mnav"><a href="javascript:void 0" data-go="t-basics">' + L('Name and requirements') + '</a><a href="javascript:void 0" data-go="t-page">' + (M.pages ? L('Pages') : L('Sections')) + '</a><a href="javascript:void 0" data-go="t-angles">' + L('Angles') + '</a><a href="javascript:void 0" data-go="t-frame">' + L('Frame') + '</a><a href="javascript:void 0" data-go="t-ai">' + L('AI notes') + '</a></nav>'
      + '<form id="tform" class="mform" novalidate>'
      + '<section class="card msec" id="t-basics"><h3>' + L('Name and requirements') + '</h3>'
      + '<div class="field"><span class="lbl">' + L('Name in the job form') + '</span><input type="text" data-a="label" value="' + esc(M.generator.label || c.label) + '"><span class="muted mhelp">' + L('Shown in the job form and the job page.') + '</span></div>'
      + '<div class="field"><span class="lbl">' + L('Title') + '</span><input type="text" data-a="title" value="' + esc(M.title || '') + '"><span class="muted mhelp">' + L('Internal title of the plan (shown in docs and AI briefs).') + '</span></div>'
      + '<div class="field"><span class="lbl">' + L('Needs vertical') + '</span><select data-a="vert" style="max-width:200px"><option value="">' + L('nothing') + '</option><option value="sports"' + (M.requires_vertical === 'sports' ? ' selected' : '') + '>sports</option><option value="casino"' + (M.requires_vertical === 'casino' ? ' selected' : '') + '>casino</option></select><span class="muted mhelp">' + L('The whole type is built only in markets that allow it.') + '</span></div></section>'
      + (M.pages ? '<div class="tpages">' + pids.map(function (pid) { return '<button type="button" data-a="page" data-pg="' + pid + '"' + (pid === page ? ' aria-pressed="true" class="primary"' : '') + '>' + esc(pid === 'home' ? L('Home page') : G.pageName(pid)) + '</button>'; }).join('') + '</div>' : '')
      + pagePanel(page)
      + '<section class="card msec" id="t-angles"><h3>' + L('Angles') + '</h3><p class="muted mhelp">' + L('An angle is a theme a site can take: which optional sections it shows, which designs its sections may use, which pages it adds. Each site gets one, picked by its seed among the angles its market allows.') + '</p>'
      + anglePanel('angles', M.angles, M.pages ? (M.pages.home.angles ? null : M.pages.home.sections) : M.sections, M.pages ? L('Angles') : L('Angles'))
      + (M.pages && M.pages.home.angles ? anglePanel('pages.home.angles', M.pages.home.angles, M.pages.home.sections, L('Home page angles')) : '') + '</section>'
      + '<section class="card msec" id="t-frame"><h3>' + L('Frame') + '</h3><p class="muted mhelp">' + L('Header and footer designs this type may use. The rest of the frame (compliance bar, age check, sticky button) follows the market.') + '</p>' + framePanel() + '</section>'
      + '<section class="card msec" id="t-ai"><h3>' + L('AI notes') + '</h3><p class="muted mhelp">' + L('Added to every AI brief for sites of this type.') + '</p><textarea id="tnotes" rows="12" style="width:100%;font:13px/1.5 ui-monospace,monospace">' + esc(D.notes) + '</textarea></section>'
      + '<details class="more"><summary>' + L('All of it as JSON') + '</summary><p class="muted mhelp">' + L('Every field of the plan. Edit here for anything the form does not cover, then use this JSON and save.') + '</p><textarea id="traw" rows="20" style="width:100%;font:12px/1.5 ui-monospace,monospace"></textarea><div class="row"><button type="button" id="trawuse">' + L('Use this JSON') + '</button><span class="muted" id="trawmsg"></span></div></details>'
      + '<div class="msavebar"><button class="primary">' + L('Save changes') + '</button><span class="chip warn" id="tdirty"' + (dirty ? '' : ' hidden') + '>' + L('Unsaved changes') + '</span><span id="tsave" class="muted"></span></div></form>';
    wire();
    if (keep) window.scrollTo(0, scroll);
  }
  function wire() {
    var f = document.getElementById('tform'), t = D.type;
    var touch = function (redraw) { dirty = true; if (redraw) draw(true); else document.getElementById('tdirty').hidden = false; };
    var P = function (el) { return pagesOf(M)[el.dataset.pg]; };
    f.addEventListener('change', function (e) {
      var el = e.target, a = el.dataset.a;
      if (!a) return;
      if (a === 'var') {
        var s = P(el).sections[+el.dataset.i], all = designs(s.block);
        var on = [].filter.call(f.querySelectorAll('[data-a=var][data-pg="' + el.dataset.pg + '"][data-i="' + el.dataset.i + '"]'), function (x) { return x.checked; }).map(function (x) { return x.dataset.v; });
        if (on.length === all.length) delete s.variants; else s.variants = on;
        return touch();
      }
      if (a === 'zone' || a === 'rule') { P(el).sections[+el.dataset.i][a] = el.value; return touch(true); }
      if (a === 'prule') { P(el).rule = el.value; return touch(); }
      if (a === 'ra' || a === 'rb') { P(el).order_rules[+el.dataset.i][a === 'ra' ? 'a' : 'b'] = el.value; return touch(); }
      if (a === 'rr') { var r = P(el).order_rules[+el.dataset.i]; r.rule = el.value; if (r.rule === 'not_first') delete r.b; else r.b = r.b || r.a; return touch(true); }
      if (a === 'vert') { if (el.value) M.requires_vertical = el.value; else delete M.requires_vertical; return touch(); }
      var ang = el.dataset.w ? angleHolder(el.dataset.w)[el.dataset.n] : null;
      if (a === 'areq') { if (el.value) ang.requires_vertical = el.value; else delete ang.requires_vertical; return touch(); }
      if (a === 'aopt') { ang.optional = [].filter.call(f.querySelectorAll('[data-a=aopt][data-w="' + el.dataset.w + '"][data-n="' + el.dataset.n + '"]'), function (x) { return x.checked; }).map(function (x) { return x.dataset.s; }); return touch(); }
      if (a === 'avar') { ang.variants[el.dataset.s] = [].filter.call(f.querySelectorAll('[data-a=avar][data-w="' + el.dataset.w + '"][data-n="' + el.dataset.n + '"][data-s="' + el.dataset.s + '"]'), function (x) { return x.checked; }).map(function (x) { return x.dataset.v; }); return touch(); }
      if (a === 'avadd' && el.value) { ang.variants = ang.variants || {}; var sec = (pagesOf(M)[M.pages ? 'home' : ''].sections || []).filter(function (s) { return s.id === el.value; })[0]; ang.variants[el.value] = designs(sec.block).slice(); return touch(true); }
      if (a === 'apage') { ang.pages = [].filter.call(f.querySelectorAll('[data-a=apage][data-w="' + el.dataset.w + '"][data-n="' + el.dataset.n + '"]'), function (x) { return x.checked; }).map(function (x) { return x.dataset.s; }); return touch(); }
      if (a === 'frame') {
        var fr = M.frame[el.dataset.k], alld = designs(fr.block), ond = [].filter.call(f.querySelectorAll('[data-a=frame][data-k="' + el.dataset.k + '"]'), function (x) { return x.checked; }).map(function (x) { return x.dataset.v; });
        if (ond.length === alld.length) delete fr.variants; else fr.variants = ond;
        return touch();
      }
    });
    f.addEventListener('input', function (e) {
      var el = e.target, a = el.dataset.a;
      if (a === 'label') { M.generator.label = el.value; touch(); }
      if (a === 'title') { M.title = el.value; touch(); }
      if (a === 'slug') { P(el).slug = el.value.trim(); touch(); }
    });
    f.addEventListener('click', function (e) {
      var el = e.target.closest('button[data-a]');
      if (!el) return;
      var a = el.dataset.a, p = el.dataset.pg !== undefined ? P(el) : null, i = +el.dataset.i;
      if (a === 'page') { page = el.dataset.pg; return draw(true); }
      if (a === 'up' || a === 'down') { var j = a === 'up' ? i - 1 : i + 1; if (j < 0 || j >= p.sections.length) return; var x = p.sections[i]; p.sections[i] = p.sections[j]; p.sections[j] = x; return touch(true); }
      if (a === 'rm') {
        var id = p.sections[i].id;
        p.sections.splice(i, 1);
        p.order_rules = (p.order_rules || []).filter(function (r) { return r.a !== id && r.b !== id; });
        var clean = function (angles) { Object.keys(angles || {}).forEach(function (k) { var x = angles[k]; if (x.optional) x.optional = x.optional.filter(function (s) { return s !== id; }); if (x.variants) delete x.variants[id]; }); };
        clean(p.angles); if (!M.pages || el.dataset.pg === 'home') clean(M.angles);
        return touch(true);
      }
      if (a === 'spare') { var sid = document.getElementById('tspare').value, s = D.spares[el.dataset.pg].filter(function (x) { return x.id === sid; })[0]; p.sections.push(JSON.parse(JSON.stringify(s))); return touch(true); }
      if (a === 'rrm') { p.order_rules.splice(i, 1); return touch(true); }
      if (a === 'radd') { var mid = p.sections.filter(function (s) { return s.zone === 'middle'; }); (p.order_rules = p.order_rules || []).push({ rule: 'not_first', a: mid[0].id }); return touch(true); }
      if (a === 'prm') {
        if (!confirm(L('Remove this page? Links to it disappear from every site of this type.'))) return;
        var pid = el.dataset.pg; delete M.pages[pid];
        Object.keys(M.angles || {}).forEach(function (k) { if (M.angles[k].pages) M.angles[k].pages = M.angles[k].pages.filter(function (x) { return x !== pid; }); });
        page = 'home'; return touch(true);
      }
      var holder = el.dataset.w ? angleHolder(el.dataset.w) : null;
      if (a === 'arm') { delete holder[el.dataset.n]; return touch(true); }
      if (a === 'acopy') { var nid = prompt(L('New angle id')); if (!nid || holder[nid]) return; holder[nid.trim()] = JSON.parse(JSON.stringify(holder[el.dataset.n])); return touch(true); }
      if (a === 'avrm') { delete holder[el.dataset.n].variants[el.dataset.s]; return touch(true); }
    });
    [].forEach.call(main.querySelectorAll('[data-go]'), function (x) { x.onclick = function () { document.getElementById(x.dataset.go).scrollIntoView({ behavior: 'smooth' }); }; });
    document.getElementById('tnotes').oninput = function () { touch(); };
    var raw = document.getElementById('traw');
    f.querySelector('details.more').ontoggle = function () { raw.value = JSON.stringify(M, null, 2); };
    document.getElementById('trawuse').onclick = function () {
      try { M = JSON.parse(raw.value); dirty = true; draw(true); document.getElementById('trawmsg').textContent = L('JSON applied to the form. Save to keep it.'); }
      catch (x) { document.getElementById('trawmsg').textContent = L('Not valid JSON: {0}', x.message); }
    };
    document.getElementById('tvis').onclick = function () { api('types/' + t + '/visibility', { hidden: !D.catalog.hidden }, 'PUT').then(function () { load(t); }).catch(G.fail); };
    document.getElementById('tdel').onclick = function () { if (confirm(L('Delete type {0}? Its plan and AI notes are kept in the history.', t))) api('types/' + t, null, 'DELETE').then(function () { location.hash = '#/types'; }).catch(function (x) { alert(x.message); }); };
    document.getElementById('ttest').onclick = function () {
      var el = document.getElementById('tprobe'); el.className = 'notice'; el.innerHTML = '<span class="spin"></span><span>' + L('Test builds running…') + '</span>';
      api('types/' + t + '/test', {}).then(function (r) { probeMsg(r); }).catch(function (x) { el.className = 'err'; el.textContent = x.message; });
    };
    f.onsubmit = function (ev) {
      ev.preventDefault();
      var msg = document.getElementById('tsave'), btn = f.querySelector('.msavebar button');
      [].forEach.call(document.querySelectorAll('.ferr'), function (x) { x.hidden = true; });
      [].forEach.call(document.querySelectorAll('.bad-f'), function (x) { x.classList.remove('bad-f'); });
      btn.disabled = true; msg.className = 'muted'; msg.textContent = L('Saving: test builds running…');
      api('types/' + t, { blueprint: M, notes: document.getElementById('tnotes').value }, 'PUT').then(function (r) {
        var text = r.changes && r.changes.length ? L('Saved.') + ' ' + L('Changed: {0}', r.changes.join(', ')) : L('No changes to save.');
        load(t, text, r.probe);
      }).catch(function (x) {
        btn.disabled = false; msg.className = 'err'; msg.textContent = L(x.message);
        if (x.fields) showErrors(x.fields);
      });
    };
  }
  function probeMsg(r) {
    var el = document.getElementById('tprobe');
    if (!el || !r) return;
    var bad = Object.keys(r.options || {}).map(function (a) { return a + ': ' + Object.keys(r.options[a]).join(', '); });
    el.className = r.base ? 'err' : bad.length ? 'note' : 'okmsg';
    el.textContent = r.base ? r.base : bad.length ? L('These options no longer build: {0}', bad.join('; ')) : L('Builds: OK');
  }
  function showErrors(fields) {
    var first = null;
    Object.keys(fields).forEach(function (p) {
      var ks = p.split('.'), el = null;
      while (!el && ks.length) { el = document.querySelector('[data-f="' + ks.join('.') + '"]'); if (!el) ks.pop(); }
      var box = el || document.getElementById('tsave');
      if (el) {
        el.classList.add('bad-f');
        var e = el.querySelector('.ferr');
        if (e) { e.hidden = false; e.textContent = (ks.join('.') !== p ? p + ': ' : '') + L(fields[p]); }
      } else box.textContent += ' ' + p + ': ' + L(fields[p]);
      first = first || el;
    });
    if (first) first.scrollIntoView({ block: 'center' });
  }
  function load(t, msg, probe) {
    api('types/' + t).then(function (d) {
      if (D && D.type !== t) page = null;
      D = d; M = JSON.parse(JSON.stringify(d.blueprint)); dirty = false;
      draw(!!msg);
      if (msg) { var el = document.getElementById('tsave'); el.className = 'okmsg'; el.textContent = msg; }
      if (probe) probeMsg(probe);
    }).catch(G.fail);
  }

  /* ---------- history */
  function history(t) {
    api('types/' + t).then(function (d) {
      main.innerHTML = '<p><a href="#/types/' + t + '">← ' + esc(L(d.catalog.label)) + '</a></p><h2>' + L('History') + ': ' + esc(L(d.catalog.label)) + '</h2>'
        + '<p class="muted">' + L('Every save keeps the previous version. Open one to see what differs from now, or put it back.') + '</p>'
        + (d.log.length ? '<div class="list">' + d.log.map(function (x) {
          return '<div class="card"><div class="row"><b>' + esc(x.time.replace('T', ' ').slice(0, 16)) + '</b><span>' + esc(act(x.action)) + '</span><span class="sp"></span>'
            + (x.version ? '<button data-v="' + esc(x.version) + '">' + L('Show') + '</button>' : '') + '</div>'
            + '<div class="muted" style="font:12px/1.5 ui-monospace,monospace;margin-top:6px">' + esc(x.changes.join(', ')) + '</div><div class="vbody" hidden></div></div>';
        }).join('') + '</div>' : '<p class="muted">' + L('No changes yet.') + '</p>');
      [].forEach.call(main.querySelectorAll('[data-v]'), function (b) {
        b.onclick = function () {
          var box = b.closest('.card').querySelector('.vbody');
          if (!box.hidden) { box.hidden = true; b.textContent = L('Show'); return; }
          api('types/' + t + '/version?v=' + encodeURIComponent(b.dataset.v)).then(function (v) {
            box.hidden = false; b.textContent = L('Hide');
            box.innerHTML = '<p><b>' + L('Version before') + ' ' + esc(b.dataset.v.slice(0, 15)) + '.</b> ' + (v.changes.length ? L('Differs from now in: {0}', esc(v.changes.join(', '))) : L('Same as now.')) + '</p>'
              + '<pre style="max-height:320px;overflow:auto;font-size:12px;background:var(--bg);padding:10px;border-radius:8px">' + esc(JSON.stringify(v.blueprint, null, 2)) + '</pre>'
              + '<button class="primary" data-restore>' + L('Put this version back') + '</button>';
            box.querySelector('[data-restore]').onclick = function () {
              if (confirm(L('Put this version back? The current one is kept in the history.'))) api('types/' + t + '/restore', { version: b.dataset.v }).then(function () { location.hash = '#/types/' + t; }).catch(G.fail);
            };
          }).catch(G.fail);
        };
      });
    }).catch(G.fail);
  }

  window.addEventListener('beforeunload', function (e) { if (dirty && /^#\/types\/[a-z]/.test(location.hash)) { e.preventDefault(); e.returnValue = ''; } });
  G.types = function (h) {
    if (h[1] === 'new') return create();
    if (h[1] && h[2] === 'history') return history(h[1]);
    if (h[1]) return load(h[1]);
    list();
  };
})();
