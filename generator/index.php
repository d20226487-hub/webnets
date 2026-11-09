<?php
/**
 * Website network generator app: /generator (UI) and /generator/api/* (JSON). Served by build/local-router.php.
 * Local use only: it writes data/ (SQLite, site configs) and dist/generated/.
 */
if (str_starts_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/generator/api')) {
    require __DIR__ . '/api.php';
    return;
}
header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>WebNets</title>
<script>try { var t = localStorage.getItem('gen-theme'); if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t; } catch (e) {}</script>
<style>
:root{--bg:#F6F7F9;--panel:#FFFFFF;--line:#E1E4EA;--text:#14171F;--muted:#5D6575;--accent:#4F46E5;--accent-2:#EEF0FF;--ok:#13795B;--okbg:#E7F6EF;--warn:#A15C00;--warnbg:#FFF4E0;--bad:#B42318;--badbg:#FDECEA;color-scheme:light}
@media (prefers-color-scheme:dark){:root:not([data-theme=light]){--bg:#0E1116;--panel:#161A22;--line:#262C38;--text:#E7EAF0;--muted:#99A1B3;--accent:#8B85FF;--accent-2:#1E2140;--ok:#4ADE9B;--okbg:#12291F;--warn:#F2B35B;--warnbg:#2B2112;--bad:#FF8A80;--badbg:#2E1513;color-scheme:dark}}
:root[data-theme=dark]{--bg:#0E1116;--panel:#161A22;--line:#262C38;--text:#E7EAF0;--muted:#99A1B3;--accent:#8B85FF;--accent-2:#1E2140;--ok:#4ADE9B;--okbg:#12291F;--warn:#F2B35B;--warnbg:#2B2112;--bad:#FF8A80;--badbg:#2E1513;color-scheme:dark}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:16px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;-webkit-font-smoothing:antialiased}
a{color:var(--accent)}header.top{display:flex;flex-wrap:wrap;align-items:center;gap:8px 18px;padding:12px 24px;border-bottom:1px solid var(--line);background:var(--panel)}
header.top h1{margin:0;font-size:18px}header.top nav{display:flex;flex-wrap:wrap;gap:4px;font-size:14px}header.top .sp{flex:1}
header.top nav a{padding:6px 12px;border-radius:8px;color:var(--muted);text-decoration:none;font-weight:600}
header.top nav a:hover{color:var(--text);background:var(--bg)}header.top nav a[aria-current]{color:var(--accent);background:var(--accent-2)}
.prefs{display:flex;gap:6px;align-items:center}.prefs select{padding:5px 8px;font-size:13px}
details.more{margin-top:16px}details.more>summary{cursor:pointer;font-weight:600;padding:10px 14px;border:1px solid var(--line);border-radius:10px;background:var(--panel);list-style-position:inside}
details.more[open]>summary{margin-bottom:6px}
.bpanel{margin:10px 0 4px;padding:14px 16px;border:1px solid var(--line);border-radius:10px;background:var(--panel);display:grid;gap:12px;max-width:760px}
.bhead{display:flex;flex-wrap:wrap;align-items:center;gap:6px 14px}.bhead b{margin-right:auto}
button.link{background:none;border:0;padding:4px 2px;color:var(--accent);font-weight:600}
.btypes{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,240px),1fr));gap:8px 16px}.btypes label{display:flex;gap:8px;align-items:center;font-size:14px}
[hidden]{display:none!important}
/* markets */.mtop{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,380px),1fr));gap:16px;margin:14px 0}.mtop h3{margin-top:0}.mtop p{margin:6px 0;font-size:14px}
.mnav{position:sticky;top:0;z-index:3;display:flex;flex-wrap:wrap;gap:4px;padding:10px 0;background:var(--bg);border-bottom:1px solid var(--line);margin-bottom:14px}.mnav a{padding:6px 10px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;color:var(--muted)}.mnav a:hover{background:var(--panel);color:var(--text)}
.mform{display:grid;gap:16px}.msec h3{margin:0 0 14px;font-size:18px}.msec h4{margin:18px 0 6px}.msec{scroll-margin-top:60px}.mhelp{font-size:13px!important;line-height:1.5}
.mform .field{display:grid;gap:6px;margin-bottom:18px}.mform .lbl{font-weight:650}.mform input[type=text],.mform textarea{font:14px system-ui,sans-serif;padding:8px 10px;border-radius:8px;border:1px solid var(--line);background:var(--panel);color:var(--text);max-width:100%}
.mform textarea{width:100%;resize:vertical}.inl{display:inline-flex;align-items:center;gap:8px;font-size:14px}.mchecks{display:flex;flex-wrap:wrap;gap:8px 20px}
.mgrid{display:flex;flex-wrap:wrap;gap:4px 18px}.mstack{display:grid;gap:6px}.mloc{display:grid;grid-template-columns:34px 1fr;align-items:center;gap:8px}.mloc>span:first-child{font:600 12px ui-monospace,monospace;color:var(--muted)}
.mloc input{width:100%}.mloc3{display:flex;gap:6px;align-items:center}.mprev{display:grid;gap:4px;font-size:15px}.mshare{margin:0;padding-left:22px;display:grid;gap:4px;max-width:360px}.mshare li{display:flex;align-items:center;gap:6px}.mshare button{padding:2px 8px}
.ferr{color:var(--bad);font-size:13px}.bad-f>input,.bad-f input,.bad-f select{border-color:var(--bad)!important}.okmsg{color:var(--ok);font-size:14px}
.msavebar{position:sticky;bottom:0;z-index:3;display:flex;flex-wrap:wrap;align-items:center;gap:12px;padding:12px 16px;background:var(--panel);border:1px solid var(--line);border-radius:12px;box-shadow:0 -4px 16px rgba(0,0,0,.06)}
.mkt-table td{vertical-align:middle}.tdes{display:flex;flex-wrap:wrap;gap:4px 12px}.tdes .inl{font-size:13px}.tsec td{vertical-align:top}.tsec td button{padding:2px 7px;font-size:12px}.tpages{display:flex;flex-wrap:wrap;gap:6px}.tpages button{padding:6px 10px;font-size:13px}.tangles{display:grid;gap:10px}.tangle{display:grid;gap:8px;padding:12px 14px}.tangle .field{margin-bottom:6px}.tlim{gap:6px 12px;font-size:13px}.trules{display:grid;gap:6px;margin-bottom:8px}.mform select{max-width:380px}
.bfoot{display:flex;flex-wrap:wrap;align-items:center;gap:12px}.bfoot a[aria-disabled]{opacity:.5;pointer-events:none}.bnote{margin:0;font-size:13px}
main{max-width:1280px;margin:0 auto;padding:20px 24px 60px}h2{margin:0 0 4px;font-size:22px}h3{margin:22px 0 10px;font-size:16px}
.muted{color:var(--muted)}.row{display:flex;flex-wrap:wrap;align-items:center;gap:10px}.sp{flex:1}
button,.btn{font:600 14px system-ui,sans-serif;border-radius:8px;border:1px solid var(--line);background:var(--panel);color:var(--text);padding:8px 14px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
button.primary,.btn.primary{background:var(--accent);border-color:var(--accent);color:#fff}button.danger{color:var(--bad)}button:disabled{opacity:.5;cursor:default}
button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
input[type=text],input[type=number],select{font:14px system-ui,sans-serif;padding:8px 10px;border-radius:8px;border:1px solid var(--line);background:var(--panel);color:var(--text);max-width:100%}
.card{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:16px}
.list{display:grid;gap:10px;margin-top:14px}.list a.item{display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--panel);border:1px solid var(--line);border-radius:12px;color:var(--text);text-decoration:none}
.list a.item:hover{border-color:var(--accent)}.chip{display:inline-block;padding:2px 9px;border-radius:999px;background:var(--bg);border:1px solid var(--line);font-size:12px;color:var(--muted);white-space:nowrap}
.chip.ok{background:var(--okbg);color:var(--ok);border-color:transparent}.chip.warn{background:var(--warnbg);color:var(--warn);border-color:transparent}.chip.bad{background:var(--badbg);color:var(--bad);border-color:transparent}.chip.auto{border-style:dashed}
form .field{display:grid;gap:6px;margin-bottom:16px}form label.lbl{font-weight:600}
table{border-collapse:collapse;width:100%}th,td{text-align:left;padding:8px 10px;border-bottom:1px solid var(--line);vertical-align:top;font-size:14px}th{font-size:12px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.types td{padding:10px}.wn{display:block;margin-top:4px;padding:6px 8px;border-radius:8px;background:var(--warnbg);color:var(--warn);font-size:12px;max-width:300px}
.axis label.blk{color:var(--warn)}.axis label.blk::after{content:"⚠";font-size:12px}
.brows{display:grid;gap:8px}.brow{display:grid;grid-template-columns:minmax(140px,1.4fr) minmax(140px,1.4fr) auto minmax(120px,1fr) auto;gap:8px;align-items:center}
.brow .cl{display:inline-flex;align-items:center;gap:6px;font-size:13px;white-space:nowrap}
@media (max-width:720px){.types table,.types tbody,.types tr,.types td{display:block;width:auto}.types thead{display:none}.types tr{border-top:1px solid var(--line);padding:6px 0}.types td{padding:6px 4px}.wn{max-width:none}}
@media (max-width:720px){.brow{grid-template-columns:1fr 1fr;padding:10px;border:1px solid var(--line);border-radius:10px}.brow input[type=text]{grid-column:1/-1}}.axis{display:flex;flex-wrap:wrap;gap:6px 14px;margin:2px 0 6px}.axis b{font-size:12px;color:var(--muted);font-weight:600;width:100%}
.axis label,.brands label{display:inline-flex;align-items:center;gap:6px;font-size:14px}.brands{display:flex;flex-wrap:wrap;gap:8px 18px}
.colors{display:flex;flex-wrap:wrap;gap:14px}.colors .c{display:grid;gap:6px;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:var(--panel)}
.colors input[type=color]{width:56px;height:36px;padding:0;border:1px solid var(--line);border-radius:8px;background:none}
.note{padding:10px 12px;border-radius:10px;background:var(--warnbg);color:var(--warn);font-size:14px}.err{padding:10px 12px;border-radius:10px;background:var(--badbg);color:var(--bad)}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
/* forms (job form, editors, settings): larger, calmer type */#jf .lbl,#ef .lbl,.card .lbl{font-size:15px;font-weight:650;letter-spacing:-.005em}#jf .field,#ef .field{gap:8px;margin-bottom:22px}#jf .field>.muted,#ef .muted,#jf .field>span.muted{font-size:14px!important;line-height:1.55}#jf input[type=text],#jf input[type=number],#jf select,#ef select,#ef input{font-size:15px;padding:10px 12px}#jf .axis b{font-size:13px;color:var(--text);opacity:.75;letter-spacing:.02em}#jf .axis label{font-size:15px}#jf .axis{gap:8px 18px;margin:4px 0 10px}#jf .axis small{font-size:13px!important;line-height:1.5}#jf .types td{padding:12px 10px}#jf .types strong{font-size:16px}
#sites>section{margin-top:40px;padding-top:28px;border-top:1px solid var(--line)}#sites>section:first-child{margin-top:16px;padding-top:0;border-top:0}#sites>section>h3{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:0 0 18px;font-size:24px;font-weight:700;letter-spacing:-.01em}#sites>section>h3 .muted{font-size:18px;font-weight:500}@media (max-width:720px){#sites>section{margin-top:28px;padding-top:20px}#sites>section>h3{font-size:20px}}
.site{background:var(--panel);border:1px solid var(--line);border-radius:12px;overflow:hidden;display:flex;flex-direction:column}
.thumb{position:relative;aspect-ratio:16/10;overflow:hidden;background:var(--bg);border-bottom:1px solid var(--line)}
.thumb iframe{position:absolute;top:0;left:0;width:1280px;height:800px;border:0;transform-origin:0 0;pointer-events:none}
.thumb img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:top}
.pager{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin:10px 0 4px}.pager button{padding:5px 11px;font-size:13px}.pager button[aria-current]{background:var(--accent);border-color:var(--accent);color:#fff}
.thumb .st{position:absolute;inset:0;display:grid;place-items:center;color:var(--muted);font-size:14px;text-align:center;padding:12px}
.site .body{padding:12px 14px;display:grid;gap:8px}.site .name{font-weight:700}.chips{display:flex;flex-wrap:wrap;gap:6px}
.site .acts{display:flex;flex-wrap:wrap;gap:8px;padding:0}.site .acts .btn{padding:6px 10px;font-size:13px}
.bar{height:6px;border-radius:6px;background:var(--line);overflow:hidden;margin:10px 0}.bar i{display:block;height:100%;background:var(--accent);transition:width .3s}
.matrix{overflow-x:auto}.matrix td.v{text-align:center;font-variant-numeric:tabular-nums;white-space:nowrap}.matrix td.v small{display:block;color:var(--muted);font-size:11px}
.matrix td.low{background:var(--warnbg)}.matrix td.self{background:var(--bg)}
.dt td,.dt th{vertical-align:top}.dt .pct{font-weight:700;font-variant-numeric:tabular-nums}.dt small{display:block;color:var(--muted);font-size:12px}
.notice{display:flex;gap:10px;align-items:flex-start;margin:10px 0;padding:10px 12px;border-radius:10px;background:var(--warnbg);color:var(--warn);font-size:14px}
.spin{flex:none;width:14px;height:14px;margin-top:2px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:spin .8s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}
@media (prefers-reduced-motion:reduce){.spin{animation:none}}
#diff.stale table,#diff.stale .ov{opacity:.45}
.ov td,.ov th{text-align:left}.ov a{color:inherit}
.legend{display:flex;flex-wrap:wrap;gap:6px 10px;margin:8px 0 4px;font-size:13px}
details.warn summary{cursor:pointer;color:var(--warn);font-size:13px}details.warn ul{margin:6px 0 0;padding-left:18px;font-size:12px;color:var(--muted)}
@media (max-width:640px){main{padding:16px}header.top{padding:12px 16px}.types th:nth-child(3){display:none}}
</style>
</head>
<body>
<header class="top"><h1 id="apptitle">WebNets</h1><nav id="topnav"></nav><span class="sp"></span>
<div class="prefs"><select id="ui-lang"><option value="en">EN</option><option value="ru">RU</option></select><select id="ui-theme"></select></div></header>
<main id="main" aria-live="polite"></main>
<script>
(function () {
  var main = document.getElementById('main'), CAT = null, poll = null;
  var RU = {
    'Projects': 'Проекты', 'Thresholds': 'Пороги', 'Gallery': 'Галерея', 'AI brief settings': 'Настройки ИИ-брифа',
    'Language': 'Язык', 'Theme': 'Тема', 'Auto': 'Авто', 'Light': 'Светлая', 'Dark': 'Тёмная',
    'Difference thresholds': 'Пороги различий',
    'How different every generated site must be from the sites it is compared with (same type). The generator checks each site after it is built; a site under a threshold gets a new plan (new seed, another style, the next hero layout, then a free section order and free layouts) and is rebuilt, up to the rebuilds limit. It keeps the best version; a site that still misses a threshold is flagged on the job page.':
      'Насколько каждый сгенерированный сайт должен отличаться от сайтов, с которыми его сравнивают (того же типа). Генератор проверяет каждый сайт после сборки; сайт ниже порога получает новый план (новый seed, другой стиль, следующий макет первого экрана, затем свободный порядок секций и свободные макеты) и пересобирается, но не больше заданного числа раз. Сохраняется лучшая версия; сайт, который так и не достиг порога, отмечается на странице задания.',
    'Minimum code difference, %': 'Минимальное различие кода, %',
    'Share of CSS rules and HTML that differ (renamed classes, whitespace and order do not count). Checked in milliseconds after each build. Default {0}.':
      'Доля отличающихся CSS-правил и HTML (переименованные классы, пробелы и порядок не учитываются). Проверяется за миллисекунды после каждой сборки. По умолчанию {0}.',
    'Check visual difference': 'Проверять визуальное различие',
    'Minimum visual difference, %': 'Минимальное визуальное различие, %',
    'Share of the first screen that visibly changes, desktop (1280×800) and phone (375×667) averaged. Measured from screenshots once the job is built, which adds about 2 seconds per site. Default {0}.':
      'Доля первого экрана, которая заметно отличается, в среднем для компьютера (1280×800) и телефона (375×667). Измеряется по скриншотам после сборки задания, около 2 секунд на сайт. По умолчанию {0}.',
    'Style variants': 'Варианты стилей', 'Each preset also in its light or dark version and in 6 colour rotations (120 styles instead of 10); a site that only looks too alike swaps light and dark first. Off: the 10 presets as they are.': 'Каждый пресет также в светлой или тёмной версии и в 6 поворотах цвета (120 стилей вместо 10); сайт, который похож только внешне, сначала меняет светлую тему на тёмную или наоборот. Выключено: 10 пресетов как есть.',
    'Compare with': 'Сравнивать с', 'Sites of every job': 'Сайтами всех заданий', 'Sites of the same project': 'Сайтами того же проекта', 'Sites of the same job only': 'Только сайтами того же задания',
    'Rebuilds per check': 'Пересборок на проверку',
    'How many times a site under a threshold is re-planned and rebuilt, for the code check and again for the visual check. 0 = measure only, never rebuild. Default {0}.':
      'Сколько раз сайт ниже порога перепланируется и пересобирается: для проверки кода и ещё раз для визуальной проверки. 0 = только измерять, не пересобирать. По умолчанию {0}.',
    'Save': 'Сохранить', 'Saved.': 'Сохранено.',
    'New thresholds apply to the next build. Use Rebuild all on a job to check its sites again.': 'Новые пороги действуют со следующей сборки. Чтобы проверить сайты задания заново, нажмите «Пересобрать всё».',
    'A project holds the jobs that generate its sites. Sites are compared inside the project and against every other job.': 'Проект объединяет задания, которые генерируют его сайты. Сайты сравниваются внутри проекта и со всеми остальными заданиями.',
    'New project name': 'Название нового проекта', 'Create project': 'Создать проект', 'No projects yet.': 'Проектов пока нет.',
    '{0} jobs': 'заданий: {0}', '{0} sites': 'сайтов: {0}', '{0} / {1} sites': '{0} / {1} сайтов',
    'New job': 'Новое задание', 'Delete project': 'Удалить проект',
    'Other jobs are not compared with this one': 'Другие задания с этим не сравниваются', 'left out of comparisons': 'не участвует в сравнении',
    'No jobs yet. Create one to generate sites.': 'Заданий пока нет. Создайте задание, чтобы сгенерировать сайты.',
    'Delete this project, its jobs and every generated site?': 'Удалить проект, его задания и все сгенерированные сайты?',
    'Project': 'Проект', 'Edit job': 'Изменить задание',
    'Saving rebuilds the job. Sites that stay (same type and number) keep their seed and style; extra sites are added, sites over the new count are deleted.':
      'Сохранение пересобирает задание. Сайты, которые остаются (тот же тип и номер), сохраняют seed и стиль; недостающие добавляются, лишние удаляются.',
    'Each site gets its own seed, style, layouts, code style and implementation; the generator spreads them so sites differ.': 'Каждый сайт получает свой seed, стиль, макеты, стиль кода и реализацию; генератор распределяет их, чтобы сайты различались.',
    'Job name': 'Название задания', 'e.g. Brazil casino network, wave 1': 'например, Сеть казино Бразилия, волна 1', 'Market': 'Рынок',
    'Site types, how many and which variations': 'Типы сайтов, сколько и какие вариации', 'Checking what this market allows…': 'Проверяем, что разрешено на этом рынке…',
    'Brands': 'Бренды', '+ Add brand': '+ Добавить бренд',
    'Your own brands. Single-brand sites are spread across them; casino reviews and news sites list all of them. Domain is optional (links use it); colour is used by the "brand colours" design. Logos are generated from the initials, other brand data (bonuses, reviews, ratings, games) is placeholder.':
      'Ваши бренды. Сайты одного бренда распределяются между ними; обзоры казино и новостные сайты показывают все. Домен необязателен (на него ведут ссылки); цвет используется в дизайне «Цвета бренда». Логотипы строятся из инициалов, остальные данные бренда (бонусы, отзывы, рейтинги, игры) — заглушки.',
    'Copy': 'Тексты', 'Demo copy, with your brand names': 'Демо-тексты с вашими брендами', 'Placeholder text': 'Текст-заглушка',
    'The demo copy is in Russian: sites in Russian (demo market, Kazakhstan) get it; sites in other languages (Australia, Brazil) get placeholder text.':
      'Демо-тексты на русском: их получают русскоязычные сайты (демо-рынок, Казахстан); сайты на других языках (Австралия, Бразилия) получают заглушки.',
    'Colours': 'Цвета', 'Brand colours': 'Цвета бренда',
    'Any (each design in turn)': 'Любой (все по очереди)', 'Comparison table': 'Таблица сравнения', 'Cards': 'Карточки', 'Podium': 'Пьедестал', 'Download catalog (like uptodown)': 'Каталог загрузок (как uptodown)', 'APK page (like apkpure)': 'Страница APK (как apkpure)',
    'Download hub (like softonic)': 'Центр загрузок (как softonic)', 'Logo grid (like casino.ru)': 'Сетка логотипов (как casino.ru)', 'Offer cards (like liga.net)': 'Карточки бонусов (как liga.net)',
    'Portal (like affgambler.ru)': 'Портал (как affgambler.ru)', 'Bonus cards (like slotcatalog)': 'Карточки «Получить бонус» (как slotcatalog)',
    'Brand colours: the colour of the site itself, as it lists several brands.': '«Цвета бренда»: собственный цвет сайта, ведь на нём несколько брендов.',
    'The first screen. Google Play and App Store looks bring their own colours, so Colours does not change them.': 'Первый экран. У видов «Как Google Play» и «Как App Store» свои цвета: «Цвета» на них не влияют.',
    'The ranking on the first screen.': 'Рейтинг на первом экране.', 'Primary': 'Основной', 'buttons, links, highlights': 'кнопки, ссылки, акценты', 'Secondary': 'Дополнительный', 'second highlight colour': 'второй цвет акцентов',
    'Accent': 'Акцентный', 'hero background': 'фон первого экрана', 'Automatic': 'Автоматически',
    'Fixed colours are shared by every site in the job, so the sites look more alike. Leave them automatic for the most different sites.': 'Заданные цвета общие для всех сайтов задания, поэтому сайты выглядят похожее. Оставьте автоматический выбор для максимально разных сайтов.',
    'Generate sites': 'Сгенерировать сайты', 'Save and rebuild': 'Сохранить и пересобрать',
    '{0} sites will be generated': 'Будет сгенерировано сайтов: {0}',
    'Brand name': 'Название бренда', 'Domain (optional), e.g. mybrand.com': 'Домен (необязательно), например mybrand.com', 'Brand domain': 'Домен бренда', 'Brand colour': 'Цвет бренда',
    'Products': 'Продукты', 'Remove brand': 'Удалить бренд',
    'Checking the market rules… warnings appear here in a few seconds. You can fill in the form meanwhile.': 'Проверяем правила рынка… предупреждения появятся здесь через несколько секунд. Форму можно заполнять.',
    'Type': 'Тип', 'Sites': 'Сайты', 'Variations (tick one or more)': 'Вариации (одна или несколько)',
    'No content variations; layouts and angles differ per site.': 'Вариаций контента нет; макеты и подачи различаются по сайтам.',
    'You can still build it: the sites will carry this warning.': 'Собрать всё равно можно: сайты получат это предупреждение.', 'No warning with: {0}.': 'Без предупреждения: {0}.',
    'How many {0} sites': 'Сколько сайтов «{0}»',
    'Tick at least one site type.': 'Отметьте хотя бы один тип сайта.', 'Add at least one brand.': 'Добавьте хотя бы один бренд.',
    'Give the job a name.': 'Назовите задание.', 'Pick a market.': 'Выберите рынок.', 'Give the project a name.': 'Назовите проект.',
    'No such job.': 'Задание не найдено.', 'No such project.': 'Проект не найден.', 'Not found': 'Не найдено',
    'The job is still building. Edit it when it is done.': 'Задание ещё собирается. Измените его после завершения.',
    'Phone check pending': 'Проверка на телефоне ожидается', 'Scrolls sideways on phones': 'На телефоне есть горизонтальная прокрутка', 'Fits phones': 'Помещается на телефоне',
    'CTA below the fold ({0} px)': 'Кнопка ниже первого экрана ({0} px)', 'CTA on the first screen': 'Кнопка на первом экране',
    'Failed: {0}': 'Ошибка: {0}', 'picked by the generator': 'выбрано генератором', 'from the job form': 'из формы задания', 'style: {0}': 'стиль: {0}', '{0} pages': 'страниц: {0}',
    'Market rule overridden: {0}': 'Правило рынка обойдено: {0}', '{0} build notes': 'Заметок сборки: {0}', '1 build note': '1 заметка сборки',
    'Open site': 'Открыть сайт', 'AI brief': 'ИИ-бриф',
    'Screenshots are compared once every site of the job is built': 'Скриншоты сравниваются, когда собраны все сайты задания', '{0}: checked after the build': '{0}: проверка после сборки',
    'No other site of this type to compare with': 'Нет других сайтов этого типа для сравнения', '{0}: nothing to compare': '{0}: не с чем сравнить',
    'Most similar: {0} · threshold {1}%': 'Самый похожий: {0} · порог {1}%', ' · under {0}%': ' · ниже {0}%', 'Code': 'Код', 'Visual': 'Визуально',
    'Re-planned and rebuilt to meet the thresholds': 'Перепланирован и пересобран, чтобы достичь порогов', 'rebuilt {0}×': 'пересобран {0}×',
    'How different are the sites?': 'Насколько различаются сайты?', 'Working out the differences… On a big network this takes up to a minute.': 'Считаем различия… В большой сети это занимает до минуты.', 'Shown when the sites are built.': 'Появится, когда сайты будут собраны.',
    'Retry failed ({0})': 'Повторить неудачные ({0})', 'Continue the build': 'Продолжить сборку',
    'Waiting for another job': 'Ожидает другое задание', 'Job "{0}" is building now. This job starts automatically when it is done; one job builds at a time, so the generator stays responsive.': 'Сейчас собирается задание «{0}». Это задание запустится автоматически, когда оно завершится: одновременно собирается одно задание, чтобы генератор не тормозил.',
    'The build was stopped before it finished ({0} of {1} sites built). Continue the build to finish the rest.': 'Сборка была остановлена до завершения (собрано {0} из {1}). Продолжите сборку, чтобы достроить остальное.', 'New looks': 'Новый облик', 'Rebuild all': 'Пересобрать всё', 'Delete job': 'Удалить задание',
    'New seed, style, palette and hero layout for every site; the variations stay': 'Новые seed, стиль, палитра и макет первого экрана для каждого сайта; вариации остаются',
    'Same plans (seeds, styles, variations), current engine; sites under the difference thresholds are re-planned': 'Те же планы (seed, стили, вариации), текущий движок; сайты ниже порогов перепланируются',
    'Count this job in comparisons': 'Учитывать задание в сравнениях',
    'Untick for a draft or test job: other jobs are not compared with its sites or rebuilt to differ from them. Its own sites are still checked.': 'Снимите отметку для черновика или тестового задания: другие задания не сравниваются с его сайтами и не пересобираются, чтобы от них отличаться. Его собственные сайты по-прежнему проверяются.',
    '{0} of {1} sites built': 'Собрано сайтов: {0} из {1}', ', {0} failed': ', с ошибкой: {0}',
    'Open a site to see every page, the phone view, the developer panel and the AI brief.': 'Откройте сайт, чтобы увидеть все страницы, вид на телефоне, панель разработчика и ИИ-бриф.',
    'Checking the differences': 'Проверяем различия', 'Starting the build': 'Запускаем сборку', 'Rebuilding: {0} of {1} sites done': 'Пересборка: готово {0} из {1}',
    'Screenshots are compared site by site; a site that looks too alike is rebuilt, so its card can still change.': 'Скриншоты сравниваются по очереди; слишком похожий сайт пересобирается, поэтому его карточка ещё может измениться.',
    'Sites are built one by one; each card comes back when its site is ready.': 'Сайты собираются по одному; карточка появляется, когда сайт готов.',
    'The comparison table updates when the job is done. You can leave this page; the build carries on.': 'Таблица сравнения обновится, когда задание завершится. Страницу можно закрыть, сборка продолжится.',
    'These numbers are from the previous build. They update when the rebuild is done.': 'Это цифры предыдущей сборки. Они обновятся после пересборки.',
    'The comparison appears when the build is done.': 'Сравнение появится после сборки.',
    'Delete this job and its sites?': 'Удалить задание и его сайты?', 'Starting…': 'Запуск…',
    'Give every site of this job a new seed, style, palette and hero layout? The variations stay the same.': 'Дать каждому сайту задания новые seed, стиль, палитру и макет первого экрана? Вариации останутся прежними.',
    'Very different': 'Очень разные', 'Different': 'Разные', 'Somewhat alike': 'Довольно похожи', 'Too alike': 'Слишком похожи',
    'job {0} · ': 'задание {0} · ', 'code': 'код', 'visual': 'визуально', 'Under {0}%': 'Ниже {0}%', 'Meets {0}%': 'Не ниже {0}%',
    'vs {0} · structure {1}% different': 'с {0} · структура отличается на {1}%', 'vs {0}': 'с {0}', 'No screenshots yet': 'Скриншотов пока нет',
    'Site by site: {0} types, {1} sites': 'По сайтам: типов {0}, сайтов {1}',
    '{0}–{1} of {2}': '{0}–{1} из {2}',
    'Edit mode': 'Режим правки', 'Done editing': 'Готово', 'Shows the links to edit a whole type or a single site': 'Показывает ссылки для правки целого типа или одного сайта',
    'Edit mode: change a whole type with “Edit all” next to its name, or one site with “Edit” on its card. Your settings stay when the job is rebuilt.': 'Режим правки: меняйте целый тип кнопкой «Изменить все» рядом с его названием или один сайт кнопкой «Изменить» на карточке. Ваши настройки сохраняются при пересборке задания.',
    'AI briefs': 'ИИ-брифы', 'Download AI briefs': 'Скачать ИИ-брифы', 'All': 'Все', 'None': 'Ни одного', 'Download .zip': 'Скачать .zip',
    'Download the AI brief for the whole site (.txt)': 'Скачать ИИ-бриф всего сайта (.txt)', '{0} sites in {1} folders': 'Сайтов: {0}, папок: {1}', '{0} sites': 'Сайтов: {0}',
    'Choose at least one site type.': 'Выберите хотя бы один тип сайтов.',
    'One .txt file per built site: the system prompt and the rebuild brief for all its pages. With several site types, each type gets its own folder.':
      'По одному .txt на каждый собранный сайт: системный промпт и бриф на все его страницы. Если выбрано несколько типов, у каждого типа своя папка.',
    'Edit all': 'Изменить все', 'Edit': 'Изменить', 'edited': 'изменён', 'This site has its own settings': 'У сайта свои настройки',
    'Edit: {0}': 'Изменить: {0}', 'Edit site {0}': 'Изменить сайт {0}',
    'Applies to all {0} sites of this type in the job. What stays on Automatic is left to the generator, which keeps the sites different. Saving rebuilds these sites and checks their differences again.': 'Применяется ко всем сайтам этого типа в задании ({0}). То, что оставлено на «Автоматически», выбирает генератор, чтобы сайты различались. Сохранение пересобирает эти сайты и заново проверяет различия.',
    'Settings for this site only; they go on top of its type\u2019s settings. What stays on Automatic follows the type and the generator.': 'Настройки только для этого сайта, поверх настроек его типа. Оставленное на «Автоматически» следует типу и генератору.',
    'The type has its own settings: <a href="{0}">edit the type</a>.': 'У типа есть свои настройки: <a href="{0}">изменить тип</a>.',
    'Fixed choices make sites more alike: change only what you need.': 'Зафиксированные настройки делают сайты похожее: меняйте только нужное.',
    'Look': 'Внешний вид', 'Style': 'Стиль', 'Light / dark': 'Светлая / тёмная', 'As the style': 'Как в стиле', 'Swapped': 'Наоборот',
    'Navigation': 'Навигация', 'On desktop': 'На компьютере', 'Top menu': 'Меню сверху', 'Sidebar on the left': 'Боковое меню слева', 'Sidebar on the right': 'Боковое меню справа',
    'Hub (the home page links to every page)': 'Хаб (главная ведёт на все страницы)', 'Sidebar look': 'Вид бокового меню', 'Panel': 'Панель', 'Rail': 'Полоса', 'Cards': 'Карточки',
    'Phone menu side': 'Сторона меню на телефоне', 'Left': 'Слева', 'Right': 'Справа', 'Phone menu opens as': 'Меню на телефоне открывается', 'Dropdown': 'Выпадающим списком',
    'Side drawer': 'Шторкой сбоку', 'Under the header': 'Панелью под шапкой', 'Full screen': 'На весь экран', 'Phone menu': 'Меню на телефоне', 'Show': 'Показывать',
    'None (the sidebar shows under the content)': 'Нет (боковое меню под содержимым)', 'Sections': 'Секции',
    'One page per item (brand or article): the settings apply to each of them.': 'По странице на каждый элемент (бренд или статью): настройки действуют на каждую.',
    'Set the order of the middle sections': 'Задать порядок средних секций', 'Order rules: {0}': 'Правила порядка: {0}', '“{0}” before “{1}”': '«{0}» перед «{1}»', '“{0}” not first': '«{0}» не первой',
    'Order': 'Порядок', 'Section': 'Секция', 'Where': 'Где', 'Shown': 'Показ', 'Layout': 'Макет', 'Move up': 'Выше', 'Move down': 'Ниже',
    'no demo copy: the block shows its defaults': 'нет демо-текста: блок покажет значения по умолчанию', 'On': 'Вкл', 'Off': 'Выкл', 'Always': 'Всегда', 'With an APK download': 'Если есть APK',
    'Rows of cards (bonuses, games, screenshots, reviews)': 'Ряды карточек (бонусы, игры, скриншоты, отзывы)', 'Rows of cards': 'Ряды карточек',
    'Both: swipe on some sites, stacked on others': 'Оба: на одних сайтах листаются, на других сеткой', 'Swipe: one row you scroll sideways': 'Листаются: один ряд с прокруткой вбок',
    'Stacked: cards wrap into a grid': 'Сеткой: карточки переносятся в сетку', 'Wide tables keep their sideways scroll either way.': 'Широкие таблицы в любом случае прокручиваются вбок.',
    'Swipe': 'Листаются', 'Stacked': 'Сеткой',
    'Affiliate disclosure (casino reviews, brand reviews, news)': 'Раскрытие партнёрских ссылок (обзоры казино, отзывы, новости)', 'A strip at the top of every page': 'Полоса вверху каждой страницы',
    'In the footer': 'В подвале', 'Affiliate disclosure': 'Раскрытие партнёрских ссылок', 'Strip at the top': 'Полоса вверху',
    'The line saying the site earns a commission through its links. Most gambling advertising rules require it: with Off, checking the market’s rules is up to you, and each site carries a build note.': 'Строка о том, что сайт получает комиссию по своим ссылкам. Её требуют большинство правил рекламы азартных игр: при «Выкл» проверка правил рынка — на вас, а у каждого сайта будет заметка сборки.',
    'Off (no disclosure)': 'Выкл (без раскрытия)',
    'Close': 'Закрыть',
    'Sample brands in casino rankings': 'Примерные бренды в рейтингах казино',
    'Made-up operators listed next to your brands on casino review sites, so a ranking looks like a real one. Their data is placeholder; the featured (paid) slot is always one of your brands. 0 = your brands only.': 'Вымышленные операторы рядом с вашими брендами на сайтах-обзорах казино, чтобы рейтинг выглядел как настоящий. Их данные — заглушки; платное место «Партнёр» всегда у вашего бренда. 0 — только ваши бренды.',
    'Pages': 'Страницы', 'Page': 'Страница', 'Keep': 'Оставить', 'Remove': 'Удалить', 'Add': 'Добавить', 'Keep (over the type)': 'Оставить (вопреки типу)',
    'On this site': 'Сейчас на сайте', 'Yes': 'Есть', 'No': 'Нет', 'Removed': 'Удалена',
    'Always: first screen': 'Всегда: первый экран', 'Always: compliance': 'Всегда: комплаенс', 'Always: home page': 'Всегда: главная', 'Always: one page per item': 'Всегда: страница на каждый элемент',
    'Always: policy': 'Всегда: правила', 'Required in {0}': 'Обязательна в {0}',
    'This page is removed: its sections are not built.': 'Эта страница удалена: её секции не собираются.',
    '“Add” turns on a section the generator left off. Sections outside this type’s design cannot be added: the generator has no copy for them.': '«Добавить» включает секцию, которую генератор не поставил. Секции вне макета этого типа добавить нельзя: для них у генератора нет текста.',
    'Removing pages and sections makes sites shorter and more alike: remove only what you need.': 'Удаление страниц и секций делает сайты короче и похожее друг на друга: удаляйте только нужное.',
    'middle': 'середина', 'bottom': 'низ', 'Save and rebuild {0} sites': 'Сохранить и пересобрать сайты ({0})', 'Save and rebuild this site': 'Сохранить и пересобрать сайт',
    'Reset everything to Automatic': 'Сбросить всё на «Автоматически»', 'Cancel': 'Отмена', 'Reset every setting here to Automatic and rebuild?': 'Сбросить все настройки здесь на «Автоматически» и пересобрать?',
    'This job has no sites of that type.': 'В задании нет сайтов этого типа.', 'No such site.': 'Сайт не найден.',
    'Home': 'Главная', 'Sign-up': 'Регистрация', 'Betting': 'Ставки', 'Bonus': 'Бонус', 'Responsible gambling': 'Ответственная игра', 'Review (per brand)': 'Обзор (на бренд)',
    'Comparison': 'Сравнение', 'Fast payouts': 'Быстрый вывод', 'Live casino': 'Живое казино', 'How we rate': 'Как мы оцениваем', 'iPhone': 'iPhone', 'Features': 'Возможности',
    'What\u2019s new': 'Что нового', 'Help': 'Помощь', 'Guides': 'Гайды', 'Partner content': 'Партнёрские материалы', 'About': 'О нас', 'Article (per article)': 'Статья (на статью)',
    'Reviews': 'Отзывы', 'Payout reviews': 'Отзывы о выплатах', 'Support reviews': 'Отзывы о поддержке', 'Write a review': 'Написать отзыв', 'How we moderate': 'Как мы модерируем',
    'Affiliate Disclosure': 'Раскрытие партнёрства', 'App Hero': 'Первый экран приложения', 'App Information': 'О приложении', 'App Privacy': 'Конфиденциальность приложения',
    'App Screenshots': 'Скриншоты приложения', 'Article List': 'Список статей', 'Bonus / Feature Card': 'Карточка бонуса', 'Brand Facts': 'Факты о бренде', 'Brand Ranking': 'Рейтинг брендов',
    'Brand Review Header': 'Шапка обзора бренда', 'Comparison Table': 'Таблица сравнения', 'Download Panel': 'Панель загрузки', 'Editor Box': 'Блок редакции', 'FAQ': 'Вопросы и ответы',
    'Final CTA': 'Финальный призыв', 'Game Grid': 'Сетка игр', 'Hero': 'Первый экран', 'How To Start': 'Как начать', 'Install Guide': 'Как установить', 'Lead Stories': 'Главные новости',
    'More Promotions': 'Другие акции', 'More Providers': 'Другие провайдеры', 'Offer Hero': 'Первый экран акции', 'Offer Terms': 'Условия акции', 'Page Hero (inner pages)': 'Заголовок страницы',
    'Page Links': 'Ссылки на страницы', 'Product Showcase': 'Витрина продукта', 'Pros and Cons': 'Плюсы и минусы', 'Prose (page text)': 'Текст страницы', 'RTP & Volatility Explainer': 'RTP и волатильность',
    'Rating Summary': 'Сводка рейтинга', 'Review Form': 'Форма отзыва', 'Review List': 'Список отзывов', 'Score Breakdown': 'Разбор оценки', 'Scoring Criteria': 'Критерии оценки',
    'Store About': 'Об игре в магазине', 'Store Header': 'Шапка магазина', 'Store Reviews': 'Отзывы в магазине', 'Topic Scores': 'Оценки по темам', 'Trust Bar': 'Полоса доверия', 'Vendor Hero': 'Первый экран провайдера',
    "What's New": 'Что нового', 'Article': 'Статья',
    'Overview': 'Сводка', 'Per type, against the most similar site in this job or any other job.': 'По типам, относительно самого похожего сайта в этом или любом другом задании.',
    '{0} under a threshold': 'Ниже порога: {0}', 'Nothing under a threshold, ': 'Ниже порогов ничего нет, ',
    '{0} somewhat alike (code {1}–74%), out of {2} sites. Click a type for its sites.': 'довольно похожих (код {1}–74%): {0}, всего сайтов: {2}. Нажмите на тип, чтобы увидеть его сайты.',
    'Under code {0}%': 'Код ниже {0}%', 'Under visual {0}%': 'Визуально ниже {0}%', 'Lowest code': 'Мин. код', 'Lowest visual': 'Мин. визуально',
    'Each site compared with the <b>most similar</b> site of the same type, in this job, in the project’s other jobs and in every other job. <b>Code</b> = how much of the CSS and HTML differs (renamed classes, whitespace and order do not count). <b>Visual</b> = how much of the first screen looks different, desktop and phone. Higher is better; 100% = nothing in common.':
      'Каждый сайт сравнивается с <b>самым похожим</b> сайтом того же типа: в этом задании, в других заданиях проекта и во всех остальных заданиях. <b>Код</b> — насколько отличаются CSS и HTML (переименованные классы, пробелы и порядок не учитываются). <b>Визуально</b> — насколько отличается первый экран на компьютере и телефоне. Чем больше, тем лучше; 100% — ничего общего.',
    'Thresholds: code {0}%': 'Пороги: код {0}%', ', visual {0}%': ', визуально {0}%', ' (visual check off)': ' (визуальная проверка выключена)',
    'compared with {0}': 'сравнение с: {0}', 'every job': 'все задания', 'the project': 'проект', 'this job': 'это задание', 'change': 'изменить',
    'code 90%+ very different': 'код 90%+ очень разные', '75–89% different': '75–89% разные', '50–74% somewhat alike': '50–74% довольно похожи', 'under the threshold': 'ниже порога',
    'Site': 'Сайт', 'Closest in this job': 'Ближайший в этом задании', 'Closest in other jobs of the project': 'Ближайший в других заданиях проекта', 'Closest in all other jobs': 'Ближайший во всех остальных заданиях',
    'only site of this type': 'единственный сайт этого типа', 'no other jobs in this project': 'других заданий в проекте нет', 'no other jobs': 'других заданий нет',
    'Branded landing': 'Брендовый лендинг', 'App page (landing)': 'Страница приложения (лендинг)', 'App site (multi-page)': 'Сайт приложения (многостраничный)',
    'Bonuses and promo codes': 'Бонусы и промокоды', 'Game provider overview': 'Обзор провайдера игр', 'Multi-page satellite': 'Многостраничный сателлит',
    'Casino reviews (several brands)': 'Обзоры казино (несколько брендов)', 'Casino reviews, one page (several brands)': 'Обзоры нескольких казино, одна страница', 'Gambling news': 'Новости гемблинга', 'Brand reviews (user reviews)': 'Отзывы о бренде (пользовательские)',
    'Product': 'Продукт', 'Platform': 'Платформа', 'Design': 'Дизайн', 'Offers': 'Предложения', 'Providers': 'Провайдеры', 'News': 'Новости', 'Paid articles': 'Платные статьи',
    'Casino + sport': 'Казино + спорт', 'Casino': 'Казино', 'Sport': 'Спорт', 'Live betting': 'Лайв-ставки', 'iOS + Android': 'iOS + Android',
    'Site style': 'Стиль сайта', 'Random style': 'Случайный стиль', 'Neutral': 'Нейтральный', 'Brand colours': 'Цвета бренда', 'Google Play look': 'Как Google Play', 'App Store look': 'Как App Store', 'Provider colours': 'Цвета провайдера',
    'Banners + promo codes': 'Баннеры + промокоды', 'Bonus banners': 'Бонусные баннеры', 'Promo codes': 'Промокоды', 'One provider': 'Один провайдер', 'Several providers': 'Несколько провайдеров',
    'General iGaming news': 'Общие новости iGaming', 'Brand news': 'Новости бренда', 'As a separate block': 'Отдельным блоком', 'In the news feed': 'В ленте новостей',
    'Australia': 'Австралия', 'Brazil': 'Бразилия', 'Kazakhstan': 'Казахстан', 'Demoland (demo only)': 'Демоленд (только демо)',
    'planned': 'запускается', 'queued': 'в очереди', 'stopped': 'остановлено', 'running': 'собирается', 'checking': 'проверка', 'done': 'готово', 'done-with-errors': 'готово с ошибками', 'building': 'собирается', 'built': 'собран', 'failed': 'ошибка',
    'length': 'длина', 'nav': 'навигация', 'rank': 'рейтинг', 'images': 'картинки', 'size': 'размер', 'full': 'полная', 'compact': 'компактная', 'top': 'сверху', 'sidebar': 'боковая', 'hub': 'хаб',
    'on': 'вкл', 'off': 'выкл', 'lite': 'лёгкий', 'standard': 'стандарт', 'live': 'лайв', 'overall': 'общий'
  };
  var LANG = 'en';
  try { LANG = localStorage.getItem('gen-lang') === 'ru' ? 'ru' : 'en'; } catch (e) {}
  function L(str) {
    var out = LANG === 'ru' && RU[str] !== undefined ? RU[str] : str, args = arguments;
    return out.replace(/\{(\d)\}/g, function (m, i) { return args[+i + 1]; });
  }
  function optLabel(lbl) {
    var m = /^([\w-]+): (.+)$/.exec(lbl);
    return m ? L(m[1]) + ': ' + L(m[2]) : L(lbl);
  }
  function chrome() {
    document.documentElement.lang = LANG;
    document.title = 'WebNets';
    var h = location.hash.replace(/^#\/?/, '').split('/')[0];
    document.getElementById('topnav').innerHTML = '<a href="#/"' + (['settings', 'markets', 'types'].indexOf(h) < 0 ? ' aria-current="page"' : '') + '>' + L('Projects') + '</a><a href="#/types"' + (h === 'types' ? ' aria-current="page"' : '') + '>' + L('Site types') + '</a><a href="#/markets"' + (h === 'markets' ? ' aria-current="page"' : '') + '>' + L('Markets') + '</a><a href="#/settings"' + (h === 'settings' ? ' aria-current="page"' : '') + '>' + L('Thresholds') + '</a><a href="/">' + L('Gallery') + '</a><a href="/settings/">' + L('AI brief settings') + '</a>';
    var lang = document.getElementById('ui-lang'), th = document.getElementById('ui-theme'), cur = document.documentElement.dataset.theme || 'auto';
    lang.value = LANG;
    lang.setAttribute('aria-label', L('Language'));
    th.setAttribute('aria-label', L('Theme'));
    th.innerHTML = [['auto', 'Auto'], ['light', 'Light'], ['dark', 'Dark']].map(function (o) { return '<option value="' + o[0] + '"' + (o[0] === cur ? ' selected' : '') + '>' + L(o[1]) + '</option>'; }).join('');
  }
  document.getElementById('ui-lang').onchange = function () {
    LANG = this.value;
    try { localStorage.setItem('gen-lang', LANG); } catch (e) {}
    chrome();
    main.innerHTML = '';
    route();
  };
  document.getElementById('ui-theme').onchange = function () {
    var v = this.value;
    if (v === 'auto') delete document.documentElement.dataset.theme; else document.documentElement.dataset.theme = v;
    try { localStorage.setItem('gen-theme', v); } catch (e) {}
  };
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function api(path, body, method) {
    return fetch('/generator/api/' + path, body || method ? { method: method || 'POST', headers: { 'Content-Type': 'application/json' }, body: body ? JSON.stringify(body) : undefined } : {})
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.error || r.statusText); return j; }); });
  }
  var AVAIL = {};
  function catalog() { return CAT ? Promise.resolve(CAT) : api('catalog').then(function (c) { CAT = c; return c; }); }
  function fail(e) { main.innerHTML = '<p class="err">' + esc(L(e.message || String(e))) + '</p>'; }
  function when(s) { return esc(String(s).replace('T', ' ').slice(0, 16)); }

  /* ---------- thresholds */
  function settings() {
    api('settings').then(function (d) {
      var st = d.settings, df = d.defaults;
      main.innerHTML = '<h2>' + L('Difference thresholds') + '</h2><p class="muted">' + L('How different every generated site must be from the sites it is compared with (same type). The generator checks each site after it is built; a site under a threshold gets a new plan (new seed, another style, the next hero layout, then a free section order and free layouts) and is rebuilt, up to the rebuilds limit. It keeps the best version; a site that still misses a threshold is flagged on the job page.') + '</p>'
        + '<form class="card" id="sf" style="display:grid;gap:14px;max-width:640px">'
        + '<div class="field"><label class="lbl" for="scode">' + L('Minimum code difference, %') + '</label><input type="number" id="scode" min="0" max="100" value="' + st.code_min + '" style="width:110px"><span class="muted" style="font-size:13px">' + L('Share of CSS rules and HTML that differ (renamed classes, whitespace and order do not count). Checked in milliseconds after each build. Default {0}.', df.code_min) + '</span></div>'
        + '<div class="field"><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" id="svis"' + (st.visual ? ' checked' : '') + '> <span class="lbl" style="margin:0">' + L('Check visual difference') + '</span></label>'
        + '<label class="lbl" for="svmin">' + L('Minimum visual difference, %') + '</label><input type="number" id="svmin" min="0" max="100" value="' + st.visual_min + '" style="width:110px"><span class="muted" style="font-size:13px">' + L('Share of the first screen that visibly changes, desktop (1280×800) and phone (375×667) averaged. Measured from screenshots once the job is built, which adds about 2 seconds per site. Default {0}.', df.visual_min) + '</span></div>'
        + '<div class="field"><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" id="svar"' + (st.variants ? ' checked' : '') + '> <span class="lbl" style="margin:0">' + L('Style variants') + '</span></label><span class="muted" style="font-size:13px">' + L('Each preset also in its light or dark version and in 6 colour rotations (120 styles instead of 10); a site that only looks too alike swaps light and dark first. Off: the 10 presets as they are.') + '</span></div>'
        + '<div class="field"><label class="lbl" for="sscope">' + L('Compare with') + '</label><select id="sscope"><option value="all">' + L('Sites of every job') + '</option><option value="project">' + L('Sites of the same project') + '</option><option value="job">' + L('Sites of the same job only') + '</option></select></div>'
        + '<div class="field"><label class="lbl" for="satt">' + L('Rebuilds per check') + '</label><input type="number" id="satt" min="0" max="5" value="' + st.rebuilds + '" style="width:110px"><span class="muted" style="font-size:13px">' + L('How many times a site under a threshold is re-planned and rebuilt, for the code check and again for the visual check. 0 = measure only, never rebuild. Default {0}.', df.rebuilds) + '</span></div>'
        + '<div class="row"><button class="primary">' + L('Save') + '</button><span class="muted" id="smsg"></span></div></form>'
        + '<p class="muted">' + L('New thresholds apply to the next build. Use Rebuild all on a job to check its sites again.') + '</p>';
      document.getElementById('sscope').value = st.scope;
      document.getElementById('sf').onsubmit = function (e) {
        e.preventDefault();
        api('settings', { code_min: +document.getElementById('scode').value, visual_min: +document.getElementById('svmin').value, visual: document.getElementById('svis').checked, variants: document.getElementById('svar').checked,
          scope: document.getElementById('sscope').value, rebuilds: +document.getElementById('satt').value }, 'PUT')
          .then(function () { document.getElementById('smsg').textContent = L('Saved.'); }).catch(function (x) { document.getElementById('smsg').textContent = L(x.message); });
      };
    }).catch(fail);
  }

  /* ---------- projects */
  function projects() {
    api('projects').then(function (list) {
      main.innerHTML = '<div class="row"><div><h2>' + L('Projects') + '</h2><p class="muted">' + L('A project holds the jobs that generate its sites. Sites are compared inside the project and against every other job.') + '</p></div></div>'
        + '<form class="card row" id="np"><input type="text" id="pname" placeholder="' + L('New project name') + '" aria-label="' + L('New project name') + '" required style="flex:1;min-width:220px"><button class="primary">' + L('Create project') + '</button></form>'
        + '<div class="list">' + (list.length ? list.map(function (p) {
          return '<a class="item" href="#/p/' + p.id + '"><strong>' + esc(p.name) + '</strong><span class="sp"></span><span class="chip">' + L('{0} jobs', p.jobs) + '</span><span class="chip">' + L('{0} sites', p.sites) + '</span><span class="muted">' + when(p.created) + '</span></a>';
        }).join('') : '<p class="muted">' + L('No projects yet.') + '</p>') + '</div>';
      document.getElementById('np').onsubmit = function (e) {
        e.preventDefault();
        api('projects', { name: document.getElementById('pname').value }).then(function (r) { location.hash = '#/p/' + r.id; }).catch(fail);
      };
    }).catch(fail);
  }
  function project(id) {
    api('projects/' + id).then(function (d) {
      main.innerHTML = '<p><a href="#/">← ' + L('Projects') + '</a></p><div class="row"><h2>' + esc(d.project.name) + '</h2><span class="sp"></span><a class="btn primary" href="#/p/' + id + '/new">' + L('New job') + '</a><button class="danger" id="delp">' + L('Delete project') + '</button></div>'
        + '<div class="list">' + (d.jobs.length ? d.jobs.map(function (j) {
          return '<a class="item" href="#/j/' + j.id + '"><strong>' + esc(j.name) + '</strong><span class="chip">' + esc(j.market.toUpperCase()) + '</span>' + (+j.compare ? '' : '<span class="chip auto" title="' + L('Other jobs are not compared with this one') + '">' + L('left out of comparisons') + '</span>') + '<span class="sp"></span>'
            + '<span class="chip ' + (j.status === 'done' ? 'ok' : j.status === 'done-with-errors' || j.status === 'stopped' ? 'bad' : 'warn') + '">' + esc(L(j.status)) + '</span><span class="chip">' + L('{0} / {1} sites', j.built, j.sites) + '</span><span class="muted">' + when(j.created) + '</span></a>';
        }).join('') : '<p class="muted">' + L('No jobs yet. Create one to generate sites.') + '</p>') + '</div>';
      document.getElementById('delp').onclick = function () {
        if (confirm(L('Delete this project, its jobs and every generated site?'))) api('projects/' + id, null, 'DELETE').then(function () { location.hash = '#/'; }).catch(fail);
      };
    }).catch(fail);
  }

  /* ---------- job form */
  function jobForm(pid, editId) {
    Promise.all([catalog(), editId ? api('jobs/' + editId) : null]).then(function (r) {
      var cat = r[0], ed = r[1] && r[1].job, prefilled = false;
      if (ed) pid = ed.project_id;
      main.innerHTML = '<p><a href="' + (ed ? '#/j/' + ed.id : '#/p/' + pid) + '">← ' + (ed ? esc(ed.name) : L('Project')) + '</a></p><h2>' + (ed ? L('Edit job') : L('New job')) + '</h2>'
        + (ed ? '<p class="note">' + L('Saving rebuilds the job. Sites that stay (same type and number) keep their seed and style; extra sites are added, sites over the new count are deleted.') + '</p>' : '')
        + '<p class="muted">' + L('Each site gets its own seed, style, layouts, code style and implementation; the generator spreads them so sites differ.') + '</p>'
        + '<form id="jf" class="card"><div class="field"><label class="lbl" for="jname">' + L('Job name') + '</label><input type="text" id="jname" required maxlength="120" placeholder="' + L('e.g. Brazil casino network, wave 1') + '"></div>'
        + '<div class="field"><label class="lbl" for="jmarket">' + L('Market') + '</label><select id="jmarket">' + cat.markets.map(function (m) { return '<option value="' + m.id + '"' + (m.id === 'xx' ? ' selected' : '') + '>' + esc(L(m.name)) + ' (' + m.id.toUpperCase() + ')</option>'; }).join('') + '</select></div>'
        + '<div class="field"><span class="lbl">' + L('Brands') + '</span><div id="brows" class="brows"></div><div class="row"><button type="button" id="addb">' + L('+ Add brand') + '</button></div>'
        + '<span class="muted" style="font-size:13px">' + esc(L('Your own brands. Single-brand sites are spread across them; casino reviews and news sites list all of them. Domain is optional (links use it); colour is used by the "brand colours" design. Logos are generated from the initials, other brand data (bonuses, reviews, ratings, games) is placeholder.')) + '</span></div>'
        + '<div class="field"><span class="lbl">' + L('Brand colours') + '</span><div class="colors">'
        + [['primary', 'Primary', 'buttons, links, highlights', '#4F46E5'], ['secondary', 'Secondary', 'second highlight colour', '#14B8A6'], ['accent', 'Accent', 'hero background', '#111827']].map(function (c) {
          return '<div class="c"><label class="lbl" for="c-' + c[0] + '">' + L(c[1]) + '</label><span class="muted" style="font-size:12px">' + L(c[2]) + '</span><div class="row"><input type="color" id="c-' + c[0] + '" value="' + c[3] + '" disabled><label><input type="checkbox" class="cauto" data-c="' + c[0] + '" checked> ' + L('Automatic') + '</label></div></div>';
        }).join('') + '</div><p class="note" id="cnote" hidden>' + L('Fixed colours are shared by every site in the job, so the sites look more alike. Leave them automatic for the most different sites.') + '</p></div>'
        + '<div class="field"><span class="lbl">' + L('Site types, how many and which variations') + '</span><div id="types" class="muted">' + L('Checking what this market allows…') + '</div></div>'
        + '<div class="field"><label class="lbl" for="jcopy">' + L('Copy') + '</label><select id="jcopy"><option value="demo">' + L('Demo copy, with your brand names') + '</option><option value="placeholder">' + L('Placeholder text') + '</option></select>'
        + '<span class="muted" style="font-size:13px">' + L('The demo copy is in Russian: sites in Russian (demo market, Kazakhstan) get it; sites in other languages (Australia, Brazil) get placeholder text.') + '</span></div>'
        + '<div class="field"><label class="lbl" for="jrows">' + L('Rows of cards (bonuses, games, screenshots, reviews)') + '</label><select id="jrows"><option value="both">' + L('Both: swipe on some sites, stacked on others') + '</option><option value="swipe">' + L('Swipe: one row you scroll sideways') + '</option><option value="stack">' + L('Stacked: cards wrap into a grid') + '</option></select>'
        + '<span class="muted" style="font-size:13px">' + L('Wide tables keep their sideways scroll either way.') + '</span></div>'
        + '<div class="field"><label class="lbl" for="jdisc">' + L('Affiliate disclosure (casino reviews, brand reviews, news)') + '</label><select id="jdisc"><option value="off" selected>' + L('Off (no disclosure)') + '</option><option value="top">' + L('A strip at the top of every page') + '</option><option value="footer">' + L('In the footer') + '</option></select>'
        + '<span class="muted" style="font-size:13px">' + L('The line saying the site earns a commission through its links. Most gambling advertising rules require it: with Off, checking the market’s rules is up to you, and each site carries a build note.') + '</span></div>'
        + '<div class="field"><label class="lbl" for="jsample">' + L('Sample brands in casino rankings') + '</label><input type="number" id="jsample" min="0" max="16" value="7" style="max-width:120px">'
        + '<span class="muted" style="font-size:13px">' + L('Made-up operators listed next to your brands on casino review sites, so a ranking looks like a real one. Their data is placeholder; the featured (paid) slot is always one of your brands. 0 = your brands only.') + '</span></div>'
        + '<div class="row"><span class="muted" id="total"></span><span class="sp"></span><button class="primary" id="go">' + L('Generate sites') + '</button></div><p class="err" id="ferr" hidden></p></form>';
      var form = document.getElementById('jf'), mk = document.getElementById('jmarket'), avail = {};
      [].forEach.call(document.querySelectorAll('.cauto'), function (cb) {
        cb.onchange = function () {
          document.getElementById('c-' + cb.dataset.c).disabled = cb.checked;
          document.getElementById('cnote').hidden = ![].some.call(document.querySelectorAll('.cauto'), function (x) { return !x.checked; });
        };
      });
      function totals() {
        var n = 0;
        [].forEach.call(document.querySelectorAll('.tcount'), function (i) { if (i.closest('tr').querySelector('.ton').checked) n += +i.value || 0; });
        document.getElementById('total').textContent = L('{0} sites will be generated', n);
        return n;
      }
      var bkey = 'gen-brands-' + pid;
      function brandRow(b) {
        b = b || {};
        var r = document.createElement('div');
        r.className = 'brow';
        r.innerHTML = '<input type="text" class="bname" maxlength="40" placeholder="' + L('Brand name') + '" aria-label="' + L('Brand name') + '">'
          + '<input type="text" class="bdom" maxlength="80" placeholder="' + L('Domain (optional), e.g. mybrand.com') + '" aria-label="' + L('Brand domain') + '">'
          + '<span class="cl"><input type="color" class="bcol" aria-label="' + L('Brand colour') + '"><label><input type="checkbox" class="bauto" checked> ' + L('Auto') + '</label></span>'
          + '<select class="bprod" aria-label="' + L('Products') + '"><option value="both">' + L('Casino + sport') + '</option><option value="casino">' + L('Casino') + '</option><option value="sports">' + L('Sport') + '</option></select>'
          + '<button type="button" class="bdel" aria-label="' + L('Remove brand') + '">✕</button>';
        r.querySelector('.bname').value = b.name || '';
        r.querySelector('.bdom').value = b.domain || '';
        r.querySelector('.bcol').value = b.color || '#4F46E5';
        r.querySelector('.bauto').checked = !b.color;
        r.querySelector('.bcol').disabled = !b.color;
        r.querySelector('.bprod').value = b.products || 'both';
        r.querySelector('.bauto').onchange = function () { r.querySelector('.bcol').disabled = this.checked; };
        r.querySelector('.bdel').onclick = function () { r.remove(); if (!document.querySelector('.brow')) document.getElementById('brows').appendChild(brandRow()); };
        return r;
      }
      function brandsValue() {
        return [].map.call(document.querySelectorAll('.brow'), function (r) {
          return { name: r.querySelector('.bname').value.trim(), domain: r.querySelector('.bdom').value.trim(), products: r.querySelector('.bprod').value,
            color: r.querySelector('.bauto').checked ? '' : r.querySelector('.bcol').value };
        }).filter(function (b) { return b.name; });
      }
      var saved = [];
      try { saved = JSON.parse(localStorage.getItem(bkey) || '[]'); } catch (e) { saved = []; }
      (saved.length ? saved : [{}]).forEach(function (b) { document.getElementById('brows').appendChild(brandRow(b)); });
      document.getElementById('addb').onclick = function () { var r = brandRow(); document.getElementById('brows').appendChild(r); r.querySelector('.bname').focus(); };
      function readTypes() {
        var sp = { types: {} }, any = false;
        [].forEach.call(document.querySelectorAll('.ton'), function (cb) {
          any = true;
          var row = cb.closest('tr'), v = {};
          [].forEach.call(row.querySelectorAll('.opt:checked'), function (o) { (v[o.dataset.a] = v[o.dataset.a] || []).push(o.value); });
          sp.types[cb.dataset.t] = { on: cb.checked, count: +row.querySelector('.tcount').value || 0, variations: v };
        });
        return any ? sp : null;
      }
      function drawTypes(checking) {
        var keep = readTypes();
        document.getElementById('types').innerHTML = (checking ? '<p class="muted" id="tcheck" style="margin:0 0 8px">' + L('Checking the market rules… warnings appear here in a few seconds. You can fill in the form meanwhile.') + '</p>' : '')
          + '<div class="types" style="overflow-x:auto"><table><thead><tr><th>' + L('Type') + '</th><th>' + L('Sites') + '</th><th>' + L('Variations (tick one or more)') + '</th></tr></thead><tbody>'
          + Object.keys(cat.types).filter(function (t) { return !cat.types[t].hidden || (ed && ed.spec.types[t]); }).map(function (t) {
            var a = avail[t] || { ok: true, reason: null, options: {} }, T = cat.types[t], notes = [];
            var axes = Object.keys(T.axes).map(function (ax) {
              var blk = (a.options && a.options[ax]) || {};
              return '<div class="axis"><b>' + esc(L(T.axes[ax].label)) + '</b>' + Object.keys(T.axes[ax].options).map(function (o, k) {
                if (blk[o]) notes.push(L(T.axes[ax].options[o]) + ': ' + blk[o]);
                return '<label' + (blk[o] ? ' class="blk" title="' + esc(blk[o]) + '"' : '') + '><input type="checkbox" class="opt" data-t="' + t + '" data-a="' + ax + '" value="' + o + '"' + (k === 0 ? ' checked' : '') + '> ' + esc(L(T.axes[ax].options[o])) + '</label>';
              }).join('') + (T.axes[ax].hint ? '<small class="muted" style="width:100%;font-size:12px">' + esc(L(T.axes[ax].hint)) + '</small>' : '') + '</div>';
            }).join('') || '<span class="muted">' + L('No content variations; layouts and angles differ per site.') + '</span>';
            return '<tr><td><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" class="ton" data-t="' + t + '"> <strong>' + esc(L(T.label)) + '</strong></label>'
              + (a.ok ? '' : '<span class="wn">⚠ ' + esc(a.reason) + ' ' + L('You can still build it: the sites will carry this warning.')
                + ((a.clean || []).length ? ' ' + L('No warning with: {0}.', esc(a.clean.map(function (o) { for (var ax in T.axes) if (T.axes[ax].options[o]) return L(T.axes[ax].options[o]); return o; }).join(', '))) : '') + '</span>') + '</td>'
              + '<td><input type="number" class="tcount" min="1" max="50" value="3" aria-label="' + esc(L('How many {0} sites', L(T.label))) + '" style="width:76px"></td><td>' + axes
              + notes.map(function (n) { return '<span class="wn">⚠ ' + esc(n) + '</span>'; }).join('') + '</td></tr>';
          }).join('') + '</tbody></table></div>';
        [].forEach.call(document.querySelectorAll('.ton,.tcount'), function (x) { x.oninput = x.onchange = totals; });
        if (keep) prefillTypes(keep, true);
        totals();
      }
      var asked = 0;
      function loadMarket() {
        var n = ++asked, m = mk.value;
        avail = AVAIL[m] || {};
        drawTypes(!AVAIL[m]);
        if (ed && !prefilled) { prefilled = true; prefillTypes(ed.spec); }
        if (AVAIL[m]) return;
        api('availability?market=' + m).then(function (a) { AVAIL[m] = a; if (n === asked) { avail = a; drawTypes(); } }).catch(fail);
      }
      function prefillTypes(sp, all) {
        Object.keys(sp.types || {}).forEach(function (t) {
          var cb = document.querySelector('.ton[data-t="' + t + '"]');
          if (!cb) return;
          var row = cb.closest('tr'), v = sp.types[t].variations || {};
          cb.checked = all ? sp.types[t].on : true;
          row.querySelector('.tcount').value = sp.types[t].count;
          [].forEach.call(row.querySelectorAll('.opt'), function (o) { if (v[o.dataset.a]) o.checked = v[o.dataset.a].indexOf(o.value) > -1; });
        });
        totals();
      }
      if (ed) {
        document.getElementById('jname').value = ed.name;
        document.getElementById('jcopy').value = ed.spec.copy || 'demo';
        document.getElementById('jrows').value = ed.spec.rows || 'swipe';
        document.getElementById('jdisc').value = ed.spec.disclosure || 'off';
        document.getElementById('jsample').value = ed.spec.sample_brands == null ? 0 : ed.spec.sample_brands;
        mk.value = ed.market;
        var bl = (ed.spec.brands || []).map(function (b) { return typeof b === 'string' ? { name: b.charAt(0).toUpperCase() + b.slice(1) } : b; });
        document.getElementById('brows').innerHTML = '';
        (bl.length ? bl : [{}]).forEach(function (b) { document.getElementById('brows').appendChild(brandRow(b)); });
        Object.keys(ed.spec.colors || {}).forEach(function (c) {
          var cb = document.querySelector('.cauto[data-c="' + c + '"]');
          if (cb && ed.spec.colors[c]) { cb.checked = false; document.getElementById('c-' + c).value = ed.spec.colors[c]; cb.onchange(); }
        });
        document.getElementById('go').textContent = L('Save and rebuild');
      }
      mk.onchange = loadMarket;
      loadMarket();
      form.onsubmit = function (e) {
        e.preventDefault();
        var types = {}, err = document.getElementById('ferr');
        [].forEach.call(document.querySelectorAll('.ton:checked'), function (cb) {
          var t = cb.dataset.t, row = cb.closest('tr'), v = {};
          [].forEach.call(row.querySelectorAll('.opt:checked'), function (o) { (v[o.dataset.a] = v[o.dataset.a] || []).push(o.value); });
          types[t] = { count: +row.querySelector('.tcount').value || 0, variations: v };
        });
        var colors = {};
        [].forEach.call(document.querySelectorAll('.cauto'), function (cb) { if (!cb.checked) colors[cb.dataset.c] = document.getElementById('c-' + cb.dataset.c).value; });
        if (!totals()) { err.hidden = false; err.textContent = L('Tick at least one site type.'); return; }
        var brands = brandsValue();
        if (!brands.length) { err.hidden = false; err.textContent = L('Add at least one brand.'); document.querySelector('.bname').focus(); return; }
        try { localStorage.setItem(bkey, JSON.stringify(brands)); } catch (e) {}
        document.getElementById('go').disabled = true;
        api(ed ? 'jobs/' + ed.id : 'jobs', { project_id: +pid, name: document.getElementById('jname').value, market: mk.value, types: types, colors: colors,
          brands: brands, copy: document.getElementById('jcopy').value, rows: document.getElementById('jrows').value, sample_brands: +document.getElementById('jsample').value || 0, disclosure: document.getElementById('jdisc').value }, ed ? 'PUT' : 'POST')
          .then(function (r) { location.hash = '#/j/' + r.id; })
          .catch(function (x) { err.hidden = false; err.textContent = L(x.message); document.getElementById('go').disabled = false; });
      };
    }).catch(fail);
  }

  /* ---------- job: gallery, differences, mobile checks */
  var PER_PAGE = 10, PAGE = {};
  var EDIT = false; // job page mode: View (default) or Edit (editing links on every type and site)
  try { EDIT = localStorage.getItem('gen-edit') === '1'; } catch (e) {}
  var BRIEFS = { open: false, off: {} }; // job page: the AI briefs download panel, and the site types left out
  var TIP_SEEN = false; // the edit-mode tip, closed once
  try { TIP_SEEN = localStorage.getItem('gen-edit-tip') === '1'; } catch (e) {}
  function saveScroll() { try { sessionStorage.setItem('scroll:' + location.hash, String(window.scrollY)); } catch (e) {} }
  var scrollTimer = null;
  window.addEventListener('scroll', function () { clearTimeout(scrollTimer); scrollTimer = setTimeout(saveScroll, 150); });
  if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
  var restored = {};
  function restoreScroll() {
    if (restored[location.hash]) return;
    restored[location.hash] = true;
    var y = 0;
    try { y = +sessionStorage.getItem('scroll:' + location.hash) || 0; } catch (e) {}
    if (y) window.scrollTo(0, y);
  }

  function mobileChips(m) {
    if (!m) return '<span class="chip">' + L('Phone check pending') + '</span>';
    return '<span class="chip ' + (m.overflow ? 'bad' : 'ok') + '">' + (m.overflow ? L('Scrolls sideways on phones') : L('Fits phones')) + '</span>'
      + (m.cta == null ? '' : '<span class="chip ' + (m.cta > m.fold ? 'warn' : 'ok') + '">' + (m.cta > m.fold ? L('CTA below the fold ({0} px)', m.cta) : L('CTA on the first screen')) + '</span>');
  }
  /* a card = thumbnail (kept while the build is the same, so it does not reload) + body (redrawn when anything changes) */
  function drawCard(card, s) {
    var tkey = s.status + '|' + (s.v || '') + '|' + (s.thumb || ''), bkey = JSON.stringify([s.status, s.mobile, s.warnings.length, s.blocks, s.pages, s.preset, s.brand, s.edited, EDIT]);
    if (card.dataset.t !== tkey) {
      card.dataset.t = tkey;
      var th = card.querySelector('.thumb') || card.appendChild(document.createElement('div'));
      th.className = 'thumb';
      th.innerHTML = s.status === 'built' && s.thumb
        ? '<img loading="lazy" decoding="async" alt="" src="' + esc(s.thumb) + '" data-live="' + esc(s.url + '?v=' + s.v) + '">'
        : s.status === 'built'
        ? '<iframe loading="lazy" tabindex="-1" aria-hidden="true" sandbox="allow-same-origin" src="' + s.url + '?v=' + esc(s.v) + '"></iframe>'
        : '<div class="st">' + (s.status === 'failed' ? L('Failed: {0}', esc(s.error)) : esc(L(s.status)) + '…') + '</div>';
      var im = th.querySelector('img');
      if (im) im.onerror = function () { th.innerHTML = '<iframe loading="lazy" tabindex="-1" aria-hidden="true" sandbox="allow-same-origin" src="' + im.dataset.live + '"></iframe>'; scaleThumb(card); };
      var fr = th.querySelector('iframe');
      if (fr) {
        fr.onload = function () {
          try {
            var st = fr.contentDocument.createElement('style');
            st.textContent = '[role=dialog][id^="age-"],#sx-devtoggle,#sx-skeltoggle{display:none!important}html{overflow:auto!important}';
            fr.contentDocument.head.appendChild(st);
          } catch (e) {}
        };
        scaleThumb(card);
      }
    }
    if (card.dataset.b === bkey) return;
    card.dataset.b = bkey;
    var body = card.querySelector('.body') || card.appendChild(document.createElement('div'));
    body.className = 'body';
    body.innerHTML = '<div class="row"><span class="name">' + esc(s.id) + '</span><span class="sp"></span>' + (s.brand ? '<span class="chip">' + esc(s.brand_name || s.brand) + '</span>' : '') + '</div>'
      + '<div class="chips">' + s.choice.map(function (c) { return '<span class="chip' + (c.auto ? ' auto' : '') + '" title="' + (c.auto ? L('picked by the generator') : L('from the job form')) + '">' + esc(optLabel(c.label)) + '</span>'; }).join('') + '<span class="chip auto">' + L('style: {0}', esc(s.preset)) + '</span>' + (s.edited ? '<span class="chip warn" title="' + L('This site has its own settings') + '">' + L('edited') + '</span>' : '') + (s.pages > 1 ? '<span class="chip auto">' + L('{0} pages', s.pages) + '</span>' : '') + '</div>'
      + (s.status === 'built' ? '<div class="chips">' + checkChips(s.checks) + mobileChips(s.mobile) + '</div>' : '')
      + (s.blocks.length ? '<div>' + s.blocks.map(function (w) { return '<span class="wn" style="max-width:none">⚠ ' + L('Market rule overridden: {0}', esc(w)) + '</span>'; }).join('') + '</div>' : '')
      + (s.warnings.length ? '<details class="warn"><summary>' + (s.warnings.length > 1 ? L('{0} build notes', s.warnings.length) : L('1 build note')) + '</summary><ul>' + s.warnings.slice(0, 12).map(function (w) { return '<li>' + esc(w) + '</li>'; }).join('') + '</ul></details>' : '')
      + (s.status === 'built' ? '<div class="acts"><a class="btn primary" href="' + s.url + '" target="_blank" rel="noopener">' + L('Open site') + '</a>' + (s.brief ? '<a class="btn" href="' + s.brief + '" download title="' + L('Download the AI brief for the whole site (.txt)') + '">' + L('AI brief') + ' ↓</a>' : '') + (EDIT ? '<a class="btn" href="#/s/' + s.id + '">' + L('Edit') + '</a>' : '') + '</div>' : '');
  }

  function checkChips(c) {
    if (!c) return '';
    var one = function (v, name, min) {
      if (min === null || min === undefined) return '';
      if (v === 'pending') return '<span class="chip" title="' + L('Screenshots are compared once every site of the job is built') + '">' + L('{0}: checked after the build', name) + '</span>';
      if (!v) return '<span class="chip" title="' + L('No other site of this type to compare with') + '">' + L('{0}: nothing to compare', name) + '</span>';
      return '<span class="chip ' + (v.ok ? 'ok' : 'bad') + '" title="' + L('Most similar: {0} · threshold {1}%', esc(v.site), min) + '">' + name + ' ' + v.min + '%' + (v.ok ? '' : L(' · under {0}%', min)) + '</span>';
    };
    return one(c.code, L('Code'), c.thresholds.code) + one(c.visual, L('Visual'), c.thresholds.visual)
      + (c.attempts > 1 ? '<span class="chip auto" title="' + L('Re-planned and rebuilt to meet the thresholds') + '">' + L('rebuilt {0}×', c.attempts - 1) + '</span>' : '');
  }

  /* ---------- job: gallery, differences, mobile checks */
  /* The AI briefs download: one zip of the chosen site types' briefs (all by default), a folder per type when several */
  function briefPanel(j, d) {
    var counts = {};
    d.sites.forEach(function (s) { if (s.status === 'built') counts[s.type] = (counts[s.type] || 0) + 1; });
    var types = Object.keys(counts);
    return '<div id="bpanel" class="bpanel" role="group" aria-label="' + L('AI briefs') + '"><div class="bhead"><b>' + L('Download AI briefs') + '</b>'
      + '<button type="button" class="link" id="ball">' + L('All') + '</button><button type="button" class="link" id="bnone">' + L('None') + '</button></div>'
      + '<div class="btypes">' + types.map(function (t) {
        return '<label><input type="checkbox" data-btype="' + esc(t) + '"' + (BRIEFS.off[t] ? '' : ' checked') + '> ' + esc(L(d.types[t] || t)) + ' <span class="muted">· ' + counts[t] + '</span></label>';
      }).join('') + '</div>'
      + '<div class="bfoot"><a class="btn primary" id="bdl" download>' + L('Download .zip') + '</a><span class="muted" id="bsum"></span></div>'
      + '<p class="muted bnote">' + L('One .txt file per built site: the system prompt and the rebuild brief for all its pages. With several site types, each type gets its own folder.') + '</p></div>';
  }
  function briefWire(j, d) {
    var b = document.getElementById('briefs');
    if (b) b.onclick = function () { BRIEFS.open = !BRIEFS.open; job(j.id, true); };
    var p = document.getElementById('bpanel');
    if (!p) return;
    var boxes = [].slice.call(p.querySelectorAll('[data-btype]'));
    var sync = function () {
      var on = boxes.filter(function (x) { return x.checked; }).map(function (x) { return x.dataset.btype; });
      boxes.forEach(function (x) { BRIEFS.off[x.dataset.btype] = !x.checked; });
      var n = d.sites.filter(function (s) { return s.status === 'built' && on.indexOf(s.type) >= 0; }).length;
      var a = document.getElementById('bdl');
      if (on.length) { a.href = '/generator/api/jobs/' + j.id + '/briefs' + (on.length < boxes.length ? '?types=' + on.join(',') : ''); a.removeAttribute('aria-disabled'); }
      else { a.removeAttribute('href'); a.setAttribute('aria-disabled', 'true'); }
      document.getElementById('bsum').textContent = !on.length ? L('Choose at least one site type.') : on.length > 1 ? L('{0} sites in {1} folders', n, on.length) : L('{0} sites', n);
    };
    boxes.forEach(function (x) { x.onchange = sync; });
    document.getElementById('ball').onclick = function () { boxes.forEach(function (x) { x.checked = true; }); sync(); };
    document.getElementById('bnone').onclick = function () { boxes.forEach(function (x) { x.checked = false; }); sync(); };
    sync();
  }
  var LAST = {};
  /* local: redraw from the data already loaded (switching pages), without asking the server again */
  function job(id, local) {
    (local && LAST[id] ? Promise.resolve(LAST[id]) : api('jobs/' + id)).then(function (d) {
      LAST[id] = d;
      if (location.hash !== '#/j/' + id) return;
      var j = d.job, built = d.sites.filter(function (s) { return s.status === 'built'; }).length, failed = d.sites.filter(function (s) { return s.status === 'failed'; }).length;
      var running = j.status === 'planned' || j.status === 'queued' || j.status === 'running' || j.status === 'checking';
      var byType = {};
      d.sites.forEach(function (s) { (byType[s.type] = byType[s.type] || []).push(s); });
      if (!document.getElementById('jobhead')) {
        main.innerHTML = '<p><a href="#/p/' + j.project_id + '">← ' + esc(j.project_name) + '</a></p><div id="jobhead"></div><div id="sites"></div><h3>' + L('How different are the sites?') + '</h3><div id="diffnote"></div><div id="diff" class="muted">' + L('Shown when the sites are built.') + '</div>';
      }
      document.getElementById('jobhead').innerHTML = '<div class="row"><h2>' + esc(j.name) + '</h2><span class="chip">' + esc(j.market.toUpperCase()) + '</span><span class="sp"></span>'
        + (j.status === 'stopped' ? '<button id="retry" class="primary">' + L('Continue the build') + '</button>' : failed && !running ? '<button id="retry">' + L('Retry failed ({0})', failed) + '</button>' : '')
        + (running ? '' : '<button id="emode" class="' + (EDIT ? 'primary' : '') + '" aria-pressed="' + EDIT + '" title="' + L('Shows the links to edit a whole type or a single site') + '">' + (EDIT ? L('Done editing') : L('Edit mode')) + '</button>')
        + (running ? '' : '<a class="btn" href="#/j/' + j.id + '/edit">' + L('Edit job') + '</a><button id="restyle" title="' + L('New seed, style, palette and hero layout for every site; the variations stay') + '">' + L('New looks') + '</button><button id="rebuild" title="' + L('Same plans (seeds, styles, variations), current engine; sites under the difference thresholds are re-planned') + '">' + L('Rebuild all') + '</button>') + (built ? '<button id="briefs" aria-expanded="' + BRIEFS.open + '" aria-controls="bpanel">' + L('AI briefs') + ' ↓</button>' : '') + '<button class="danger" id="delj">' + L('Delete job') + '</button></div>'
        + (built && BRIEFS.open ? briefPanel(j, d) : '')
        + '<div style="margin:6px 0 2px;font-size:14px"><label style="display:inline-flex;gap:8px;align-items:center"><input type="checkbox" id="jcmp"' + (+j.compare ? ' checked' : '') + '> ' + L('Count this job in comparisons') + '</label>'
        + '<div class="muted" style="font-size:13px;margin-top:2px">' + L('Untick for a draft or test job: other jobs are not compared with its sites or rebuilt to differ from them. Its own sites are still checked.') + '</div></div>'
        + '<p class="muted">' + (running ? '' : L('{0} of {1} sites built', built, d.sites.length) + (failed ? L(', {0} failed', failed) : '') + '. ') + L('Open a site to see every page, the phone view, the developer panel and the AI brief.') + '</p>'
        + '<div class="bar"><i style="width:' + Math.round(100 * (built + failed) / Math.max(1, d.sites.length)) + '%"></i></div>'
        + (j.status === 'stopped' ? '<p class="note">' + L('The build was stopped before it finished ({0} of {1} sites built). Continue the build to finish the rest.', built, d.sites.length) + '</p>' : '')
        + (j.status === 'queued' ? '<div class="notice" role="status"><span class="spin" aria-hidden="true"></span><span><b>' + L('Waiting for another job') + '.</b> ' + esc(L('Job "{0}" is building now. This job starts automatically when it is done; one job builds at a time, so the generator stays responsive.', j.busy ? j.busy.name : '…')) + '</span></div>'
        : running ? '<div class="notice" role="status"><span class="spin" aria-hidden="true"></span><span><b>' + (j.status === 'checking' ? L('Checking the differences') : j.status === 'planned' ? L('Starting the build') : L('Rebuilding: {0} of {1} sites done', built, d.sites.length)) + '.</b> '
          + (j.status === 'checking' ? L('Screenshots are compared site by site; a site that looks too alike is rebuilt, so its card can still change.') : L('Sites are built one by one; each card comes back when its site is ready.'))
          + ' ' + L('The comparison table updates when the job is done. You can leave this page; the build carries on.') + '</span></div>' : '')
        + (EDIT && !running && !TIP_SEEN ? '<p class="note" id="etip" style="display:flex;gap:12px;align-items:flex-start"><span style="flex:1">' + L('Edit mode: change a whole type with “Edit all” next to its name, or one site with “Edit” on its card. Your settings stay when the job is rebuilt.') + '</span><button type="button" id="etipx" aria-label="' + L('Close') + '" style="padding:2px 8px;line-height:1">×</button></p>' : '')
        + '<p class="err" id="jerr" hidden></p>';
      var dEl = document.getElementById('diff');
      dEl.classList.toggle('stale', running);
      document.getElementById('diffnote').innerHTML = running ? '<p class="notice"><span class="spin" aria-hidden="true"></span><span>' + (dEl.querySelector('table') ? L('These numbers are from the previous build. They update when the rebuild is done.') : L('The comparison appears when the build is done.')) + '</span></p>' : '';
      var sitesEl = document.getElementById('sites'), shown = [];
      [].forEach.call(sitesEl.querySelectorAll('section'), function (sec) { if (!byType[sec.id.slice(2)]) sec.remove(); });
      Object.keys(byType).forEach(function (t) {
        var sec = document.getElementById('t-' + t);
        if (!sec) {
          sec = document.createElement('section');
          sec.id = 't-' + t;
          sec.innerHTML = '<h3></h3><div class="grid"></div><div class="pager"></div>';
          sitesEl.appendChild(sec);
        }
        sec.querySelector('h3').innerHTML = esc(L(d.types[t])) + ' <span class="muted">· ' + byType[t].length + '</span>' + (running || !EDIT ? '' : ' <a class="btn" style="margin-left:8px;padding:4px 10px;font-size:13px" href="#/j/' + j.id + '/type/' + t + '">' + L('Edit all') + '</a>');
        var grid = sec.querySelector('.grid'), ids = {}, pages = Math.ceil(byType[t].length / PER_PAGE), key = id + ':' + t;
        var pg = Math.min(PAGE[key] || 0, pages - 1);
        var pager = sec.querySelector('.pager');
        pager.innerHTML = pages > 1 ? Array.apply(null, Array(pages)).map(function (_, i) {
          return '<button type="button" data-p="' + i + '"' + (i === pg ? ' aria-current="page"' : '') + '>' + (i + 1) + '</button>';
        }).join('') + '<span class="muted" style="font-size:13px">' + L('{0}–{1} of {2}', pg * PER_PAGE + 1, Math.min(byType[t].length, (pg + 1) * PER_PAGE), byType[t].length) + '</span>' : '';
        [].forEach.call(pager.querySelectorAll('button'), function (b) {
          b.onclick = function () { PAGE[key] = +b.dataset.p; job(id, true); sec.scrollIntoView({ block: 'start' }); };
        });
        byType[t].slice(pg * PER_PAGE, (pg + 1) * PER_PAGE).forEach(function (s) {
          shown.push(s);
          ids['s-' + s.id] = true;
          var card = document.getElementById('s-' + s.id);
          if (!card) { card = document.createElement('article'); card.className = 'site'; card.id = 's-' + s.id; grid.appendChild(card); }
          drawCard(card, s);
        });
        [].forEach.call(grid.querySelectorAll('.site'), function (c) { if (!ids[c.id]) c.remove(); });
      });
      var jerr = function (x) { var e = document.getElementById('jerr'); e.hidden = false; e.textContent = L(x.message || String(x)); };
      var del = document.getElementById('delj');
      del.onclick = function () { if (confirm(L('Delete this job and its sites?'))) api('jobs/' + id, null, 'DELETE').then(function () { location.hash = '#/p/' + j.project_id; }).catch(jerr); };
      var retry = document.getElementById('retry');
      if (retry) retry.onclick = function () { retry.disabled = true; retry.textContent = L('Starting…'); api('jobs/' + id + '/retry', null, 'POST').then(function () { job(id); }).catch(jerr); };
      var em = document.getElementById('emode');
      if (em) em.onclick = function () { EDIT = !EDIT; try { localStorage.setItem('gen-edit', EDIT ? '1' : '0'); } catch (e) {} job(id, true); };
      briefWire(j, d);
      var tipx = document.getElementById('etipx'); // the edit-mode tip, once closed, stays closed
      if (tipx) tipx.onclick = function () { TIP_SEEN = true; try { localStorage.setItem('gen-edit-tip', '1'); } catch (e) {} var t = document.getElementById('etip'); if (t) t.remove(); };
      var cmp = document.getElementById('jcmp');
      cmp.onchange = function () { cmp.disabled = true; api('jobs/' + id + '/compare', { compare: cmp.checked }, 'PUT').then(function () { cmp.disabled = false; diff(id, d); }).catch(function (x) { cmp.disabled = false; cmp.checked = !cmp.checked; jerr(x); }); };
      var rs = document.getElementById('restyle');
      if (rs) rs.onclick = function () { if (!confirm(L('Give every site of this job a new seed, style, palette and hero layout? The variations stay the same.'))) return; rs.disabled = true; rs.textContent = L('Starting…'); api('jobs/' + id + '/restyle', null, 'POST').then(function () { job(id); }).catch(function (x) { rs.disabled = false; rs.textContent = L('New looks'); jerr(x); }); };
      var rb = document.getElementById('rebuild');
      if (rb) rb.onclick = function () { rb.disabled = true; rb.textContent = L('Starting…'); api('jobs/' + id + '/rebuild', null, 'POST').then(function () { job(id); }).catch(function (x) { rb.disabled = false; rb.textContent = L('Rebuild all'); jerr(x); }); };
      clearTimeout(poll);
      if (running) {
        restoreScroll();
        poll = setTimeout(function () { if (location.hash === '#/j/' + id) job(id); }, 4000);
      } else {
        if (!local) diff(id, d); // the differences do not change with the page shown
        mobileChecks(id, shown.filter(function (s) { return s.status === 'built' && !s.mobile; }));
      }
    }).catch(fail);
  }
  function scaleThumb(card) {
    var th = card.querySelector('.thumb'), fr = th && th.querySelector('iframe');
    if (fr) fr.style.transform = 'scale(' + (th.clientWidth / 1280) + ')';
  }
  window.addEventListener('resize', function () { [].forEach.call(document.querySelectorAll('.site'), scaleThumb); });

  /* differences: per site, its most similar site in the job, in the rest of the project and in every other job */
  function verdict(code) {
    return code >= 90 ? ['ok', L('Very different')] : code >= 75 ? ['ok', L('Different')] : code >= 50 ? ['warn', L('Somewhat alike')] : ['bad', L('Too alike')];
  }
  function diffCell(c, label, st) {
    if (!c) return '<td><small>' + L(label) + '</small></td>';
    var short = function (i) { return i.replace(/^j(\d+)-/, function (m, n) { return L('job {0} · ', n); }); };
    var vd = verdict(c.code.code), code = '<div><span class="pct">' + c.code.code + '%</span> ' + L('code') + ' <span class="chip ' + (c.code.code < st.code_min ? 'bad' : vd[0]) + '">' + (c.code.code < st.code_min ? L('Under {0}%', st.code_min) : vd[1]) + '</span><small>' + L('vs {0} · structure {1}% different', esc(short(c.code.site)), c.code.skeleton) + '</small></div>';
    var vis = c.visual ? '<div style="margin-top:6px"><span class="pct">' + c.visual.visual + '%</span> ' + L('visual') + ' <span class="chip ' + (c.visual.visual < st.visual_min ? 'bad' : 'ok') + '">' + (c.visual.visual < st.visual_min ? L('Under {0}%', st.visual_min) : L('Meets {0}%', st.visual_min)) + '</span><small>' + L('vs {0}', esc(short(c.visual.site))) + '</small></div>' : (st.visual ? '<small>' + L('No screenshots yet') + '</small>' : '');
    return '<td>' + code + vis + '</td>';
  }
  function overview(byType, x, st, d) {
    var tot = { n: 0, uc: 0, uv: 0, alike: 0 };
    var rows = Object.keys(byType).map(function (t) {
      var r = { n: byType[t].length, uc: 0, uv: 0, alike: 0, code: null, vis: null };
      byType[t].forEach(function (a) {
        var c = x.closest[a] || {}, code = null, vis = null;
        ['job', 'project', 'global'].forEach(function (k) {
          var v = c[k];
          if (!v) return;
          if (code === null || v.code.code < code) code = v.code.code;
          if (v.visual && (vis === null || v.visual.visual < vis)) vis = v.visual.visual;
        });
        if (code !== null && code < st.code_min) r.uc++;
        else if (code !== null && code < 75) r.alike++;
        if (st.visual && vis !== null && vis < st.visual_min) r.uv++;
        if (code !== null && (r.code === null || code < r.code)) r.code = code;
        if (vis !== null && (r.vis === null || vis < r.vis)) r.vis = vis;
      });
      ['n', 'uc', 'uv', 'alike'].forEach(function (k) { tot[k] += r[k]; });
      var num = function (v, cls) { return v ? '<span class="chip ' + cls + '">' + v + '</span>' : '<span class="muted">0</span>'; };
      return '<tr><th scope="row"><a href="#dt-' + t + '" data-jump="dt-' + t + '">' + esc(L(d.types[t])) + '</a></th><td>' + r.n + '</td><td>' + num(r.uc, 'bad') + '</td>'
        + (st.visual ? '<td>' + num(r.uv, 'bad') + '</td>' : '') + '<td>' + num(r.alike, 'warn') + '</td><td>' + (r.code === null ? '–' : r.code + '%') + '</td>' + (st.visual ? '<td>' + (r.vis === null ? '–' : r.vis + '%') + '</td>' : '') + '</tr>';
    });
    return '<h3 style="margin-top:14px">' + L('Overview') + '</h3><p class="muted" style="margin:0 0 6px">' + L('Per type, against the most similar site in this job or any other job.') + ' '
      + (tot.uc + tot.uv ? '<b>' + L('{0} under a threshold', tot.uc + tot.uv) + '</b>, ' : L('Nothing under a threshold, ')) + L('{0} somewhat alike (code {1}–74%), out of {2} sites. Click a type for its sites.', tot.alike, st.code_min, tot.n) + '</p>'
      + '<div class="matrix card dt ov" style="padding:0"><table><thead><tr><th>' + L('Type') + '</th><th>' + L('Sites') + '</th><th>' + L('Under code {0}%', st.code_min) + '</th>' + (st.visual ? '<th>' + L('Under visual {0}%', st.visual_min) + '</th>' : '')
      + '<th>' + L('Somewhat alike') + '</th><th>' + L('Lowest code') + '</th>' + (st.visual ? '<th>' + L('Lowest visual') + '</th>' : '') + '</tr></thead><tbody>' + rows.join('') + '</tbody></table></div>';
  }
  var openDetails = {};
  function diff(id, d) {
    api('jobs/' + id + '/diff').then(function (x) {
      if (x.pending) { // worked out in the background: ask again shortly, while this job's page is open
        document.getElementById('diff').innerHTML = '<p class="muted">' + L('Working out the differences… On a big network this takes up to a minute.') + '</p>';
        setTimeout(function () { if (location.hash === '#/j/' + id) diff(id, d); }, 4000);
        return;
      }
      var byType = {}, st = x.settings;
      d.sites.filter(function (s) { return s.status === 'built'; }).forEach(function (s) { (byType[s.type] = byType[s.type] || []).push(s.id); });
      var types = Object.keys(byType);
      document.getElementById('diff').innerHTML = '<p class="muted">' + L('Each site compared with the <b>most similar</b> site of the same type, in this job, in the project\u2019s other jobs and in every other job. <b>Code</b> = how much of the CSS and HTML differs (renamed classes, whitespace and order do not count). <b>Visual</b> = how much of the first screen looks different, desktop and phone. Higher is better; 100% = nothing in common.')
        + ' ' + L('Thresholds: code {0}%', st.code_min) + (st.visual ? L(', visual {0}%', st.visual_min) : L(' (visual check off)')) + ', ' + L('compared with {0}', L(({ all: 'every job', project: 'the project', job: 'this job' })[st.scope])) + ' · <a href="#/settings">' + L('change') + '</a>.</p>'
        + '<div class="legend"><span class="chip ok">' + L('code 90%+ very different') + '</span><span class="chip ok">' + L('75–89% different') + '</span><span class="chip warn">' + L('50–74% somewhat alike') + '</span><span class="chip bad">' + L('under the threshold') + '</span></div>'
        + overview(byType, x, st, d)
        + '<details class="more" id="dtables"' + (openDetails[id] ? ' open' : '') + '><summary>' + L('Site by site: {0} types, {1} sites', types.length, types.reduce(function (n, t) { return n + byType[t].length; }, 0)) + '</summary>'
        + types.map(function (t) {
          return '<h3 id="dt-' + t + '" style="margin-top:14px">' + esc(L(d.types[t])) + '</h3><div class="matrix card dt" style="padding:0"><table><thead><tr><th>' + L('Site') + '</th><th>' + L('Closest in this job') + '</th><th>' + L('Closest in other jobs of the project') + '</th><th>' + L('Closest in all other jobs') + '</th></tr></thead><tbody>'
            + byType[t].map(function (a) {
              var c = x.closest[a] || {};
              return '<tr><th scope="row">' + esc(a.replace(/^j\d+-/, '')) + '</th>' + diffCell(c.job, 'only site of this type', st) + diffCell(c.project, 'no other jobs in this project', st) + diffCell(c.global, 'no other jobs', st) + '</tr>';
            }).join('') + '</tbody></table></div>';
        }).join('') + '</details>';
      var dt = document.getElementById('dtables');
      dt.ontoggle = function () { openDetails[id] = dt.open; };
      [].forEach.call(document.querySelectorAll('[data-jump]'), function (a) {
        a.onclick = function (e) { e.preventDefault(); dt.open = true; openDetails[id] = true; var el = document.getElementById(a.dataset.jump); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' }); };
      });
      restoreScroll();
    }).catch(function () { restoreScroll(); });
  }

  /* phone check: 375 x 667, sideways scroll and where the hero CTA ends; run in this browser, stored with the site */
  function mobileChecks(id, list) {
    if (!list.length) return;
    var s = list.shift(), f = document.createElement('iframe');
    f.setAttribute('aria-hidden', 'true');
    f.tabIndex = -1;
    f.style.cssText = 'position:fixed;left:-10000px;top:0;width:375px;height:667px;border:0;visibility:hidden';
    f.onload = function () {
      setTimeout(function () {
        var r = { overflow: 0, cta: null };
        try {
          var doc = f.contentDocument, g = doc.querySelector('[role=dialog][id^="age-"]');
          if (g) g.hidden = true;
          doc.documentElement.style.overflow = '';
          r.overflow = doc.documentElement.scrollWidth > 376 ? doc.documentElement.scrollWidth : 0;
          var c = doc.querySelector('[data-cta="hero"]') || doc.querySelector('main [data-cta]');
          if (c) r.cta = Math.round(c.getBoundingClientRect().bottom + (f.contentWindow.scrollY || 0));
        } catch (e) {}
        f.remove();
        api('sites/' + s.id + '/mobile', r).then(function () {
          if (location.hash === '#/j/' + id) {
            if (!list.length) job(id); else mobileChecks(id, list);
          }
        });
      }, 400);
    };
    f.src = s.url;
    document.body.appendChild(f);
  }

  /* ---------- editor: a type in a job (bulk) or one site; what is left on Automatic stays with the generator */
  function editor(kind, id, type) {
    var url = kind === 'type' ? 'jobs/' + id + '/editor/' + type : 'sites/' + id + '/editor';
    api(url).then(function (d) {
      var S = d.schema, E = JSON.parse(JSON.stringify(d.edits || {})), page = S.pages[0].id, CUR = d.current || null;
      if (Array.isArray(E)) E = {};
      var back = '#/j/' + d.job.id;
      var opt = function (v, label, cur) { return '<option value="' + esc(v) + '"' + (String(cur) === String(v) ? ' selected' : '') + '>' + esc(label) + '</option>'; };
      var sel = function (idA, cur, opts) { return '<select id="' + idA + '">' + opts.map(function (o) { return opt(o[0], o[1], cur == null ? '' : cur); }).join('') + '</select>'; };
      function draw() {
        var col = E.colors || {}, st = E.style || {}, mn = E.menu || {};
        var p = S.pages.filter(function (x) { return x.id === page; })[0], pe = (E.pages || {})[page] || {};
        var order = pe.order || null, mids = p.sections.filter(function (s) { return s.zone === 'middle'; }).map(function (s) { return s.id; });
        var rows = p.sections.slice();
        if (order) rows.sort(function (a, b) { var ia = a.zone === 'middle' ? order.indexOf(a.id) : -1, ib = b.zone === 'middle' ? order.indexOf(b.id) : -1; return a.zone !== b.zone || ia < 0 || ib < 0 ? 0 : ia - ib; });
        var zones = { top: L('top'), middle: L('middle'), bottom: L('bottom') };
        main.innerHTML = '<p><a href="' + back + '">← ' + esc(d.job.name) + '</a></p>'
          + '<h2>' + (kind === 'type' ? L('Edit: {0}', esc(L(d.label))) : L('Edit site {0}', esc(d.site))) + '</h2>'
          + '<p class="muted">' + (kind === 'type' ? L('Applies to all {0} sites of this type in the job. What stays on Automatic is left to the generator, which keeps the sites different. Saving rebuilds these sites and checks their differences again.', d.sites)
            : L('Settings for this site only; they go on top of its type’s settings. What stays on Automatic follows the type and the generator.') + (Object.keys(d.type_edits || {}).length ? ' ' + L('The type has its own settings: <a href="{0}">edit the type</a>.', '#/j/' + d.job.id + '/type/' + d.type) : '')
              + ' <a href="' + d.url + '" target="_blank" rel="noopener">' + L('Open site') + '</a>') + '</p>'
          + '<div class="note">' + L('Fixed choices make sites more alike: change only what you need.') + ' ' + L('Removing pages and sections makes sites shorter and more alike: remove only what you need.') + '</div>'
          + '<form id="ef" class="card" style="display:grid;gap:18px;margin-top:14px">'
          + '<div><h3 style="margin:0 0 10px">' + L('Look') + '</h3><div class="row" style="align-items:flex-start;gap:18px">'
          + '<div class="field"><label class="lbl" for="e-preset">' + L('Style') + '</label>' + sel('e-preset', st.preset, [['', L('Automatic')]].concat(S.presets.map(function (x) { return [x, x]; }))) + '</div>'
          + '<div class="field"><label class="lbl" for="e-flip">' + L('Light / dark') + '</label>' + sel('e-flip', st.flip == null ? '' : (st.flip ? '1' : '0'), [['', L('Automatic')], ['0', L('As the style')], ['1', L('Swapped')]]) + '</div></div>'
          + '<div class="colors">' + [['primary', 'Primary', 'buttons, links, highlights', '#4F46E5'], ['secondary', 'Secondary', 'second highlight colour', '#14B8A6'], ['accent', 'Accent', 'hero background', '#111827']].map(function (c) {
            var v = col[c[0]];
            return '<div class="c"><label class="lbl" for="e-c-' + c[0] + '">' + L(c[1]) + '</label><span class="muted" style="font-size:12px">' + L(c[2]) + '</span><div class="row"><input type="color" id="e-c-' + c[0] + '" value="' + (v || c[3]) + '"' + (v ? '' : ' disabled') + '><label><input type="checkbox" class="e-cauto" data-c="' + c[0] + '"' + (v ? '' : ' checked') + '> ' + L('Automatic') + '</label></div></div>';
          }).join('') + '</div></div>'
          + '<div><h3 style="margin:0 0 10px">' + L('Navigation') + '</h3><div class="row" style="align-items:flex-start;gap:18px">'
          + '<div class="field"><label class="lbl" for="e-nav">' + L('On desktop') + '</label>' + sel('e-nav', E.nav, [['', L('Automatic')]].concat(S.navs.map(function (n) { return [n, L({ top: 'Top menu', left: 'Sidebar on the left', right: 'Sidebar on the right', hub: 'Hub (the home page links to every page)' }[n])]; }))) + '</div>'
          + '<div class="field"><label class="lbl" for="e-look">' + L('Sidebar look') + '</label>' + sel('e-look', E.look, [['', L('Automatic')]].concat(S.looks.map(function (n) { return [n, L({ panel: 'Panel', rail: 'Rail', cards: 'Cards' }[n])]; }))) + '</div>'
          + (['casinos', 'casinos-one', 'reviews', 'news'].indexOf((CAT && CAT.types[d.type] && CAT.types[d.type].base) || d.type) > -1 ? '<div class="field"><label class="lbl" for="e-disc">' + L('Affiliate disclosure') + '</label>' + sel('e-disc', E.disclosure, [['', L('Automatic')], ['top', L('Strip at the top')], ['footer', L('In the footer')], ['off', L('Off (no disclosure)')]]) + '</div>' : '')
          + '<div class="field"><label class="lbl" for="e-rows">' + L('Rows of cards') + '</label>' + sel('e-rows', E.rows, [['', L('Automatic')], ['swipe', L('Swipe')], ['stack', L('Stacked')]]) + '</div>'
          + '<div class="field"><label class="lbl" for="e-mside">' + L('Phone menu side') + '</label>' + sel('e-mside', mn.side, [['', L('Automatic')], ['left', L('Left')], ['right', L('Right')]]) + '</div>'
          + '<div class="field"><label class="lbl" for="e-mstyle">' + L('Phone menu opens as') + '</label>' + sel('e-mstyle', mn.style, [['', L('Automatic')]].concat(S.menu_styles.map(function (n) { return [n, L({ dropdown: 'Dropdown', drawer: 'Side drawer', sheet: 'Under the header', full: 'Full screen' }[n])]; }))) + '</div>'
          + (S.multi ? '' : '<div class="field"><label class="lbl" for="e-mskip">' + L('Phone menu') + '</label>' + sel('e-mskip', mn.skip == null ? '' : (mn.skip ? '1' : '0'), [['', L('Automatic')], ['0', L('Show')], ['1', L('None (the sidebar shows under the content)')]]) + '</div>')
          + '</div></div>'
          + (S.multi ? '<div><h3 style="margin:0 0 10px">' + L('Pages') + '</h3><div class="matrix" style="overflow-x:auto"><table><thead><tr><th>' + L('Page') + '</th>' + (CUR ? '<th>' + L('On this site') + '</th>' : '') + '<th>' + L('Shown') + '</th></tr></thead><tbody>'
            + S.pages.map(function (x) {
              var po = ((E.pages || {})[x.id] || {}).on, cv = po == null ? '' : po ? '1' : '0';
              var ctl = x.keep ? '<span class="muted">' + L('Always: ' + x.keep) + '</span>'
                : '<select class="e-pon" data-p="' + esc(x.id) + '">' + (x.rule === 'optional' ? opt('', L('Automatic'), cv) + opt('1', L('Add'), cv) : opt('', L('Keep'), cv) + (kind === 'site' ? opt('1', L('Keep (over the type)'), cv) : '')) + opt('0', L('Remove'), cv) + '</select>'
                  + (x.required_in.length ? ' <small class="muted">' + L('Required in {0}', esc(x.required_in.join(', ').toUpperCase())) + '</small>' : '');
              return '<tr><td><strong>' + esc(pageLabel(x.id)) + '</strong>' + (x.generated ? ' ×N' : '') + '</td>' + (CUR ? '<td>' + (CUR[x.id] ? L('Yes') : '<span class="muted">' + L('No') + '</span>') + '</td>' : '') + '<td>' + ctl + '</td></tr>';
            }).join('') + '</tbody></table></div></div>' : '')
          + '<div><h3 style="margin:0 0 10px">' + L('Sections') + '</h3>'
          + '<p class="muted" style="margin:0 0 8px;font-size:13px">' + L('“Add” turns on a section the generator left off. Sections outside this type’s design cannot be added: the generator has no copy for them.') + '</p>'
          + (S.pages.length > 1 ? '<div class="pager" style="margin:0 0 10px">' + S.pages.map(function (x) { return '<button type="button" data-page="' + esc(x.id) + '"' + (x.id === page ? ' aria-current="page"' : '') + '>' + esc(pageLabel(x.id)) + (x.generated ? ' ×N' : '') + (((E.pages || {})[x.id] || {}).on === false ? ' ✕' : '') + '</button>'; }).join('') + '</div>' : '')
          + (pe.on === false ? '<p class="note" style="margin:0 0 8px">' + L('This page is removed: its sections are not built.') + '</p>' : '')
          + (p.generated ? '<p class="muted" style="margin:0 0 8px">' + L('One page per item (brand or article): the settings apply to each of them.') + '</p>' : '')
          + (mids.length > 1 ? '<label style="display:inline-flex;gap:8px;align-items:center;margin-bottom:8px"><input type="checkbox" id="e-order"' + (order ? ' checked' : '') + '> ' + L('Set the order of the middle sections') + '</label>' : '')
          + (p.order_rules.length ? '<p class="muted" style="margin:0 0 8px;font-size:13px">' + L('Order rules: {0}', p.order_rules.map(function (r) { var lb = function (i) { return (p.sections.filter(function (s) { return s.id === i; })[0] || {}).label || i; }; return r.rule === 'before' ? L('“{0}” before “{1}”', lb(r.a), lb(r.b)) : L('“{0}” not first', lb(r.a)); }).join(', ')) + '</p>' : '')
          + '<div class="matrix" style="overflow-x:auto"><table><thead><tr><th>' + (order ? L('Order') : '') + '</th><th>' + L('Section') + '</th><th>' + L('Where') + '</th><th>' + L('Shown') + '</th>' + (CUR ? '<th>' + L('On this site') + '</th>' : '') + '<th>' + L('Layout') + '</th></tr></thead><tbody>'
          + rows.map(function (s) {
            var on = (pe.sections || {})[s.id], v = (pe.variants || {})[s.id], mi = order && s.zone === 'middle' ? order.indexOf(s.id) : -1;
            return '<tr><td style="white-space:nowrap">' + (mi > -1 ? '<button type="button" class="e-up" data-s="' + s.id + '" aria-label="' + L('Move up') + '"' + (mi === 0 ? ' disabled' : '') + '>↑</button> <button type="button" class="e-down" data-s="' + s.id + '" aria-label="' + L('Move down') + '"' + (mi === order.length - 1 ? ' disabled' : '') + '>↓</button>' : '') + '</td>'
              + '<td><strong>' + esc(L(s.label)) + '</strong><small class="muted" style="display:block">' + esc(s.id) + (s.content ? '' : ' · ' + L('no demo copy: the block shows its defaults')) + '</small></td>'
              + '<td>' + esc(zones[s.zone] || s.zone) + '</td>'
              + '<td>' + (function () {
                var cv = on == null ? '' : on ? '1' : '0';
                if (s.rule === 'optional') return '<select class="e-on" data-s="' + s.id + '">' + opt('', L('Automatic'), cv) + opt('1', L('Add'), cv) + opt('0', L('Remove'), cv) + '</select>';
                if (s.removable) return '<select class="e-on" data-s="' + s.id + '">' + opt('', L(s.rule === 'apk' ? 'With an APK download' : 'Keep'), cv) + (kind === 'site' ? opt('1', L('Keep (over the type)'), cv) : '') + opt('0', L('Remove'), cv) + '</select>';
                return '<span class="muted">' + L(s.keep ? 'Always: ' + s.keep : s.rule === 'apk' ? 'With an APK download' : 'Always') + '</span>';
              })() + '</td>'
              + (CUR ? '<td>' + (!CUR[page] ? '<span class="muted">' + L('Removed') + '</span>' : CUR[page][s.id] != null ? L('Yes') + (CUR[page][s.id] ? ' <small class="muted">· ' + esc(CUR[page][s.id]) + '</small>' : '') : '<span class="muted">' + L('No') + '</span>') + '</td>' : '')
              + '<td>' + (s.locked || s.variants.length < 2 ? '<span class="muted">' + (s.variants[0] ? esc(s.variants[0]) : '–') + '</span>' : '<select class="e-v" data-s="' + s.id + '">' + opt('', L('Automatic'), v || '') + s.variants.map(function (x) { return opt(x, x, v || ''); }).join('') + '</select>') + '</td></tr>';
          }).join('') + '</tbody></table></div></div>'
          + '<p class="err" id="eerr" hidden></p>'
          + '<div class="row"><button class="primary" id="esave">' + (kind === 'type' ? L('Save and rebuild {0} sites', d.sites) : L('Save and rebuild this site')) + '</button><button type="button" id="ereset">' + L('Reset everything to Automatic') + '</button><a class="btn" href="' + back + '">' + L('Cancel') + '</a></div></form>';
        var pe2 = function () { E.pages = E.pages || {}; E.pages[page] = E.pages[page] || {}; return E.pages[page]; };
        var val = function (i) { var x = document.getElementById(i); return x ? x.value : ''; };
        var read = function () {
          E.style = {}; if (val('e-preset')) E.style.preset = val('e-preset'); if (val('e-flip') !== '') E.style.flip = val('e-flip') === '1';
          E.colors = {}; [].forEach.call(document.querySelectorAll('.e-cauto'), function (cb) { if (!cb.checked) E.colors[cb.dataset.c] = document.getElementById('e-c-' + cb.dataset.c).value; });
          E.nav = val('e-nav') || undefined; E.look = val('e-look') || undefined; E.rows = val('e-rows') || undefined; E.disclosure = val('e-disc') || undefined;
          E.menu = {}; if (val('e-mside')) E.menu.side = val('e-mside'); if (val('e-mstyle')) E.menu.style = val('e-mstyle'); if (val('e-mskip') !== '') E.menu.skip = val('e-mskip') === '1';
          [].forEach.call(document.querySelectorAll('.e-pon'), function (x) { E.pages = E.pages || {}; var q = E.pages[x.dataset.p] = E.pages[x.dataset.p] || {}; if (x.value === '') delete q.on; else q.on = x.value === '1'; });
          [].forEach.call(document.querySelectorAll('.e-on'), function (x) { var q = pe2(); q.sections = q.sections || {}; if (x.value === '') delete q.sections[x.dataset.s]; else q.sections[x.dataset.s] = x.value === '1'; });
          [].forEach.call(document.querySelectorAll('.e-v'), function (x) { var q = pe2(); q.variants = q.variants || {}; if (x.value === '') delete q.variants[x.dataset.s]; else q.variants[x.dataset.s] = x.value; });
        };
        [].forEach.call(document.querySelectorAll('.e-cauto'), function (cb) { cb.onchange = function () { document.getElementById('e-c-' + cb.dataset.c).disabled = cb.checked; }; });
        [].forEach.call(document.querySelectorAll('[data-page]'), function (b) { b.onclick = function () { read(); page = b.dataset.page; draw(); }; });
        var oc = document.getElementById('e-order');
        if (oc) oc.onchange = function () { read(); var q = pe2(); if (oc.checked) q.order = mids.slice(); else delete q.order; draw(); };
        var move = function (sid, dlt) { read(); var o = pe2().order, i = o.indexOf(sid), j = i + dlt; if (j < 0 || j >= o.length) return; o[i] = o[j]; o[j] = sid; draw(); };
        [].forEach.call(document.querySelectorAll('.e-up'), function (b) { b.onclick = function () { move(b.dataset.s, -1); }; });
        [].forEach.call(document.querySelectorAll('.e-down'), function (b) { b.onclick = function () { move(b.dataset.s, 1); }; });
        var err = document.getElementById('eerr'), save = function (edits) {
          var btn = document.getElementById('esave');
          btn.disabled = true;
          api(url, edits, 'PUT').then(function () { location.hash = back; }).catch(function (x) { err.hidden = false; err.textContent = L(x.message); btn.disabled = false; });
        };
        document.getElementById('ef').onsubmit = function (e) { e.preventDefault(); read(); save(E); };
        document.getElementById('ereset').onclick = function () { if (confirm(L('Reset every setting here to Automatic and rebuild?'))) save({}); };
      }
      draw();
    }).catch(fail);
  }

  function pageLabel(id) {
    var en = { home: 'Home', signup: 'Sign-up', payments: 'Payments', app: 'App', betting: 'Betting', casino: 'Casino', bonus: 'Bonus', responsible: 'Responsible gambling',
      review: 'Review (per brand)', compare: 'Comparison', fast: 'Fast payouts', live: 'Live casino', method: 'How we rate', ios: 'iPhone', android: 'Android', features: 'Features',
      updates: 'What\u2019s new', help: 'Help', news: 'News', guides: 'Guides', partners: 'Partner content', about: 'About', article: 'Article (per article)', reviews: 'Reviews',
      payouts: 'Payout reviews', support: 'Support reviews', write: 'Write a review', policy: 'How we moderate' }[id];
    return en ? L(en) : id;
  }

  window.GEN = { api: api, esc: esc, L: L, fail: fail, main: main, RU: RU, pageName: pageLabel };
<?php readfile(__DIR__ . '/markets.js'); ?>
<?php readfile(__DIR__ . '/types.js'); ?>

  function route() {
    clearTimeout(poll);
    var h = location.hash.replace(/^#\/?/, '').split('/');
    if (h[0] === 'settings') return settings();
    if (h[0] === 'markets') return GEN.markets(h);
    if (h[0] === 'types') return GEN.types(h);
    if (h[0] === 'p' && h[2] === 'new') return jobForm(h[1]);
    if (h[0] === 'p') return project(h[1]);
    if (h[0] === 'j' && h[2] === 'edit') return jobForm(null, h[1]);
    if (h[0] === 'j' && h[2] === 'type' && h[3]) return editor('type', h[1], h[3]);
    if (h[0] === 's' && h[1]) return editor('site', h[1]);
    if (h[0] === 'j') return job(h[1]);
    projects();
  }
  window.addEventListener('hashchange', function () { main.innerHTML = ''; chrome(); route(); });
  chrome();
  route();
})();
</script>
</body>
</html>
