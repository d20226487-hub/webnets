/* Markets pages of the generator: list, add, edit (every rule, with what it changes), history. Inlined by index.php;
   uses the helpers index.php shares on window.GEN (api, esc, L, fail, main, RU). */
(function () {
  var G = window.GEN, api = G.api, esc = G.esc, L = G.L, main = G.main;
  Object.assign(G.RU, {
    'Markets': 'Рынки', 'Add market': 'Добавить рынок', 'Market': 'Рынок', 'Languages': 'Языки', 'Age': 'Возраст', 'Verticals': 'Вертикали',
    'Promotions': 'Акции', 'Currency': 'Валюта', 'Used by': 'Где используется', 'Last change': 'Последнее изменение', 'Edit': 'Изменить',
    'demo only': 'только демо', '{0} of {1} allowed': 'разрешено {0} из {1}', '{0} jobs · {1} sites': 'заданий: {0} · сайтов: {1}', '{0} demos': 'демо: {0}',
    'Each market sets the rules every site built for it follows: languages and formats, what may be shown (sports, casino, promotions), the compliance texts, and the pages and blocks that must be there. Sites keep the rules they were built with until they are rebuilt.':
      'Рынок задаёт правила для каждого сайта, собранного под него: языки и форматы, что можно показывать (спорт, казино, акции), тексты для соответствия требованиям, а также обязательные страницы и блоки. Сайты сохраняют правила, с которыми были собраны, пока их не пересоберут.',
    'Deleted markets': 'Удалённые рынки', 'Restore': 'Восстановить', 'Recent changes': 'Последние изменения', 'No changes yet.': 'Изменений пока нет.',
    'Time': 'Время', 'Action': 'Действие', 'What changed': 'Что изменилось', 'History': 'История', 'Delete market': 'Удалить рынок',
    'New market': 'Новый рынок', 'Market id': 'Код рынка', 'Short code used in files and links, 2–8 lowercase Latin letters (e.g. de, ontario). It cannot be changed later.':
      'Короткий код для файлов и ссылок: 2–8 строчных латинских букв (например, de, ontario). Потом его не изменить.',
    'Name': 'Название', 'Start from': 'Взять за основу', 'The new market starts as a copy of this one: every rule and text, and its AI notes. Change what differs.':
      'Новый рынок начинается как копия выбранного: все правила, тексты и заметки для ИИ. Измените то, что отличается.',
    'Demo only': 'Только для демо', 'A demo market is not a real jurisdiction: real sites refuse it.': 'Демо-рынок — не настоящая юрисдикция: реальные сайты его не принимают.',
    'Create market': 'Создать рынок', '← Markets': '← Рынки', 'Save changes': 'Сохранить изменения', 'Saved.': 'Сохранено.', 'Changed: {0}': 'Изменено: {0}',
    'Some fields need fixing.': 'Некоторые поля нужно исправить.', 'No changes to save.': 'Нет изменений.', 'Unsaved changes': 'Есть несохранённые изменения',
    'Basics': 'Основное', 'Formats': 'Форматы', 'What is allowed': 'Что разрешено', 'Payments and apps': 'Платежи и приложения', 'Compliance texts': 'Тексты о соответствии',
    'Pages and sharing': 'Страницы и кнопки «поделиться»', 'AI notes': 'Заметки для ИИ', 'All of it as JSON': 'Всё в виде JSON',
    'Built here': 'Что здесь собирается', 'Checking what can be built here (test builds of every site type)…': 'Проверяем, что можно собрать (пробные сборки каждого типа сайтов)…',
    'Available': 'Доступно', 'Not available': 'Недоступно', 'Some options off': 'Часть вариантов недоступна',
    'Jobs': 'Задания', 'Demos': 'Демо', 'Brands licensed here': 'Бренды с лицензией здесь', 'None.': 'Нет.',
    '{0} built sites were made before the last change and still show the old rules. Rebuild their jobs to apply it.':
      'Сайтов, собранных до последнего изменения и показывающих старые правила: {0}. Пересоберите их задания, чтобы применить изменения.',
    'Notes in the file': 'Заметки в файле', 'Cannot be deleted while jobs or demos use it.': 'Нельзя удалить, пока его используют задания или демо.',
    'Delete market {0}? Its file and AI notes are moved to the deleted markets, where you can restore them.': 'Удалить рынок {0}? Файл и заметки для ИИ перейдут в удалённые рынки, откуда их можно восстановить.',
    'Main language': 'Основной язык', 'Pages are in the main language unless a site picks another one of the market.': 'Страницы на основном языке, если сайт не выбрал другой язык рынка.',
    'The languages sites can be built in. Only languages the blocks are written in can be chosen. Every language needs its compliance texts.':
      'Языки, на которых можно собирать сайты. Можно выбрать только языки, на которых написаны блоки. Для каждого языка нужны тексты о соответствии.',
    '{0}% of block texts': 'тексты блоков: {0}%', 'Short name per language': 'Короткое название по языкам', 'Used where the full name is too long (switchers, labels). Optional.':
      'Там, где полное название слишком длинное (переключатели, подписи). Необязательно.', 'Text direction': 'Направление текста',
    'Symbol': 'Символ', 'Code': 'Код', 'Symbol position': 'Положение символа', 'before': 'до суммы', 'after': 'после суммы', 'Space between': 'Пробел между',
    'Decimal mark': 'Десятичный разделитель', 'Thousands mark': 'Разделитель тысяч', 'Decimals': 'Знаков после запятой', 'Date format': 'Формат даты',
    'PHP date letters: d day, j day without zero, m month, n month without zero, M short month name, Y year.': 'Буквы формата PHP: d — день, j — день без нуля, m — месяц, n — месяц без нуля, M — короткое название месяца, Y — год.',
    'Preview': 'Пример', 'Per-language formats': 'Форматы по языкам', 'Only where a language writes numbers or dates differently from the market default. Empty = market default.':
      'Только если язык пишет числа или даты иначе, чем по умолчанию для рынка. Пусто — как у рынка.',
    'Minimum age': 'Минимальный возраст', 'Shown in the age check, the compliance bar and footers; the age check asks for it.': 'Показывается в проверке возраста, полосе соответствия и подвалах; проверка возраста спрашивает именно его.',
    'Sports betting': 'Ставки на спорт', 'Casino': 'Казино', 'Off here means:': 'Если выключено:', 'site types not built': 'типы сайтов не собираются',
    'angles left out': 'углы подачи не используются', 'pages left out': 'страницы не создаются', 'section designs left out': 'дизайны секций не используются',
    'Unknown counts as restricted. A promo site needs at least one allowed type its angle uses; pages that show offers need any allowed type.':
      '«Неизвестно» считается запретом. Сайту с акциями нужен хотя бы один разрешённый тип, который использует его угол подачи; страницам с предложениями нужен любой разрешённый тип.',
    'allowed': 'разрешено', 'restricted': 'запрещено', 'unknown': 'неизвестно', 'Used by': 'Используют', 'Pages that need any allowed type: {0}': 'Страницы, которым нужен любой разрешённый тип: {0}',
    'Payment methods': 'Способы оплаты', 'Shown on payment sections and brand facts, in this order. Unknown ids are shown as written.':
      'Показываются в секциях о платежах и фактах о бренде, в этом порядке. Неизвестные коды показываются как есть.', 'Add another id': 'Добавить другой код',
    'iPhone apps': 'Приложения для iPhone', 'Android apps': 'Приложения для Android', 'Which install paths app sites and app pages offer.': 'Какие способы установки предлагают сайты и страницы приложений.',
    'App Store': 'App Store', 'Google Play': 'Google Play', 'APK file': 'Файл APK', 'Google Play and APK': 'Google Play и APK', 'none': 'нет',
    'Brand domain ending': 'Окончание домена бренда', 'Brand links must point to a domain with this ending (e.g. .bet.br). Empty = any domain.':
      'Ссылки на бренд должны вести на домен с таким окончанием (например, .bet.br). Пусто — любой домен.',
    'Compliance bar': 'Полоса соответствия', 'required on every site': 'обязательна на каждом сайте', 'optional (site decides)': 'по желанию (решает сайт)',
    'The strip with the age notice and licence line at the top of every page.': 'Полоса с возрастным уведомлением и строкой о лицензии вверху каждой страницы.',
    'Required blocks': 'Обязательные блоки', 'These blocks are always on and cannot be removed in the editor.': 'Эти блоки всегда включены, их нельзя убрать в редакторе.',
    'age-gate': 'проверка возраста', 'compliance-bar': 'полоса соответствия',
    'Licence line': 'Строка о лицензии', 'Regulator': 'Регулятор', 'Licence badge': 'Значок лицензии', 'Age notice': 'Возрастное уведомление', 'Self-exclusion': 'Самоисключение',
    'Taglines': 'Слоганы об ответственной игре', 'Helpline': 'Телефон помощи', 'Compliance notes': 'Заметки о соответствии',
    'Footer and compliance bar. {brand}, {license_no} and {regulator} are filled in.': 'Подвал и полоса соответствия. {brand}, {license_no} и {regulator} подставляются.',
    'Used when the brand has no regulator of its own.': 'Используется, если у бренда не указан свой регулятор.', 'Short label in trust bars and cards.': 'Короткая подпись в полосах доверия и карточках.',
    'Compliance bar, age check and footers.': 'Полоса соответствия, проверка возраста и подвалы.', 'Responsible gambling page and footers. Optional.': 'Страница об ответственной игре и подвалы. Необязательно.',
    'One per line; footers rotate through them.': 'По одному на строку; подвалы показывают их по очереди.', 'Footer and responsible gambling page. Empty = none.': 'Подвал и страница об ответственной игре. Пусто — нет.',
    'For people: what to check with compliance. Not shown on sites.': 'Для людей: что проверить с юристами. На сайтах не показывается.',
    'Pages required here': 'Обязательные страницы', 'These pages are always built for site types that have them, and cannot be removed in the editor.':
      'Эти страницы всегда создаются у типов сайтов, где они есть, и их нельзя убрать в редакторе.',
    'Share buttons': 'Кнопки «поделиться»', 'News and review pages show share buttons in this order. Untick a network to leave it out.':
      'Новости и страницы отзывов показывают кнопки в этом порядке. Снимите галочку, чтобы убрать сеть.', 'Move up': 'Выше', 'Move down': 'Ниже',
    'Page language tags': 'Языковые теги страниц', 'The lang attribute of every page, per language (search engines and screen readers read it).':
      'Атрибут lang каждой страницы для каждого языка (его читают поисковики и экранные дикторы).',
    'Added to every AI brief for sites of this market: market context and rules the AI must not break.': 'Добавляется в каждый ИИ-бриф для сайтов этого рынка: контекст рынка и правила, которые ИИ нарушать нельзя.',
    'Written from the settings above into every AI brief (after saving, it follows your changes):': 'Пишется в каждый ИИ-бриф из настроек выше (после сохранения следует за изменениями):',
    'Your extra rules, added after it: only what the settings cannot say (e.g. identification, self-exclusion services, domains).': 'Ваши дополнительные правила, идут следом: только то, чего нет в настройках (например, идентификация, сервисы самоисключения, домены).',
    'Every field of the market file. Edit here for anything the form does not cover, then use this JSON and save.':
      'Все поля файла рынка. Редактируйте здесь то, чего нет в форме, затем примените JSON и сохраните.',
    'Use this JSON': 'Применить JSON', 'Not valid JSON: {0}': 'Неверный JSON: {0}', 'JSON applied to the form. Save to keep it.': 'JSON применён к форме. Сохраните, чтобы закрепить.',
    'Every save keeps the previous version. Open one to see what differs from now, or put it back.': 'Каждое сохранение хранит предыдущую версию. Откройте её, чтобы увидеть отличия от текущей, или верните.',
    'Version before': 'Версия до', 'Differs from now in: {0}': 'Отличается от текущей: {0}', 'Same as now.': 'Совпадает с текущей.', 'Put this version back': 'Вернуть эту версию',
    'Put this version back? The current one is kept in the history.': 'Вернуть эту версию? Текущая сохранится в истории.', 'Show': 'Показать', 'Hide': 'Скрыть',
    'edited': 'изменён', 'deleted': 'удалён', 'restored': 'восстановлен', 'Options': 'Варианты', 'Language': 'Язык', 'Optional.': 'Необязательно.',
    'Builds run as the sites of this market would. A type that is not available cannot be chosen in the job form for this market.':
      'Проверка идёт так, как собирались бы сайты этого рынка. Недоступный тип нельзя выбрать в форме задания для этого рынка.'
  });
  var M = null, D = null, dirty = false;
  var get = function (o, p) { return p.split('.').reduce(function (x, k) { return x == null ? undefined : x[k]; }, o); };
  var set = function (o, p, v) { var ks = p.split('.'), x = o; ks.slice(0, -1).forEach(function (k) { if (x[k] == null || typeof x[k] !== 'object') x[k] = {}; x = x[k]; }); x[ks[ks.length - 1]] = v; };
  var chip = function (t, cls) { return '<span class="chip' + (cls ? ' ' + cls : '') + '">' + t + '</span>'; };
  var act = function (a) { return a.replace(/^(edited|deleted|restored)/, function (m) { return L(m); }); };

  /* ---------- list */
  function list() {
    api('markets').then(function (d) {
      main.innerHTML = '<div class="row"><div><h2>' + L('Markets') + '</h2></div><span class="sp"></span><a class="btn primary" href="#/markets/new">' + L('Add market') + '</a></div>'
        + '<p class="muted" style="max-width:820px">' + L('Each market sets the rules every site built for it follows: languages and formats, what may be shown (sports, casino, promotions), the compliance texts, and the pages and blocks that must be there. Sites keep the rules they were built with until they are rebuilt.') + '</p>'
        + '<div class="card mkt-table" style="padding:0;overflow-x:auto"><table><thead><tr><th>' + L('Market') + '</th><th>' + L('Languages') + '</th><th>' + L('Age') + '</th><th>' + L('Verticals') + '</th><th>' + L('Promotions') + '</th><th>' + L('Currency') + '</th><th>' + L('Used by') + '</th><th>' + L('Last change') + '</th></tr></thead><tbody>'
        + d.markets.map(function (m) {
          return '<tr><td><a href="#/markets/' + m.id + '"><b>' + esc(m.name) + '</b></a> ' + chip(esc(m.id.toUpperCase())) + (m.demo_only ? ' ' + chip(L('demo only'), 'auto') : '') + '</td>'
            + '<td>' + m.locales.map(function (l) { return chip(esc(l)); }).join(' ') + '</td><td>' + esc(m.min_age) + '+</td>'
            + '<td>' + ['sports', 'casino'].map(function (v) { return chip(L(v === 'sports' ? 'Sports betting' : 'Casino'), m.verticals[v] ? 'ok' : 'bad'); }).join(' ') + '</td>'
            + '<td>' + L('{0} of {1} allowed', m.promos_allowed, 8) + '</td><td>' + esc(m.currency) + '</td>'
            + '<td>' + L('{0} jobs · {1} sites', m.jobs, m.sites) + (m.demos ? '<br><span class="muted">' + L('{0} demos', m.demos) + '</span>' : '') + '</td><td class="muted">' + esc(m.changed) + '</td></tr>';
        }).join('') + '</tbody></table></div>'
        + (d.deleted.length ? '<h3>' + L('Deleted markets') + '</h3><div class="list">' + d.deleted.map(function (x) {
          return '<div class="item card row"><b>' + esc(x.name) + '</b> ' + chip(esc(x.id.toUpperCase())) + '<span class="sp"></span><button data-restore="' + esc(x.id) + '" data-v="' + esc(x.version) + '">' + L('Restore') + '</button></div>';
        }).join('') + '</div>' : '')
        + '<h3>' + L('Recent changes') + '</h3>' + logTable(d.log, true);
      [].forEach.call(main.querySelectorAll('[data-restore]'), function (b) {
        b.onclick = function () { api('markets', { id: b.dataset.restore, restore: b.dataset.v }).then(function () { location.hash = '#/markets/' + b.dataset.restore; }).catch(G.fail); };
      });
    }).catch(G.fail);
  }
  function logTable(log, withMarket) {
    if (!log.length) return '<p class="muted">' + L('No changes yet.') + '</p>';
    return '<div class="card" style="padding:0;overflow-x:auto"><table><thead><tr><th>' + L('Time') + '</th>' + (withMarket ? '<th>' + L('Market') + '</th>' : '') + '<th>' + L('Action') + '</th><th>' + L('What changed') + '</th></tr></thead><tbody>'
      + log.map(function (x) {
        return '<tr><td class="muted" style="white-space:nowrap">' + esc(x.time.replace('T', ' ').slice(0, 16)) + '</td>' + (withMarket ? '<td><a href="#/markets/' + esc(x.market) + '">' + esc(x.market.toUpperCase()) + '</a></td>' : '')
          + '<td>' + esc(act(x.action)) + '</td><td style="font-family:ui-monospace,monospace;font-size:12px">' + esc(x.changes.slice(0, 12).join(', ') + (x.changes.length > 12 ? ' …' : '')) + '</td></tr>';
      }).join('') + '</tbody></table></div>';
  }

  /* ---------- add */
  function create() {
    api('markets').then(function (d) {
      main.innerHTML = '<p><a href="#/markets">' + L('← Markets') + '</a></p><h2>' + L('New market') + '</h2>'
        + '<form class="card" id="mnew" style="max-width:640px;display:grid;gap:4px">'
        + '<div class="field"><label class="lbl" for="mid">' + L('Market id') + '</label><input type="text" id="mid" required pattern="[a-z]{2,8}" style="width:160px"><span class="muted">' + L('Short code used in files and links, 2–8 lowercase Latin letters (e.g. de, ontario). It cannot be changed later.') + '</span></div>'
        + '<div class="field"><label class="lbl" for="mname">' + L('Name') + '</label><input type="text" id="mname" required></div>'
        + '<div class="field"><label class="lbl" for="mfrom">' + L('Start from') + '</label><select id="mfrom">' + d.markets.map(function (m) { return '<option value="' + m.id + '"' + (m.id === 'xx' ? ' selected' : '') + '>' + esc(m.name) + ' (' + m.id.toUpperCase() + ')</option>'; }).join('') + '</select><span class="muted">' + L('The new market starts as a copy of this one: every rule and text, and its AI notes. Change what differs.') + '</span></div>'
        + '<div class="field"><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" id="mdemo"> <span class="lbl" style="margin:0">' + L('Demo only') + '</span></label><span class="muted">' + L('A demo market is not a real jurisdiction: real sites refuse it.') + '</span></div>'
        + '<div class="row"><button class="primary">' + L('Create market') + '</button></div><p class="err" id="merr" hidden></p></form>';
      document.getElementById('mnew').onsubmit = function (e) {
        e.preventDefault();
        var id = document.getElementById('mid').value.trim().toLowerCase();
        api('markets', { id: id, name: document.getElementById('mname').value, from: document.getElementById('mfrom').value, demo_only: document.getElementById('mdemo').checked })
          .then(function () { location.hash = '#/markets/' + id; })
          .catch(function (x) { var el = document.getElementById('merr'); el.hidden = false; el.textContent = L(x.message); });
      };
    }).catch(G.fail);
  }

  /* ---------- edit */
  function field(label, inner, help, path) {
    return '<div class="field" data-f="' + (path || '') + '"><span class="lbl">' + label + '</span>' + inner + (help ? '<span class="muted mhelp">' + help + '</span>' : '') + '<span class="ferr" hidden></span></div>';
  }
  function inp(path, type, attrs) {
    var v = get(M, path);
    if (type === 'bool') return '<input type="checkbox" data-p="' + path + '" data-t="bool"' + (v ? ' checked' : '') + '>';
    return '<input type="' + (type === 'int' ? 'number' : 'text') + '" data-p="' + path + '" data-t="' + (type || 'str') + '" value="' + esc(v == null ? '' : v) + '"' + (attrs || '') + '>';
  }
  function sel(path, opts) {
    var v = get(M, path);
    return '<select data-p="' + path + '" data-t="str">' + opts.map(function (o) { return '<option value="' + esc(o[0]) + '"' + (String(v) === String(o[0]) ? ' selected' : '') + '>' + esc(o[1]) + '</option>'; }).join('') + '</select>';
  }
  function money(n, f) {
    var c = M.currency || {}, d = f.decimal != null ? f.decimal : c.decimal, t = f.thousands != null ? f.thousands : c.thousands, k = +c.decimals || 0;
    var parts = n.toFixed(k).split('.'), int = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, t || ''), s = int + (k ? d + parts[1] : '');
    var sp = c.space ? ' ' : '';
    return c.position === 'after' ? s + sp + (c.symbol || '') : (c.symbol || '') + sp + s;
  }
  function date(fmt) {
    var x = new Date(), mon = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], p = function (n) { return (n < 10 ? '0' : '') + n; };
    return String(fmt || '').replace(/[djmnMYy]/g, function (ch) { return { d: p(x.getDate()), j: x.getDate(), m: p(x.getMonth() + 1), n: x.getMonth() + 1, M: mon[x.getMonth()], Y: x.getFullYear(), y: String(x.getFullYear()).slice(2) }[ch]; });
  }
  function previews() {
    var el = document.getElementById('fprev');
    if (!el) return;
    var lf = M.locale_formats || {};
    el.innerHTML = (M.locales || []).map(function (l) {
      var f = lf[l] || {};
      return '<div><b>' + esc(l) + '</b> ' + esc(money(12345.5, f)) + ' · ' + esc(date(f.date_format || M.date_format)) + '</div>';
    }).join('');
  }
  function list3(xs) { return xs.length ? esc(xs.join(', ')) : '—'; }

  function form() {
    var e = D.effects, langs = D.languages, locs = M.locales || [], cp = M.compliance = M.compliance || {};
    var sec = function (id, title, body) { return '<section class="card msec" id="m-' + id + '"><h3>' + title + '</h3>' + body + '</section>'; };
    var notes = Object.keys(M).filter(function (k) { return k[0] === '_'; }).map(function (k) { return '<li><code>' + esc(k) + '</code> ' + esc(M[k]) + '</li>'; })
      .concat(Object.keys(cp).filter(function (k) { return k[0] === '_'; }).map(function (k) { return '<li><code>compliance.' + esc(k) + '</code> ' + esc(cp[k]) + '</li>'; }));
    var basics = (notes.length ? '<div class="note"><b>' + L('Notes in the file') + '</b><ul style="margin:6px 0 0;padding-left:18px">' + notes.join('') + '</ul></div>' : '')
      + field(L('Name'), inp('name'), '', 'name')
      + field(L('Demo only'), '<label class="inl">' + inp('demo_only', 'bool') + ' ' + L('Demo only') + '</label>', L('A demo market is not a real jurisdiction: real sites refuse it.'), 'demo_only')
      + field(L('Languages'), '<div class="mchecks">' + Object.keys(langs).map(function (l) {
        return '<label class="inl"><input type="checkbox" data-loc="' + l + '"' + (locs.indexOf(l) >= 0 ? ' checked' : '') + '> ' + esc(L(langs[l].name)) + ' <span class="muted">(' + l + ', ' + L('{0}% of block texts', langs[l].complete) + ')</span></label>';
      }).join('') + '</div>', L('The languages sites can be built in. Only languages the blocks are written in can be chosen. Every language needs its compliance texts.'), 'locales')
      + field(L('Main language'), sel('default_locale', locs.map(function (l) { return [l, l]; })), L('Pages are in the main language unless a site picks another one of the market.'), 'default_locale')
      + field(L('Short name per language'), '<div class="mgrid">' + locs.map(function (l) { return '<label class="mloc"><span>' + l + '</span>' + inp('short_label.' + l) + '</label>'; }).join('') + '</div>', L('Used where the full name is too long (switchers, labels). Optional.'), 'short_label')
      + field(L('Text direction'), sel('dir', [['ltr', 'ltr'], ['rtl', 'rtl']]), '', 'dir');
    var formats = '<div class="mgrid">' + field(L('Code'), inp('currency.code', 'str', ' maxlength="3" style="width:90px"'), '', 'currency.code') + field(L('Symbol'), inp('currency.symbol', 'str', ' style="width:90px"'), '', 'currency.symbol')
      + field(L('Symbol position'), sel('currency.position', [['before', L('before')], ['after', L('after')]]), '', 'currency.position') + field(L('Space between'), '<label class="inl">' + inp('currency.space', 'bool') + '</label>', '', 'currency.space')
      + field(L('Decimal mark'), inp('currency.decimal', 'str', ' maxlength="1" style="width:70px"'), '', 'currency.decimal') + field(L('Thousands mark'), inp('currency.thousands', 'str', ' maxlength="1" style="width:70px"'), '', 'currency.thousands')
      + field(L('Decimals'), inp('currency.decimals', 'int', ' min="0" max="3" style="width:80px"'), '', 'currency.decimals') + field(L('Date format'), inp('date_format', 'str', ' style="width:120px"'), '', 'date_format') + '</div>'
      + '<p class="muted mhelp">' + L('PHP date letters: d day, j day without zero, m month, n month without zero, M short month name, Y year.') + '</p>'
      + field(L('Per-language formats'), '<div class="mgrid">' + locs.map(function (l) {
        return '<div class="mloc3"><b>' + l + '</b>' + inp('locale_formats.' + l + '.decimal', 'opt', ' maxlength="1" placeholder="' + esc((M.currency || {}).decimal || '') + '" aria-label="' + L('Decimal mark') + '" style="width:60px"')
          + inp('locale_formats.' + l + '.thousands', 'opt', ' maxlength="1" placeholder="' + esc((M.currency || {}).thousands || '') + '" aria-label="' + L('Thousands mark') + '" style="width:60px"')
          + inp('locale_formats.' + l + '.date_format', 'opt', ' placeholder="' + esc(M.date_format || '') + '" aria-label="' + L('Date format') + '" style="width:110px"') + '</div>';
      }).join('') + '</div>', L('Only where a language writes numbers or dates differently from the market default. Empty = market default.'), 'locale_formats')
      + field(L('Preview'), '<div id="fprev" class="mprev"></div>', '', '');
    var vert = function (v, label) {
      var x = e.verticals[v];
      return field(label, '<label class="inl">' + inp('verticals.' + v, 'bool') + ' ' + label + '</label>',
        '<b>' + L('Off here means:') + '</b> ' + L('site types not built') + ': ' + list3(x.types) + '; ' + L('angles left out') + ': ' + list3(x.angles) + '; ' + L('pages left out') + ': ' + list3(x.pages) + '; ' + L('section designs left out') + ': ' + list3(x.designs) + '.', 'verticals.' + v);
    };
    var allowed = field(L('Minimum age'), inp('min_age', 'int', ' min="16" max="25" style="width:90px"'), L('Shown in the age check, the compliance bar and footers; the age check asks for it.'), 'min_age')
      + vert('sports', L('Sports betting')) + vert('casino', L('Casino'))
      + '<h4>' + L('Promotions') + '</h4><p class="muted mhelp">' + L('Unknown counts as restricted. A promo site needs at least one allowed type its angle uses; pages that show offers need any allowed type.') + ' ' + L('Pages that need any allowed type: {0}', list3(e.promo_pages)) + '</p>'
      + '<div class="card" style="padding:0;overflow-x:auto"><table><tbody>' + D.options.promotions.map(function (p) {
        return '<tr data-f="promotions.' + p + '"><td><code>' + p + '</code></td><td>' + sel('promotions.' + p, [['allowed', L('allowed')], ['restricted', L('restricted')], ['unknown', L('unknown')]]) + '<span class="ferr" hidden></span></td><td class="muted" style="font-size:13px">' + L('Used by') + ': ' + list3(e.promotions[p].angles) + '</td></tr>';
      }).join('') + '</tbody></table></div>';
    var pays = (M.payments || []).concat(D.payments.filter(function (p) { return (M.payments || []).indexOf(p) < 0; }));
    var payapps = field(L('Payment methods'), '<div class="mchecks">' + pays.map(function (p) {
      return '<label class="inl"><input type="checkbox" data-pay="' + esc(p) + '"' + ((M.payments || []).indexOf(p) >= 0 ? ' checked' : '') + '> ' + esc(p) + '</label>';
    }).join('') + '</div><div class="row" style="margin-top:6px"><input type="text" id="paynew" placeholder="' + L('Add another id') + '" style="width:200px"><button type="button" id="payadd">+</button></div>', L('Shown on payment sections and brand facts, in this order. Unknown ids are shown as written.'), 'payments')
      + '<div class="mgrid">' + field(L('iPhone apps'), sel('apps.ios', [['app-store', L('App Store')], ['none', L('none')]]), '', 'apps.ios')
      + field(L('Android apps'), sel('apps.android', [['play', L('Google Play')], ['apk', L('APK file')], ['both', L('Google Play and APK')], ['none', L('none')]]), '', 'apps.android') + '</div>'
      + '<p class="muted mhelp">' + L('Which install paths app sites and app pages offer.') + '</p>'
      + field(L('Brand domain ending'), inp('compliance.brand_domain_suffix', 'opt', ' placeholder=".bet.br" style="width:160px"'), L('Brand links must point to a domain with this ending (e.g. .bet.br). Empty = any domain.'), 'compliance.brand_domain_suffix');
    var texts = [['license_line', L('Licence line'), L('Footer and compliance bar. {brand}, {license_no} and {regulator} are filled in.')], ['regulator_default', L('Regulator'), L('Used when the brand has no regulator of its own.')],
      ['license_short', L('Licence badge'), L('Short label in trust bars and cards.')], ['age_notice', L('Age notice'), L('Compliance bar, age check and footers.')], ['self_exclusion', L('Self-exclusion'), L('Responsible gambling page and footers. Optional.')]];
    var comp = field(L('Compliance bar'), sel('compliance.strip', [['required', L('required on every site')], ['optional', L('optional (site decides)')]]), L('The strip with the age notice and licence line at the top of every page.'), 'compliance.strip')
      + field(L('Required blocks'), '<div class="mchecks">' + D.options.required_blocks.map(function (b) {
        return '<label class="inl"><input type="checkbox" data-rb="' + b + '"' + ((cp.required_blocks || []).indexOf(b) >= 0 ? ' checked' : '') + '> ' + L(b) + ' <code>' + b + '</code></label>';
      }).join('') + '</div>', L('These blocks are always on and cannot be removed in the editor.'), 'compliance.required_blocks')
      + texts.map(function (t) {
        return field(t[1], '<div class="mstack">' + locs.map(function (l) { return '<label class="mloc" data-f="compliance.' + t[0] + '.' + l + '"><span>' + l + '</span>' + inp('compliance.' + t[0] + '.' + l) + '<span class="ferr" hidden></span></label>'; }).join('') + '</div>', t[2], 'compliance.' + t[0]);
      }).join('')
      + field(L('Taglines'), '<div class="mstack">' + locs.map(function (l) { return '<label class="mloc"><span>' + l + '</span><textarea rows="2" data-tag="' + l + '">' + esc(((cp.taglines || {})[l] || []).join('\n')) + '</textarea></label>'; }).join('') + '</div>', L('One per line; footers rotate through them.'), 'compliance.taglines')
      + field(L('Helpline'), '<div class="mstack">' + locs.map(function (l) { return '<label class="mloc"><span>' + l + '</span><input type="text" data-help="' + l + '" value="' + esc((cp.helpline || {})[l] || '') + '"></label>'; }).join('') + '</div>', L('Footer and responsible gambling page. Empty = none.'), 'compliance.helpline')
      + field(L('Compliance notes'), '<textarea rows="3" data-p="compliance.notes" data-t="str">' + esc(cp.notes || '') + '</textarea>', L('For people: what to check with compliance. Not shown on sites.'), 'compliance.notes');
    var share = M.share_networks || D.options.share, allShare = share.concat(D.options.share.filter(function (n) { return share.indexOf(n) < 0; }));
    var pages = field(L('Pages required here'), '<div class="mchecks">' + Object.keys(D.pages).map(function (p) {
      return '<label class="inl"><input type="checkbox" data-rp="' + p + '"' + ((M.required_pages || []).indexOf(p) >= 0 ? ' checked' : '') + '> <b>' + esc(G.pageName(p)) + '</b> <span class="muted">(' + esc(D.pages[p].types.join(', ')) + ')</span></label>';
    }).join('') + '</div>', L('These pages are always built for site types that have them, and cannot be removed in the editor.'), 'required_pages')
      + field(L('Share buttons'), '<ol class="mshare">' + allShare.map(function (n) {
        return '<li><label class="inl"><input type="checkbox" data-share="' + n + '"' + (share.indexOf(n) >= 0 ? ' checked' : '') + '> ' + n + '</label><span class="sp"></span><button type="button" data-up="' + n + '" aria-label="' + L('Move up') + '">↑</button><button type="button" data-down="' + n + '" aria-label="' + L('Move down') + '">↓</button></li>';
      }).join('') + '</ol>', L('News and review pages show share buttons in this order. Untick a network to leave it out.'), 'share_networks')
      + field(L('Page language tags'), '<div class="mgrid">' + locs.map(function (l) { return '<label class="mloc"><span>' + l + '</span>' + inp('html_lang.' + l, 'opt', ' style="width:110px"') + '</label>'; }).join('') + '</div>', L('The lang attribute of every page, per language (search engines and screen readers read it).'), 'html_lang');
    var ai = '<p class="muted mhelp">' + L('Written from the settings above into every AI brief (after saving, it follows your changes):') + '</p><pre style="white-space:pre-wrap;font:12px/1.5 ui-monospace,monospace;background:var(--bg);border:1px solid var(--line);padding:10px;border-radius:8px;max-height:260px;overflow:auto">' + esc(D.ai_facts || '') + '</pre>'
      + '<p class="muted mhelp">' + L('Your extra rules, added after it: only what the settings cannot say (e.g. identification, self-exclusion services, domains).') + '</p><textarea id="mnotes" rows="14" style="width:100%;font:13px/1.5 ui-monospace,monospace">' + esc(D.notes) + '</textarea>';
    var raw = '<p class="muted mhelp">' + L('Every field of the market file. Edit here for anything the form does not cover, then use this JSON and save.') + '</p><textarea id="mraw" rows="18" style="width:100%;font:12px/1.5 ui-monospace,monospace"></textarea><div class="row"><button type="button" id="mrawuse">' + L('Use this JSON') + '</button><span class="muted" id="mrawmsg"></span></div>';
    return sec('basics', L('Basics'), basics) + sec('formats', L('Formats'), formats) + sec('allowed', L('What is allowed'), allowed) + sec('payapps', L('Payments and apps'), payapps)
      + sec('compliance', L('Compliance texts'), comp) + sec('pages', L('Pages and sharing'), pages) + sec('ai', L('AI notes'), ai)
      + '<details class="more"><summary>' + L('All of it as JSON') + '</summary>' + raw + '</details>';
  }
  function wire(id) {
    var f = document.getElementById('mform');
    var touch = function () { dirty = true; document.getElementById('mdirty').hidden = false; previews(); };
    [].forEach.call(f.querySelectorAll('[data-p]'), function (el) {
      el.addEventListener(el.tagName === 'SELECT' || el.type === 'checkbox' ? 'change' : 'input', function () {
        var t = el.dataset.t, v = t === 'bool' ? el.checked : t === 'int' ? (el.value === '' ? null : parseInt(el.value, 10)) : el.value;
        if (t === 'opt' && v === '') { var ks = el.dataset.p.split('.'), last = ks.pop(), par = get(M, ks.join('.')); if (par) delete par[last]; } else set(M, el.dataset.p, v);
        touch();
      });
    });
    [].forEach.call(f.querySelectorAll('[data-loc]'), function (el) {
      el.onchange = function () {
        var on = [].filter.call(f.querySelectorAll('[data-loc]'), function (x) { return x.checked; }).map(function (x) { return x.dataset.loc; });
        M.locales = on; if (on.indexOf(M.default_locale) < 0) M.default_locale = on[0] || '';
        dirty = true; draw(id, true);
      };
    });
    var listOf = function (attr, path) {
      [].forEach.call(f.querySelectorAll('[' + attr + ']'), function (el) {
        el.onchange = function () { set(M, path, [].filter.call(f.querySelectorAll('[' + attr + ']'), function (x) { return x.checked; }).map(function (x) { return x.getAttribute(attr); })); touch(); };
      });
    };
    listOf('data-pay', 'payments'); listOf('data-rb', 'compliance.required_blocks'); listOf('data-rp', 'required_pages'); listOf('data-share', 'share_networks');
    document.getElementById('payadd').onclick = function () {
      var v = document.getElementById('paynew').value.trim().toLowerCase();
      if (!v) return;
      if ((M.payments || []).indexOf(v) < 0) (M.payments = M.payments || []).push(v);
      if (D.payments.indexOf(v) < 0) D.payments.push(v);
      dirty = true; draw(id, true);
    };
    [].forEach.call(f.querySelectorAll('[data-up],[data-down]'), function (b) {
      b.onclick = function () {
        var n = b.dataset.up || b.dataset.down, order = [].map.call(f.querySelectorAll('[data-share]'), function (x) { return x.dataset.share; }), i = order.indexOf(n), j = b.dataset.up ? i - 1 : i + 1;
        if (j < 0 || j >= order.length) return;
        order[i] = order[j]; order[j] = n;
        var on = (M.share_networks || D.options.share);
        M.share_networks = order.filter(function (x) { return on.indexOf(x) >= 0; });
        dirty = true; draw(id, true);
      };
    });
    [].forEach.call(f.querySelectorAll('[data-tag]'), function (el) {
      el.oninput = function () { set(M, 'compliance.taglines.' + el.dataset.tag, el.value.split('\n').map(function (s) { return s.trim(); }).filter(Boolean)); touch(); };
    });
    [].forEach.call(f.querySelectorAll('[data-help]'), function (el) {
      el.oninput = function () {
        var h = {}; [].forEach.call(f.querySelectorAll('[data-help]'), function (x) { if (x.value.trim()) h[x.dataset.help] = x.value.trim(); });
        M.compliance.helpline = Object.keys(h).length ? h : null; touch();
      };
    });
    document.getElementById('mnotes').oninput = touch;
    var raw = document.getElementById('mraw');
    f.querySelector('details.more').ontoggle = function () { raw.value = JSON.stringify(M, null, 2); };
    document.getElementById('mrawuse').onclick = function () {
      try { M = JSON.parse(raw.value); dirty = true; draw(id, true); var mm = document.getElementById('mrawmsg'); if (mm) mm.textContent = L('JSON applied to the form. Save to keep it.'); }
      catch (x) { document.getElementById('mrawmsg').textContent = L('Not valid JSON: {0}', x.message); }
    };
    f.onsubmit = function (ev) {
      ev.preventDefault();
      var msg = document.getElementById('msave');
      [].forEach.call(document.querySelectorAll('.ferr'), function (x) { x.hidden = true; });
      [].forEach.call(document.querySelectorAll('.bad-f'), function (x) { x.classList.remove('bad-f'); });
      api('markets/' + id, { market: M, notes: document.getElementById('mnotes').value }, 'PUT').then(function (r) {
        dirty = false;
        msg.className = 'okmsg';
        msg.textContent = r.changes && r.changes.length ? L('Saved.') + ' ' + L('Changed: {0}', r.changes.join(', ')) : L('No changes to save.');
        load(id, msg.textContent);
      }).catch(function (x) {
        msg.className = 'err';
        msg.textContent = L(x.message);
      });
    };
  }
  /* field errors come back as { path: message }; show each next to its field */
  function showErrors(fields) {
    var first = null;
    Object.keys(fields).forEach(function (p) {
      var el = document.querySelector('[data-f="' + p + '"]'), ks = p.split('.');
      while (!el && ks.length > 1) { ks.pop(); el = document.querySelector('[data-f="' + ks.join('.') + '"]'); }
      if (!el) return;
      el.classList.add('bad-f');
      var e = el.querySelector('.ferr');
      if (e) { e.hidden = false; e.textContent = (ks.join('.') !== p ? p + ': ' : '') + L(fields[p]); }
      first = first || el;
    });
    if (first) first.scrollIntoView({ block: 'center' });
  }
  function draw(id, keep) {
    var scroll = window.scrollY, u = D.usage, m = D.market;
    var inUse = u.jobs.length || u.demos.length;
    main.innerHTML = '<p><a href="#/markets">' + L('← Markets') + '</a></p>'
      + '<div class="row"><h2>' + esc(M.name || id) + '</h2>' + chip(esc(id.toUpperCase())) + (M.demo_only ? chip(L('demo only'), 'auto') : '') + '<span class="sp"></span>'
      + '<a class="btn" href="#/markets/' + id + '/history">' + L('History') + ' (' + D.log.length + ')</a>'
      + '<button class="danger" id="mdel"' + (inUse ? ' disabled title="' + L('Cannot be deleted while jobs or demos use it.') + '"' : '') + '>' + L('Delete market') + '</button></div>'
      + '<p class="muted">' + L('Last change') + ': ' + esc(D.changed) + '</p>'
      + (D.stale ? '<p class="note">' + L('{0} built sites were made before the last change and still show the old rules. Rebuild their jobs to apply it.', D.stale) + '</p>' : '')
      + '<div class="mtop"><section class="card"><h3>' + L('Used by') + '</h3>'
      + '<p><b>' + L('Jobs') + ':</b> ' + (u.jobs.length ? u.jobs.slice(0, 12).map(function (j) { return '<a href="#/j/' + j.id + '">' + esc(j.project) + ' / ' + esc(j.name) + '</a> <span class="muted">(' + j.sites + ')</span>'; }).join(', ') + (u.jobs.length > 12 ? ' …' : '') : L('None.')) + '</p>'
      + '<p><b>' + L('Demos') + ':</b> ' + (u.demos.length ? esc(u.demos.join(', ')) : L('None.')) + '</p>'
      + '<p><b>' + L('Brands licensed here') + ':</b> ' + (u.brands.length ? esc(u.brands.join(', ')) : L('None.')) + '</p></section>'
      + '<section class="card"><h3>' + L('Built here') + '</h3><p class="muted mhelp">' + L('Builds run as the sites of this market would. A type that is not available cannot be chosen in the job form for this market.') + '</p><div id="mavail"><p class="notice"><span class="spin" aria-hidden="true"></span><span>' + L('Checking what can be built here (test builds of every site type)…') + '</span></p></div></section></div>'
      + '<nav class="mnav">' + [['basics', 'Basics'], ['formats', 'Formats'], ['allowed', 'What is allowed'], ['payapps', 'Payments and apps'], ['compliance', 'Compliance texts'], ['pages', 'Pages and sharing'], ['ai', 'AI notes']]
        .map(function (x) { return '<a href="javascript:void 0" data-go="m-' + x[0] + '">' + L(x[1]) + '</a>'; }).join('') + '</nav>'
      + '<form id="mform" class="mform" novalidate>' + form()
      + '<div class="msavebar"><button class="primary">' + L('Save changes') + '</button><span class="chip warn" id="mdirty"' + (dirty ? '' : ' hidden') + '>' + L('Unsaved changes') + '</span><span id="msave" class="muted"></span></div></form>';
    wire(id);
    previews();
    [].forEach.call(main.querySelectorAll('[data-go]'), function (a) { a.onclick = function () { document.getElementById(a.dataset.go).scrollIntoView({ behavior: 'smooth' }); }; });
    document.getElementById('mdel').onclick = function () {
      if (confirm(L('Delete market {0}? Its file and AI notes are moved to the deleted markets, where you can restore them.', id))) api('markets/' + id, null, 'DELETE').then(function () { location.hash = '#/markets'; }).catch(G.fail);
    };
    avail(id);
    if (keep) window.scrollTo(0, scroll);
  }
  var AV = {};
  function avail(id) {
    var put = function (d) {
      var el = document.getElementById('mavail');
      if (!el) return;
      el.innerHTML = '<table><tbody>' + Object.keys(d.types).map(function (t) {
        var x = d.types[t], opts = Object.keys(x.options || {}), lab = L(d.labels[t] || t);
        return '<tr><td>' + esc(lab) + '</td><td>' + (x.ok ? (opts.length ? chip(L('Some options off'), 'warn') : chip(L('Available'), 'ok')) : chip(L('Not available'), 'bad'))
          + (x.reason ? '<div class="muted" style="font-size:12px;margin-top:4px">' + esc(x.reason) + '</div>' : '')
          + (opts.length ? '<div class="muted" style="font-size:12px;margin-top:4px">' + opts.map(function (a) { return esc(a) + ': ' + Object.keys(x.options[a]).map(esc).join(', '); }).join('; ') + '</div>' : '') + '</td></tr>';
      }).join('') + '</tbody></table>';
    };
    if (AV[id] && AV[id].v === D.changed) return put(AV[id].d);
    api('markets/' + id + '/availability').then(function (d) { AV[id] = { v: D.changed, d: d }; put(d); }).catch(function (x) { var el = document.getElementById('mavail'); if (el) el.innerHTML = '<p class="err">' + esc(x.message) + '</p>'; });
  }
  function load(id, msg) {
    api('markets/' + id).then(function (d) {
      D = d; M = JSON.parse(JSON.stringify(d.market)); dirty = false;
      draw(id, !!msg);
      if (msg) { var el = document.getElementById('msave'); el.className = 'okmsg'; el.textContent = msg; }
    }).catch(G.fail);
  }
  function edit(id) {
    load(id);
  }
  /* PUT errors carry the field list: catch it from the response itself */
  var putMarket = function (id, body) {
    return fetch('/generator/api/markets/' + id, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) { var e = new Error(j.error || r.statusText); e.fields = j.fields; throw e; } return j; }); });
  };
  api = (function (orig) {
    return function (path, body, method) { return method === 'PUT' && /^markets\/[a-z]+$/.test(path) ? putMarket(path.split('/')[1], body).catch(function (x) { if (x.fields) showErrors(x.fields); throw x; }) : orig(path, body, method); };
  })(api);

  /* ---------- history */
  function history(id) {
    api('markets/' + id).then(function (d) {
      main.innerHTML = '<p><a href="#/markets/' + id + '">← ' + esc(d.market.name) + '</a></p><h2>' + L('History') + ': ' + esc(d.market.name) + '</h2>'
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
          api('markets/' + id + '/version?v=' + encodeURIComponent(b.dataset.v)).then(function (v) {
            box.hidden = false; b.textContent = L('Hide');
            box.innerHTML = '<p><b>' + L('Version before') + ' ' + esc(b.dataset.v.slice(0, 15)) + '.</b> ' + (v.changes.length ? L('Differs from now in: {0}', esc(v.changes.join(', '))) : L('Same as now.')) + '</p>'
              + '<pre style="max-height:320px;overflow:auto;font-size:12px;background:var(--bg);padding:10px;border-radius:8px">' + esc(JSON.stringify(v.market, null, 2)) + '</pre>'
              + '<button class="primary" data-restore>' + L('Put this version back') + '</button>';
            box.querySelector('[data-restore]').onclick = function () {
              if (confirm(L('Put this version back? The current one is kept in the history.'))) api('markets/' + id + '/restore', { version: b.dataset.v }).then(function () { location.hash = '#/markets/' + id; }).catch(G.fail);
            };
          }).catch(G.fail);
        };
      });
    }).catch(G.fail);
  }

  window.addEventListener('beforeunload', function (e) { if (dirty && /^#\/markets\/[a-z]+$/.test(location.hash)) { e.preventDefault(); e.returnValue = ''; } });
  G.markets = function (h) {
    if (h[1] === 'new') return create();
    if (h[1] && h[2] === 'history') return history(h[1]);
    if (h[1]) return edit(h[1]);
    list();
  };
})();
