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
.pfiles{margin-top:16px;padding-top:14px;border-top:1px solid var(--line)}.pflist{list-style:none;margin:10px 0 0;padding:0;display:grid;gap:6px}.pflist li{display:flex;flex-wrap:wrap;align-items:center;gap:4px 12px}.pflist .muted{font-size:13px}.pflist button{padding:4px 10px;font-size:13px}#pf-msg{margin-top:6px;font-size:13px}
.stabs{display:flex;flex-wrap:wrap;gap:4px;margin:0 0 18px;border-bottom:1px solid var(--line)}.stabs a{padding:8px 14px;color:var(--muted);text-decoration:none;font-weight:600;border-bottom:2px solid transparent;margin-bottom:-1px}.stabs a:hover{color:var(--text)}.stabs a[aria-current]{color:var(--accent);border-bottom-color:var(--accent)}
.bfacts{margin:14px 0}.bfgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:4px 18px}.bfgrid .field{margin-bottom:12px}.bfstate{display:flex;flex-wrap:wrap;gap:6px;align-items:center;font-size:13px}.bfq{font-size:12px}.bffound input{border-color:var(--ok)}.linkish{border:0;background:none;padding:0;color:var(--accent);font-weight:600;font-size:13px;cursor:pointer}.bf-msg.ok{color:var(--ok)}
.usage{margin:8px 0;font-size:14px}.usage .warnx{color:var(--warn)}.pusage{margin:0 0 22px}.utable{width:100%;border-collapse:collapse;font-size:14px}.utable th,.utable td{text-align:left;padding:6px 8px;border-bottom:1px solid var(--line)}
.seo{margin:14px 0}.seorow{margin-top:8px;padding-top:8px;border-top:1px dashed var(--line)}.seorow .acts button{padding:4px 10px;font-size:13px}.seonote{margin:6px 0 0;font-size:13px;white-space:pre-wrap}#v-msg.ok{color:var(--ok)}#v-msg.err{color:var(--bad)}#vf .inl{display:flex}
.pout{margin:0 0 22px}.poutlist{display:grid;gap:6px;margin:10px 0}.poutlist .inl{display:flex}
.pinfo{margin:14px 0 22px}.pdl{display:grid;grid-template-columns:minmax(140px,220px) 1fr;gap:8px 16px;margin:14px 0 0}.pdl dt{font-weight:650}.pdl dd{margin:0}@media (max-width:640px){.pdl{grid-template-columns:1fr}.pdl dd{margin-bottom:6px}}#pf .inl{display:flex}
.card{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:16px}
.list{display:grid;gap:10px;margin-top:14px}.list a.item{display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--panel);border:1px solid var(--line);border-radius:12px;color:var(--text);text-decoration:none}
.list a.item:hover{border-color:var(--accent)}.chip{display:inline-block;padding:2px 9px;border-radius:999px;background:var(--bg);border:1px solid var(--line);font-size:12px;color:var(--muted);white-space:nowrap}
.chip.ok{background:var(--okbg);color:var(--ok);border-color:transparent}.chip.warn{background:var(--warnbg);color:var(--warn);border-color:transparent}.chip.bad{background:var(--badbg);color:var(--bad);border-color:transparent}.chip.auto{border-style:dashed}
form .field{display:grid;gap:6px;margin-bottom:16px}form label.lbl{font-weight:600}
table{border-collapse:collapse;width:100%}th,td{text-align:left;padding:8px 10px;border-bottom:1px solid var(--line);vertical-align:top;font-size:14px}th{font-size:12px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.types td{padding:10px}.wn{display:block;margin-top:4px;padding:6px 8px;border-radius:8px;background:var(--warnbg);color:var(--warn);font-size:12px;max-width:300px}
.axis label.blk{color:var(--warn)}.axis label.off{opacity:.45;text-decoration:line-through;cursor:not-allowed}.axis label.blk::after{content:"⚠";font-size:12px}
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
/* Справка (user guide) */
.docs{display:grid;grid-template-columns:240px minmax(0,1fr);gap:40px;align-items:start}
.dtoc{position:sticky;top:16px;max-height:calc(100vh - 32px);overflow:auto;font-size:14px}
.dtoc summary{font-weight:650;cursor:pointer;padding:6px 0;list-style:none}.dtoc summary::-webkit-details-marker{display:none}
.dtoc ol{margin:6px 0 0;padding:0;list-style:none;counter-reset:d;display:grid;gap:2px}
.dtoc li{counter-increment:d}.dtoc a{display:block;padding:6px 10px;border-radius:8px;color:var(--muted);text-decoration:none;line-height:1.35}
.dtoc a::before{content:counter(d) ". ";font-variant-numeric:tabular-nums;opacity:.7}
.dtoc a:hover{background:var(--accent-2);color:var(--text)}.dtoc a[aria-current]{background:var(--accent-2);color:var(--accent);font-weight:600}
.dbody{max-width:780px;font-size:16px;line-height:1.65;min-width:0}
.dkicker{margin:0;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);font-weight:600}
.dh1{margin:6px 0 18px;font-size:30px;line-height:1.15;letter-spacing:-.01em}
.dbody section{padding:18px 0 22px;border-top:1px solid var(--line);scroll-margin-top:16px}
.dbody section:first-of-type{border-top:0;padding-top:0}
.dbody h2{font-size:22px;margin:0 0 10px}.dbody h3{font-size:17px;margin:20px 0 8px}
.dbody p,.dbody ul,.dbody ol{margin:0 0 12px}.dbody li{margin:4px 0}
.dbody code{font:13px/1.4 ui-monospace,SFMono-Regular,Consolas,monospace;background:var(--bg);border:1px solid var(--line);border-radius:6px;padding:1px 6px;overflow-wrap:anywhere}
.dbody .steps{padding-left:22px}.dbody .steps li{margin:8px 0}
.dtable{width:100%;border-collapse:collapse;margin:8px 0 14px;font-size:15px}
.dtable th,.dtable td{border-top:1px solid var(--line);padding:10px 12px;vertical-align:top;text-align:left}
.dtable th{width:30%;font-weight:600}.dtable tr:first-child th,.dtable tr:first-child td{border-top:0}
.dnote,.dtip{margin:14px 0;padding:12px 14px;border-radius:10px;font-size:15px}
.dnote{background:var(--accent-2);border-left:3px solid var(--accent)}.dtip{background:var(--okbg);border-left:3px solid var(--ok)}
.dmuted{color:var(--muted);font-size:14px}
.dbody details{border:1px solid var(--line);border-radius:10px;padding:0 14px;margin:8px 0;background:var(--panel)}
.dbody details summary{cursor:pointer;padding:12px 0;font-weight:600}.dbody details p{margin:0 0 12px}
@media (max-width:860px){.docs{grid-template-columns:1fr;gap:16px}.dtoc{position:static;max-height:none;border:1px solid var(--line);border-radius:10px;padding:4px 12px;background:var(--panel)}
.dtoc summary::after{content:" ▾";color:var(--muted)}.dh1{font-size:24px}.dtable,.dtable tbody,.dtable tr,.dtable th,.dtable td{display:block;width:auto}
.dtable th{padding:10px 0 2px;border-top:1px solid var(--line)}.dtable td{border-top:0;padding:0 0 10px}}
@media (max-width:640px){main{padding:16px}header.top{padding:12px 16px}.types th:nth-child(3){display:none}}
</style>
</head>
<body>
<header class="top"><h1 id="apptitle">WebNets</h1><nav id="topnav"></nav><span class="sp"></span>
<div class="prefs"><select id="ui-lang"><option value="en">EN</option><option value="ru">RU</option></select><select id="ui-theme"></select></div></header>
<main id="main" aria-live="polite"></main>
<template id="gen-docs"><?php readfile(__DIR__ . '/docs.html'); ?></template>
<script>
(function () {
  var main = document.getElementById('main'), CAT = null, poll = null;
  var RU = {
    'User guide': 'Справка',
    'Projects': 'Проекты', 'Thresholds': 'Пороги', 'Settings': 'Настройки', 'Gallery': 'Галерея',
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
    'New job': 'Новое задание', 'Delete project': 'Удалить проект', 'Jobs': 'Задания',
    'Project information': 'Информация о проекте', 'Project name': 'Название проекта', 'Licence issued by': 'Кем выдана лицензия',
    'Who issued the brand\'s licence, as it reads in the footer line «… licence no. …, issued by …», e.g. «Curaçao Gaming Control Board». Empty: the market\'s regulator.': 'Кто выдал лицензию бренда — так, как читается в строке подвала «… лицензия № …, выдана …», например «Curaçao Gaming Control Board». Пусто — регулятор рынка.',
    'The licence number shown on every site of the project (brand facts can give a brand its own). Empty: the brand\'s.': 'Номер лицензии на всех сайтах проекта (в фактах бренда можно задать бренду свой). Пусто — номер бренда.',
    'The short label in trust badges and app info, e.g. «Лицензия Кюрасао». Empty: «Licence issued by» above (or the number; without any project licence, the market\'s «Лицензия РК»).': 'Короткая подпись в значках доверия и данных приложения, например «Лицензия Кюрасао». Пусто — «Кем выдана лицензия» выше (или номер; а если лицензия проекта не задана — значок рынка «Лицензия РК»).',
    'The licence changed. These jobs\u2019 sites still show the previous one:': 'Лицензия изменилась. На сайтах этих заданий пока прежняя:',
    'Update the data': 'Обновить данные', '{0} requests': 'запросов: {0}', '{0} tokens in, {1} out': 'токенов: {0} на входе, {1} на выходе',
    '{0} requests without a price ({1}):': 'без цены запросов: {0} ({1}) —', 'set the price': 'задать цену', 'site text': 'тексты сайтов', 'brand facts': 'факты бренда', 'connection test': 'проверка подключения',
    'Vertex AI cost': 'Расходы Vertex AI', 'Job': 'Задание', 'Requests': 'Запросы', 'Tokens': 'Токены', 'Cost': 'Стоимость', 'Project (no job)': 'Проект (без задания)', 'Deleted job {0}': 'Удалённое задание {0}',
    'Prices': 'Цены', 'Input, $ / 1M': 'Вход, $ за 1 млн', 'Output, $ / 1M': 'Выход, $ за 1 млн', 'used': 'используется', 'Save prices': 'Сохранить цены',
    'USD per 1 million tokens, from the Vertex AI price list of your Google Cloud account. A request\u2019s cost is worked out when it is made; requests made before a price was set get it when you save.': 'Доллары за 1 млн токенов — по прайсу Vertex AI вашего аккаунта Google Cloud. Стоимость запроса считается в момент запроса; запросы, сделанные до того, как цена задана, получат её при сохранении.', 'Updating the data: {0} sites left': 'Обновляю данные: осталось сайтов — {0}',
    'Pages are written again and new preview pictures taken, a few sites at a time; each card comes back when its site is done. Design and text stay. You can leave this page.': 'Страницы записываются заново и снимаются новые превью, по несколько сайтов; карточка возвращается, когда её сайт готов. Дизайн и тексты не меняются. Страницу можно закрыть.', 'Update the data ({0})': 'Обновить данные ({0})', 'Rebuild fully': 'Пересобрать полностью', 'Leave them as they are': 'Оставить как есть',
    '«Update the data»: the pages are written again with the new licence and new preview pictures are taken, about 2 seconds per site; design and text stay. «Rebuild fully» also runs the difference checks, as «Rebuild all» on a job page.': '«Обновить данные»: страницы записываются заново с новой лицензией и снимаются новые превью — около 2 секунд на сайт; дизайн и тексты не меняются. «Пересобрать полностью» — ещё и с проверками различий, как «Пересобрать всё» на странице задания.',
    'Pages written again with the current brand data, licence and AI text, and new preview pictures; design and text stay, no difference checks. About 2 seconds per site.': 'Страницы записываются заново с текущими данными бренда, лицензией и текстом ИИ, снимаются новые превью; дизайн и тексты не меняются, без проверок различий. Около 2 секунд на сайт.',
    'Save and update the sites': 'Сохранить и обновить сайты', 'refresh': 'ждёт обновления', 'refreshing': 'обновляется',
    'A rebuild keeps each site\u2019s design and text; jobs build one at a time. You can also rebuild a job later with «Rebuild all» on its page.': 'Пересборка сохраняет дизайн и тексты сайтов; задания собираются по очереди. Задание можно пересобрать и позже кнопкой «Пересобрать всё» на его странице.', 'from the project': 'из проекта', 'Files': 'Файлы', 'Add files': 'Добавить файлы', 'Remove': 'Удалить', 'Remove this file from the project?': 'Удалить этот файл из проекта?',
    'Everything here, the files included, goes to the AI: it writes the text of the sites once the SEO approves them, and it is in every AI brief. Changes apply to the next text run and brief download; no rebuild is needed.':
      'Всё, что здесь есть, включая файлы, получает ИИ: по этому он пишет тексты сайтов после одобрения SEO, и это же попадает в каждый ИИ-бриф. Изменения действуют со следующего написания текстов и скачивания брифов; пересобирать сайты не нужно.',
    'Nothing filled in yet: the AI uses only the site and market information.': 'Пока ничего не заполнено: ИИ опирается только на данные сайта и рынка.',
    'Brand overviews, bonus tables, briefs: .txt, .md, .csv, .docx or .odt, up to 3 MB each, at most 10. The AI reads them with the fields above. An old .doc: save it as .docx first.':
      'Обзоры бренда, таблицы бонусов, брифы: .txt, .md, .csv, .docx или .odt, до 3 МБ каждый, не больше 10. ИИ читает их вместе с полями выше. Старый .doc сначала сохраните как .docx.',
    'Vertex AI is not connected. Add an API key or a service account in Settings → Vertex AI.': 'Vertex AI не подключён. Добавьте API-ключ или сервисный аккаунт в «Настройки» → «Vertex AI».',
    'No approved, built site is waiting for text. Approve sites first.': 'Нет одобренных собранных сайтов без текста. Сначала одобрите сайты.',
    'The site has no build yet. Build it first.': 'Сайт ещё не собран. Сначала соберите его.',
    'The service account JSON is not valid: it needs client_email and private_key (download a key for the service account in Google Cloud → IAM → Service accounts → Keys).': 'JSON сервисного аккаунта неверный: нужны client_email и private_key (скачайте ключ в Google Cloud → IAM → Сервисные аккаунты → Ключи).',
    'SEO review and text': 'SEO-проверка и тексты', 'Brand facts': 'Факты бренда', 'Brand facts confirmed': 'Факты бренда подтверждены',
    'The sites still show example brand data.': 'На сайтах ещё пример данных бренда.', ' and {0} more': ' и ещё {0}',
    'Confirm the real facts before approving, so no made-up licence number or date goes live.': 'Подтвердите настоящие факты до одобрения, чтобы на живой сайт не попал выдуманный номер лицензии или дата.',
    'A new brand starts with example data so the templates look real: licence number, start year, payout times, app details. Enter the real facts here; the sites are rebuilt with them. A field left empty keeps the example data (marked «example»); «Hide» removes the fact from the pages where they can do without it.':
      'Новый бренд получает пример данных, чтобы шаблоны выглядели настоящими: номер лицензии, год запуска, сроки выплат, данные приложения. Впишите здесь настоящие факты — сайты пересоберутся с ними. Пустое поле оставляет пример (метка «пример»); «Скрыть» убирает факт со страниц, где без него можно обойтись.',
    'Fill from the project': 'Заполнить из проекта', '«Fill from the project»: Vertex AI reads the project information and documents and fills in only what they state, with the quoted words. Check the values, then save.':
      '«Заполнить из проекта»: Vertex AI читает информацию и документы проекта и заполняет только то, что там написано, с цитатой. Проверьте значения и сохраните.',
    'hidden': 'скрыто', 'confirmed': 'подтверждено', 'example': 'пример', 'not shown': 'не показывается', 'keep the example': 'оставить пример', 'Hide': 'Скрыть',
    'Save and rebuild the sites': 'Сохранить и пересобрать сайты', 'This job has no brands of its own.': 'В задании нет своих брендов.', 'Reading the project…': 'Читаю проект…',
    'From the project: «{0}»': 'Из проекта: «{0}»', '{0} facts found in the project. Check them and save.': 'Найдено в проекте фактов: {0}. Проверьте и сохраните.',
    'The project information and documents do not state any of these facts. Add them there, or type them here.': 'В информации и документах проекта этих фактов нет. Добавьте их туда или впишите здесь.',
    'The project has no information or documents to read the facts from. Fill in «Project information» or upload a document first.': 'В проекте нет информации или документов, из которых можно взять факты. Сначала заполните «Информацию о проекте» или загрузите документ.',
    'Licence number': 'Номер лицензии', 'Operating since (year)': 'Работает с (год)', 'Withdrawal time, minutes: {0}': 'Срок вывода, минут: {0}', 'Tagline ({0})': 'Слоган ({0})',
    'App developer': 'Разработчик приложения', 'App version': 'Версия приложения', 'App updated (YYYY-MM-DD)': 'Обновлено (ГГГГ-ММ-ДД)', 'iPhone app size, MB': 'Размер для iPhone, МБ',
    'Android app size, MB': 'Размер для Android, МБ', 'Minimum iOS': 'Минимальная iOS', 'Minimum Android': 'Минимальный Android', 'App languages ({0}, comma separated)': 'Языки приложения ({0}, через запятую)', 'Approve all ({0})': 'Одобрить все ({0})', 'Withdraw approvals': 'Снять одобрения', 'Write the text ({0})': 'Написать тексты ({0})',
    'Approve sites first: the AI writes the text of approved sites only.': 'Сначала одобрите сайты: ИИ пишет тексты только для одобренных.', 'The AI is writing now.': 'ИИ сейчас пишет тексты.',
    'All {0} approved sites already have AI text. Write it again for all of them?': 'У всех одобренных сайтов ({0}) уже есть текст ИИ. Написать его заново для всех?',
    '{0} of the {1} approved sites already have AI text; it will be written again for them too. Go on?': 'У {0} из {1} одобренных сайтов уже есть текст ИИ — для них он будет написан заново. Продолжить?',
    '1. Look at each template and approve it, or mark it «Needs changes» with a note. 2. «Write the text»: Vertex AI rewrites the approved sites’ text from the project information and documents, then the sites are updated with it (the design stays).':
      '1. Посмотрите каждый шаблон и одобрите его или отметьте «Нужны правки» с комментарием. 2. «Написать тексты»: Vertex AI переписывает тексты одобренных сайтов по информации и документам проекта, затем сайты обновляются с ними (дизайн не меняется).',
    'Vertex AI connection': 'Подключение Vertex AI', 'Approved: {0} of {1}': 'Одобрено: {0} из {1}', 'Needs changes: {0}': 'Нужны правки: {0}', 'With AI text: {0}': 'С текстом ИИ: {0}', 'Text failed: {0}': 'Ошибка текста: {0}',
    'Writing the text: {0} sites left': 'ИИ пишет тексты: осталось сайтов — {0}', 'About half a minute per page. Then each site is updated with its new text (pages written again, new preview; the design stays); you can leave this page.': 'Около полуминуты на страницу. Затем каждый сайт обновляется с новым текстом (страницы записываются заново, снимается новое превью; дизайн не меняется); страницу можно закрыть.',
    'Withdraw the SEO approval of every site in this job?': 'Снять SEO-одобрение со всех сайтов задания?', 'What should change in this template? (the note stays on the card)': 'Что поправить в этом шаблоне? (комментарий останется на карточке)',
    'Put the template’s text back on this site? The AI text is removed and the site is rebuilt.': 'Вернуть сайту текст шаблона? Текст ИИ удалится, сайт пересоберётся.',
    'Approved by SEO': 'Одобрено SEO', 'Needs changes': 'Нужны правки', 'Not reviewed': 'Не проверен', 'Text queued': 'Текст в очереди', 'AI is writing…': 'ИИ пишет…', 'Text failed': 'Ошибка текста', 'AI text': 'Текст ИИ',
    '{0} texts rewritten, {1} below sections · {2}': 'Переписано текстов: {0}, под секциями: {1} · {2}', 'Approve': 'Одобрить', 'Withdraw approval': 'Снять одобрение', 'Write again': 'Написать заново', 'Write the text': 'Написать текст', 'Template text back': 'Вернуть текст шаблона',
    'The model that writes the sites’ text after the SEO review. Connect with an API key (Vertex Express: Gemini models, small quotas) or a service account key (your Google Cloud project’s quotas; also Claude models). Keys are stored on the server and never shown again.':
      'Модель, которая пишет тексты сайтов после SEO-проверки. Подключение по API-ключу (Vertex Express: модели Gemini, небольшие квоты) или по ключу сервисного аккаунта (квоты вашего проекта Google Cloud; также модели Claude). Ключи хранятся на сервере и больше не показываются.',
    'Connect with': 'Способ подключения', 'API key (Vertex Express)': 'API-ключ (Vertex Express)', 'Service account (JSON key)': 'Сервисный аккаунт (JSON-ключ)', 'API key': 'API-ключ',
    'Saved (ends in {0}). Leave empty to keep it.': 'Сохранён (заканчивается на {0}). Оставьте пустым, чтобы не менять.', 'Google Cloud console → Vertex AI → API keys (Express mode).': 'Консоль Google Cloud → Vertex AI → API-ключи (режим Express).',
    'Remove the saved key': 'Удалить сохранённый ключ', 'Service account key (JSON)': 'Ключ сервисного аккаунта (JSON)', 'Saved: {0}. Leave empty to keep it.': 'Сохранён: {0}. Оставьте пустым, чтобы не менять.',
    'IAM → Service accounts → Keys → Add key → JSON. The account needs the «Vertex AI User» role.': 'IAM → Сервисные аккаунты → Ключи → Добавить ключ → JSON. Аккаунту нужна роль «Vertex AI User».',
    'Choose the .json file': 'Выбрать файл .json', 'Project ID': 'ID проекта', 'Taken from the key when empty.': 'Если пусто — берётся из ключа.', 'Region': 'Регион', 'e.g. us-central1, europe-west4, or global.': 'например: us-central1, europe-west4 или global.',
    'Region for Claude models': 'Регион для моделей Claude', 'Only if Claude runs in another region (e.g. us-east5). Empty = the region above.': 'Только если Claude работает в другом регионе (например, us-east5). Пусто — регион выше.',
    'Model': 'Модель', 'A Gemini model id, or a Claude model id (service account only). Pick one or type another.': 'ID модели Gemini или Claude (Claude — только через сервисный аккаунт). Выберите из списка или впишите свою.',
    'Test the connection': 'Проверить подключение', 'Testing…': 'Проверка…', 'Connected: {0} answered in {1} ms.': 'Подключено: {0} ответила за {1} мс.', 'Documents': 'Документы', 'Upload files': 'Загрузить файлы',
    '.txt, .md, .csv, .docx or .odt, up to 3 MB each, at most 10. Their text goes into the briefs as source material; for an old .doc, save it as .docx first.':
      '.txt, .md, .csv, .docx или .odt, до 3 МБ каждый, не больше 10. Их текст попадает в брифы как исходный материал; старый .doc сначала сохраните как .docx.',
    '{0} characters': 'знаков: {0}', 'Delete this document from the project?': 'Удалить этот документ из проекта?', 'Uploading…': 'Загрузка…',
    '{0}: the file is over 3 MB.': '{0}: файл больше 3 МБ.', 'Delete': 'Удалить',
    'old Word (.doc) and .rtf files cannot be read. Save it as .docx (File → Save as) and upload that.': 'старый Word (.doc) и .rtf прочитать нельзя. Сохраните файл как .docx (Файл → Сохранить как) и загрузите его.',
    'only .txt, .md, .csv, .docx and .odt files can be uploaded.': 'можно загрузить только файлы .txt, .md, .csv, .docx и .odt.',
    'the file is over 3 MB.': 'файл больше 3 МБ.', 'no text found in the file.': 'в файле не найден текст.',
    'this is not a readable .docx file.': 'это не читаемый файл .docx.', 'this is not a readable .odt file.': 'это не читаемый файл .odt.',
    'A project holds at most 10 files. Delete one first.': 'В проекте может быть не больше 10 файлов. Сначала удалите один.', 'The file did not arrive. Try again.': 'Файл не дошёл. Попробуйте ещё раз.',
    'Used by the AI briefs of every site in this project to write relevant text in the sections and, if you choose, below them. Changes apply to the next brief download; no rebuild is needed.':
      'ИИ-брифы всех сайтов проекта используют эту информацию, чтобы писать подходящий текст в секциях и, если вы выберете, под ними. Изменения попадают в следующее скачивание брифов, пересобирать сайты не нужно.',
    'Nothing filled in yet: the briefs use only the site and market information.': 'Пока ничего не заполнено: брифы опираются только на данные сайта и рынка.',
    'Write in any language: the AI writes each site in its own language. Leave a field empty to skip it.': 'Пишите на любом языке: ИИ пишет каждый сайт на его языке. Пустые поля пропускаются.',
    'About the project': 'О проекте', 'What the project is and what its sites are for: the brand or offer, what makes it different, the goal of the sites.': 'Что это за проект и зачем его сайты: бренд или предложение, чем он отличается, цель сайтов.',
    'Facts and messages to use': 'Факты и тезисы', 'Facts the text may state and points it should make (licence, payout times, app features, support hours…), one per line. No length limit.': 'Факты, которые можно упоминать, и мысли, которые текст должен донести (лицензия, сроки выплат, функции приложения, часы поддержки…), по одному в строке. Без ограничения длины.',
    'Audience': 'Аудитория', 'Who the sites are for and what they want to know.': 'Для кого сайты и что эти люди хотят узнать.',
    'Tone of voice': 'Тон', 'e.g. friendly and plain, expert and calm, no hype.': 'например: дружелюбно и просто, экспертно и спокойно, без хайпа.',
    'Topics and search phrases': 'Темы и поисковые фразы', 'One per line: what the text should cover and the phrases people search for.': 'По одной в строке: о чём писать и что люди ищут.',
    'Avoid': 'Избегать', 'One per line: words, claims and topics the text must not use.': 'По одному в строке: слова, обещания и темы, которых в тексте быть не должно.',
    'Text below sections': 'Текст под секциями', 'Whether the AI adds a short text below sections, written from this information (useful for search).': 'Добавляет ли ИИ короткий текст под секциями на основе этой информации (полезно для поиска).',
    'No: text only inside the sections': 'Нет: текст только внутри секций', 'Where it helps: a short text below some sections': 'Где полезно: короткий текст под некоторыми секциями', 'Below every main section': 'Под каждой основной секцией',
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
    'Server error ({0}): {1}': 'Ошибка сервера ({0}): {1}', 'The server is not set up completely:': 'Сервер настроен не полностью:',
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
    'Your brands do not offer this product': 'Ваши бренды не предлагают этот продукт',
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
    document.getElementById('topnav').innerHTML = '<a href="#/"' + (['settings', 'markets', 'types', 'docs'].indexOf(h) < 0 ? ' aria-current="page"' : '') + '>' + L('Projects') + '</a><a href="#/types"' + (h === 'types' ? ' aria-current="page"' : '') + '>' + L('Site types') + '</a><a href="#/markets"' + (h === 'markets' ? ' aria-current="page"' : '') + '>' + L('Markets') + '</a><a href="#/settings"' + (h === 'settings' ? ' aria-current="page"' : '') + '>' + L('Settings') + '</a><a href="/">' + L('Gallery') + '</a><a href="/settings/">' + L('AI brief') + '</a><a href="#/docs"' + (h === 'docs' ? ' aria-current="page"' : '') + '>' + L('User guide') + '</a>';
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
      .then(function (r) {
        return r.text().then(function (t) {
          var j;
          try { j = JSON.parse(t); } catch (e) { // not JSON: show what the server said (a PHP error page, a proxy error), not "not valid JSON"
            var msg = t.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 400);
            throw new Error(L('Server error ({0}): {1}', r.status, msg || r.statusText));
          }
          if (!r.ok) throw new Error(j.error || r.statusText);
          return j;
        });
      });
  }
  // the server's setup (PHP extensions, writable folders, tools): problems shown on the Projects page
  function healthNote() {
    return fetch('/generator/api/health').then(function (r) { return r.json(); }).then(function (h) {
      return h.ok ? '' : '<div class="err" role="alert" style="margin-bottom:16px"><b>' + L('The server is not set up completely:') + '</b><ul style="margin:6px 0 0;padding-left:20px">'
        + h.problems.map(function (p) { return '<li>' + esc(p) + '</li>'; }).join('') + '</ul></div>';
    }).catch(function () { return ''; });
  }
  var AVAIL = {};
  function catalog() { return CAT ? Promise.resolve(CAT) : api('catalog').then(function (c) { CAT = c; return c; }); }
  function fail(e) { main.innerHTML = '<p class="err">' + esc(L(e.message || String(e))) + '</p>'; }
  function when(s) { return esc(String(s).replace('T', ' ').slice(0, 16)); }

  /* ---------- thresholds */
  /* Settings: one page, a tab per area */
  function settingsTabs(on) {
    return '<h2>' + L('Settings') + '</h2><nav class="stabs" aria-label="' + L('Settings') + '">'
      + [['thresholds', '#/settings', L('Thresholds')], ['vertex', '#/settings/vertex', 'Vertex AI']].map(function (t) {
        return '<a href="' + t[1] + '"' + (t[0] === on ? ' aria-current="page"' : '') + '>' + t[2] + '</a>';
      }).join('') + '</nav>';
  }
  function settings() {
    api('settings').then(function (d) {
      var st = d.settings, df = d.defaults;
      main.innerHTML = settingsTabs('thresholds') + '<h3>' + L('Difference thresholds') + '</h3><p class="muted">' + L('How different every generated site must be from the sites it is compared with (same type). The generator checks each site after it is built; a site under a threshold gets a new plan (new seed, another style, the next hero layout, then a free section order and free layouts) and is rebuilt, up to the rebuilds limit. It keeps the best version; a site that still misses a threshold is flagged on the job page.') + '</p>'
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
    Promise.all([api('projects'), healthNote()]).then(function (res) {
      var list = res[0];
      main.innerHTML = res[1] + '<div class="row"><div><h2>' + L('Projects') + '</h2><p class="muted">' + L('A project holds the jobs that generate its sites. Sites are compared inside the project and against every other job.') + '</p></div></div>'
        + '<form class="card row" id="np"><input type="text" id="pname" placeholder="' + L('New project name') + '" aria-label="' + L('New project name') + '" required style="flex:1;min-width:220px"><button class="primary">' + L('Create project') + '</button></form>'
        + '<div class="list">' + (list.length ? list.map(function (p) {
          return '<a class="item" href="#/p/' + p.id + '"><strong>' + esc(p.name) + '</strong><span class="sp"></span><span class="chip">' + L('{0} jobs', p.jobs) + '</span><span class="chip">' + L('{0} sites', p.sites) + '</span>' + (+p.ai_calls ? '<span class="chip" title="Vertex AI">' + usd(p.ai_cost) + '</span>' : '') + '<span class="muted">' + when(p.created) + '</span></a>';
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
        + '<div id="pinfo"></div><div id="pout"></div>' + projectUsage(d) + '<h3>' + L('Jobs') + '</h3>'
        + '<div class="list">' + (d.jobs.length ? d.jobs.map(function (j) {
          return '<a class="item" href="#/j/' + j.id + '"><strong>' + esc(j.name) + '</strong><span class="chip">' + esc(j.market.toUpperCase()) + '</span>' + (+j.compare ? '' : '<span class="chip auto" title="' + L('Other jobs are not compared with this one') + '">' + L('left out of comparisons') + '</span>') + '<span class="sp"></span>'
            + '<span class="chip ' + (j.status === 'done' ? 'ok' : j.status === 'done-with-errors' || j.status === 'stopped' ? 'bad' : 'warn') + '">' + esc(L(j.status)) + '</span><span class="chip">' + L('{0} / {1} sites', j.built, j.sites) + '</span><span class="muted">' + when(j.created) + '</span></a>';
        }).join('') : '<p class="muted">' + L('No jobs yet. Create one to generate sites.') + '</p>') + '</div>';
      document.getElementById('delp').onclick = function () {
        if (confirm(L('Delete this project, its jobs and every generated site?'))) api('projects/' + id, null, 'DELETE').then(function () { location.hash = '#/'; }).catch(fail);
      };
      d.project.files = d.files || [];
      projectInfo(id, d.project, false);
      outdatedPanel(id, d.jobs);
    }).catch(fail);
  }

  /* Project information: what the AI briefs of the project's sites use to write relevant text (added to every brief when it is downloaded) */
  var PINFO = [
    ['about', 'About the project', 'What the project is and what its sites are for: the brand or offer, what makes it different, the goal of the sites.', 4],
    ['audience', 'Audience', 'Who the sites are for and what they want to know.', 2],
    ['tone', 'Tone of voice', 'e.g. friendly and plain, expert and calm, no hype.', 1],
    ['facts', 'Facts and messages to use', 'Facts the text may state and points it should make (licence, payout times, app features, support hours…), one per line. No length limit.', 8],
    ['keywords', 'Topics and search phrases', 'One per line: what the text should cover and the phrases people search for.', 3],
    ['avoid', 'Avoid', 'One per line: words, claims and topics the text must not use.', 2],
    ['licence_regulator', 'Licence issued by', 'Who issued the brand\'s licence, as it reads in the footer line «… licence no. …, issued by …», e.g. «Curaçao Gaming Control Board». Empty: the market\'s regulator.', 1],
    ['licence_no', 'Licence number', 'The licence number shown on every site of the project (brand facts can give a brand its own). Empty: the brand\'s.', 1],
    ['licence_short', 'Licence badge', 'The short label in trust badges and app info, e.g. «Лицензия Кюрасао». Empty: «Licence issued by» above (or the number; without any project licence, the market\'s «Лицензия РК»).', 1]
  ];
  var PBELOW = [['none', 'No: text only inside the sections'], ['useful', 'Where it helps: a short text below some sections'], ['every', 'Below every main section']];
  function projectUsage(d) {
    var u = d.usage;
    if (!u || !u.calls) return '';
    var names = {};
    d.jobs.forEach(function (j) { names[j.id] = j.name; });
    return '<section class="card pusage"><h3 style="margin:0 0 6px">' + L('Vertex AI cost') + '</h3>' + usageLine(u, true)
      + '<table class="utable"><thead><tr><th>' + L('Job') + '</th><th>' + L('Requests') + '</th><th>' + L('Tokens') + '</th><th>' + L('Cost') + '</th></tr></thead><tbody>'
      + u.by_job.map(function (r) {
        var name = r.k === null ? L('Project (no job)') : names[r.k] ? '<a href="#/j/' + r.k + '">' + esc(names[r.k]) + '</a>' : L('Deleted job {0}', r.k);
        return '<tr><td>' + name + '</td><td>' + num(r.calls) + '</td><td>' + num(r.tokens) + '</td><td>' + usd(r.cost) + '</td></tr>';
      }).join('') + '</tbody></table></section>';
  }

  /* Jobs whose sites still show the project's previous licence: the user picks which to rebuild */
  function outdatedPanel(id, jobs) {
    var box = document.getElementById('pout'), old = jobs.filter(function (j) { return +j.outdated; });
    if (!box) return;
    if (!old.length) { box.innerHTML = ''; return; }
    box.innerHTML = '<section class="card note pout"><b>' + L('The licence changed. These jobs\u2019 sites still show the previous one:') + '</b>'
      + '<div class="poutlist">' + old.map(function (j) {
        return '<label class="inl"><input type="checkbox" value="' + j.id + '"> ' + esc(j.name) + ' <span class="muted">· ' + esc(j.market.toUpperCase()) + ' · ' + L('{0} sites', j.sites) + '</span></label>';
      }).join('') + '</div>'
      + '<div class="row"><button type="button" class="primary" id="pout-go" disabled>' + L('Update the data') + '</button><button type="button" id="pout-full" disabled>' + L('Rebuild fully') + '</button><button type="button" id="pout-skip">' + L('Leave them as they are') + '</button><span class="err" id="pout-err"></span></div>'
      + '<p class="muted" style="margin:6px 0 0;font-size:13px">' + L('«Update the data»: the pages are written again with the new licence and new preview pictures are taken, about 2 seconds per site; design and text stay. «Rebuild fully» also runs the difference checks, as «Rebuild all» on a job page.') + '</p></section>';
    var go = document.getElementById('pout-go'), full = document.getElementById('pout-full'), boxes = [].slice.call(box.querySelectorAll('input[type=checkbox]')), err = document.getElementById('pout-err');
    var picked = function () { return boxes.filter(function (c) { return c.checked; }).map(function (c) { return +c.value; }); };
    boxes.forEach(function (c) { c.onchange = function () { var n = picked().length; go.disabled = full.disabled = !n; go.textContent = n ? L('Update the data ({0})', n) : L('Update the data'); }; });
    var send = function (kind, btn) { go.disabled = full.disabled = true; var body = {}; body[kind] = picked(); api('projects/' + id + '/outdated', body, 'POST').then(function () { project(id); }).catch(function (x) { err.textContent = L(x.message || String(x)); go.disabled = full.disabled = false; }); };
    go.onclick = function () { send('refresh'); };
    full.onclick = function () { send('rebuild'); };
    document.getElementById('pout-skip').onclick = function () { api('projects/' + id + '/outdated', { rebuild: [], dismiss: true }, 'POST').then(function () { project(id); }).catch(function (x) { err.textContent = L(x.message || String(x)); }); };
  }
  function fileLink(id, f) {
    return '<a href="/generator/api/projects/' + id + '/files/' + f.id + '" target="_blank" rel="noopener"><b>' + esc(f.name) + '</b></a> <span class="muted">' + L('{0} characters', f.chars.toLocaleString()) + ' · ' + Math.max(1, Math.round(f.size / 1024)) + ' KB</span>';
  }
  function projectInfo(id, p, editing) {
    var box = document.getElementById('pinfo'), info = p.info || {};
    if (!box) return;
    var filled = PINFO.filter(function (f) { return info[f[0]]; }), below = info.below || 'none', files = p.files || [];
    if (!editing) {
      box.innerHTML = '<section class="card pinfo"><div class="row"><h3 style="margin:0">' + L('Project information') + '</h3><span class="sp"></span><button type="button" id="pedit">' + L('Edit') + '</button></div>'
        + '<p class="muted" style="margin:8px 0 0;font-size:14px">' + L('Everything here, the files included, goes to the AI: it writes the text of the sites once the SEO approves them, and it is in every AI brief. Changes apply to the next text run and brief download; no rebuild is needed.') + '</p>'
        + (filled.length || files.length || below !== 'none'
          ? '<dl class="pdl">' + filled.map(function (f) { return '<dt>' + L(f[1]) + '</dt><dd>' + esc(info[f[0]]).replace(/\n/g, '<br>') + '</dd>'; }).join('')
            + (files.length ? '<dt>' + L('Files') + '</dt><dd><ul class="pflist">' + files.map(function (f) { return '<li>' + fileLink(id, f) + '</li>'; }).join('') + '</ul></dd>' : '')
            + '<dt>' + L('Text below sections') + '</dt><dd>' + L(PBELOW.filter(function (b) { return b[0] === below; })[0][1]) + '</dd></dl>'
          : '<p style="margin:10px 0 0">' + L('Nothing filled in yet: the AI uses only the site and market information.') + '</p>')
        + '</section>';
      document.getElementById('pedit').onclick = function () { projectInfo(id, p, true); };
      return;
    }
    box.innerHTML = '<form class="card mform pinfo" id="pf"><h3 style="margin-top:0">' + L('Project information') + '</h3>'
      + '<p class="muted" style="margin-top:0;font-size:14px">' + L('Write in any language: the AI writes each site in its own language. Leave a field empty to skip it.') + '</p>'
      + '<div class="field"><label class="lbl" for="pf-name">' + L('Project name') + '</label><input type="text" id="pf-name" required maxlength="120" value="' + esc(p.name) + '"></div>'
      + PINFO.map(function (f) {
        var max = f[0] === 'facts' ? '' : ' maxlength="3000"';
        return '<div class="field"><label class="lbl" for="pf-' + f[0] + '">' + L(f[1]) + '</label><span class="muted" style="font-size:13px">' + L(f[2]) + '</span>'
          + (f[3] > 1 ? '<textarea id="pf-' + f[0] + '" rows="' + f[3] + '"' + max + '>' + esc(info[f[0]] || '') + '</textarea>' : '<input type="text" id="pf-' + f[0] + '"' + max + ' value="' + esc(info[f[0]] || '') + '">') + '</div>';
      }).join('')
      + '<div class="field"><span class="lbl">' + L('Files') + '</span><span class="muted" style="font-size:13px">' + L('Brand overviews, bonus tables, briefs: .txt, .md, .csv, .docx or .odt, up to 3 MB each, at most 10. The AI reads them with the fields above. An old .doc: save it as .docx first.') + '</span>'
      + '<ul class="pflist" id="pf-files"></ul><div class="row"><label class="btn" for="pf-up">' + L('Add files') + '</label><input type="file" id="pf-up" multiple accept=".txt,.md,.csv,.tsv,.docx,.odt" hidden><span id="pf-msg" role="status"></span></div></div>'
      + '<div class="field"><span class="lbl">' + L('Text below sections') + '</span><span class="muted" style="font-size:13px">' + L('Whether the AI adds a short text below sections, written from this information (useful for search).') + '</span>'
      + PBELOW.map(function (b) { return '<label class="inl"><input type="radio" name="pf-below" value="' + b[0] + '"' + (below === b[0] ? ' checked' : '') + '> ' + L(b[1]) + '</label>'; }).join('') + '</div>'
      + '<div class="row"><button class="primary" type="submit">' + L('Save') + '</button><button type="button" id="pf-cancel">' + L('Cancel') + '</button><span class="err" id="pf-err"></span></div></form>';
    var list = document.getElementById('pf-files'), msg = document.getElementById('pf-msg');
    function drawFiles() { // files are saved as soon as they are added or removed; the typed fields stay as they are
      list.innerHTML = files.map(function (f) { return '<li>' + fileLink(id, f) + '<span class="sp"></span><button type="button" class="danger pf-del" data-id="' + f.id + '">' + L('Remove') + '</button></li>'; }).join('');
      [].forEach.call(list.querySelectorAll('.pf-del'), function (b) {
        b.onclick = function () {
          if (!confirm(L('Remove this file from the project?'))) return;
          api('projects/' + id + '/files/' + b.dataset.id, null, 'DELETE').then(refresh).catch(function (x) { msg.className = 'err'; msg.textContent = L(x.message || String(x)); });
        };
      });
    }
    function refresh() { return api('projects/' + id).then(function (d) { files = p.files = d.files || []; drawFiles(); }); }
    drawFiles();
    document.getElementById('pf-up').onchange = function () {
      var chosen = [].slice.call(this.files), errs = [];
      this.value = '';
      msg.className = 'muted';
      msg.textContent = L('Uploading…');
      chosen.reduce(function (chain, f) {
        return chain.then(function () {
          if (f.size > 3 * 1024 * 1024) { errs.push(L('{0}: the file is over 3 MB.', f.name)); return; }
          return new Promise(function (ok) {
            var r = new FileReader();
            r.onload = function () { ok(String(r.result).split(',')[1] || ''); };
            r.onerror = function () { ok(''); };
            r.readAsDataURL(f);
          }).then(function (data) {
            return api('projects/' + id + '/files', { name: f.name, data: data }).catch(function (e) {
              var m = e.message || String(e), at = m.indexOf(': ');
              errs.push(at > 0 && m.slice(0, at) === f.name ? f.name + ': ' + L(m.slice(at + 2)) : L(m)); // "<file>: message", the message translated
            });
          });
        });
      }, Promise.resolve()).then(refresh).then(function () {
        msg.className = errs.length ? 'err' : 'muted';
        msg.innerHTML = errs.length ? errs.map(esc).join('<br>') : '';
      });
    };
    document.getElementById('pf-cancel').onclick = function () { projectInfo(id, p, false); };
    document.getElementById('pf').onsubmit = function (e) {
      e.preventDefault();
      var next = { below: document.querySelector('input[name="pf-below"]:checked').value };
      PINFO.forEach(function (f) { next[f[0]] = document.getElementById('pf-' + f[0]).value.trim(); });
      api('projects/' + id, { name: document.getElementById('pf-name').value.trim(), info: next }, 'PUT').then(function () { project(id); })
        .catch(function (er) { document.getElementById('pf-err').textContent = L(er.message || String(er)); });
    };
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
        fitProducts();
        totals();
      }
      // Product options the job's brands do not all offer are switched off: a casino-only brand gets no "Casino + sport" site
      function fitProducts() {
        var bs = brandsValue(), offer = { casino: true, sports: true };
        bs.forEach(function (b) { if (b.products === 'casino') offer.sports = false; if (b.products === 'sports') offer.casino = false; });
        var needs = { both: ['casino', 'sports'], casino: ['casino'], sports: ['sports'], live: ['sports'] };
        var why = bs.length ? L('Your brands do not offer this product') : '';
        [].forEach.call(document.querySelectorAll('.opt[data-a=product]'), function (o) {
          var ok = !bs.length || (needs[o.value] || []).every(function (n) { return offer[n]; }), lab = o.closest('label');
          o.disabled = !ok;
          if (!ok) o.checked = false;
          lab.classList.toggle('off', !ok);
          lab.title = ok ? '' : why;
        });
        [].forEach.call(document.querySelectorAll('.ton'), function (cb) { // keep one product ticked per type
          var row = cb.closest('tr'), opts = row.querySelectorAll('.opt[data-a=product]');
          if (opts.length && !row.querySelector('.opt[data-a=product]:checked')) {
            var first = [].filter.call(opts, function (o) { return !o.disabled; })[0];
            if (first) first.checked = true;
          }
        });
      }
      document.getElementById('brows').addEventListener('change', fitProducts);
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
        fitProducts();
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
  var EDIT = true; // the job page always shows "Edit all" on each type and "Edit" on each site (hidden while building)
  var BRIEFS = { open: false, off: {} }; // job page: the AI briefs download panel, and the site types left out
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
    var tkey = s.status + '|' + (s.v || '') + '|' + (s.thumb || ''), bkey = JSON.stringify([s.status, s.mobile, s.warnings.length, s.blocks, s.pages, s.preset, s.brand, s.edited, EDIT, s.seo, s.content, s.ai]);
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
      + (s.status === 'built' ? seoCard(s) : '')
      + (s.status === 'built' ? '<div class="acts"><a class="btn primary" href="' + s.url + '" target="_blank" rel="noopener">' + L('Open site') + '</a>' + (s.brief ? '<a class="btn" href="' + s.brief + '" download title="' + L('Download the AI brief for the whole site (.txt)') + '">' + L('AI brief') + ' ↓</a>' : '') + (EDIT ? '<a class="btn" href="#/s/' + s.id + '">' + L('Edit') + '</a>' : '') + '</div>' : '');
  }

  /* SEO review and AI text (generator/content.php): the SEO approves templates, then Vertex AI writes the approved sites' text */
  function seoState(s) { return s.seo && s.seo.status; }
  function seoPanel(j, d) {
    var built = d.sites.filter(function (s) { return s.status === 'built'; });
    var ok = built.filter(function (s) { return seoState(s) === 'approved'; }), changes = built.filter(function (s) { return seoState(s) === 'changes'; });
    var busy = built.filter(function (s) { return s.content && (s.content.state === 'queued' || s.content.state === 'writing'); });
    var done = built.filter(function (s) { return s.ai; }), errs = built.filter(function (s) { return s.content && s.content.state === 'error'; });
    var todo = ok.filter(function (s) { return !(s.content && (s.content.state === 'queued' || s.content.state === 'writing')); }); // every approved site: new text, or written again
    var again = todo.filter(function (s) { return s.ai; }).length;
    var why = !ok.length ? L('Approve sites first: the AI writes the text of approved sites only.') : busy.length ? L('The AI is writing now.') : '';
    return '<section class="card seo" aria-labelledby="seoh"><div class="row"><h3 id="seoh" style="margin:0">' + L('SEO review and text') + '</h3><span class="sp"></span>'
      + (ok.length + changes.length < built.length ? '<button type="button" id="seoall">' + L('Approve all ({0})', built.length - ok.length - changes.length) + '</button>' : '')
      + (ok.length ? '<button type="button" id="seonone">' + L('Withdraw approvals') + '</button>' : '')
      + '<button type="button" class="primary" id="aiwrite" data-again="' + again + '" data-n="' + todo.length + '"' + (todo.length && !busy.length ? '' : ' disabled') + (why ? ' title="' + esc(why) + '"' : '') + '>' + L('Write the text ({0})', todo.length) + '</button></div>'
      + (why && !busy.length ? '<p class="muted" style="margin:6px 0 0;font-size:13px">' + why + '</p>' : '')
      + '<p class="muted" style="margin:8px 0 0;font-size:14px">' + L('1. Look at each template and approve it, or mark it «Needs changes» with a note. 2. «Write the text»: Vertex AI rewrites the approved sites’ text from the project information and documents, then the sites are updated with it (the design stays).') + ' <a href="#/settings/vertex">' + L('Vertex AI connection') + '</a> · <a href="#/p/' + j.project_id + '">' + L('Project information') + '</a></p>'
      + factsNote(j)
      + '<div class="chips" style="margin-top:10px"><span class="chip ok">' + L('Approved: {0} of {1}', ok.length, built.length) + '</span>' + (changes.length ? '<span class="chip warn">' + L('Needs changes: {0}', changes.length) + '</span>' : '')
      + '<span class="chip">' + L('With AI text: {0}', done.length) + '</span>' + (errs.length ? '<span class="chip bad">' + L('Text failed: {0}', errs.length) + '</span>' : '') + '</div>'
      + (busy.length ? '<div class="notice" role="status" style="margin-top:10px"><span class="spin" aria-hidden="true"></span><span><b>' + L('Writing the text: {0} sites left', busy.length) + '.</b> ' + L('About half a minute per page. Then each site is updated with its new text (pages written again, new preview; the design stays); you can leave this page.') + '</span></div>' : '')
      + '</section>';
  }
  function factLabel(f) { return L(f.label, f.param); }
  function factsNote(j) {
    var left = j.facts_left || {}, names = Object.keys(left);
    if (!names.length) return '<p style="margin:10px 0 0;font-size:14px"><span class="chip ok">' + L('Brand facts confirmed') + '</span> <a href="#/j/' + j.id + '/brands">' + L('Brand facts') + '</a></p>';
    return '<div class="note" style="margin-top:10px"><b>' + L('The sites still show example brand data.') + '</b> '
      + names.map(function (n) { return esc(n) + ': ' + left[n].slice(0, 6).map(function (f) { return esc(factLabel(f)); }).join(', ') + (left[n].length > 6 ? L(' and {0} more', left[n].length - 6) : ''); }).join('; ')
      + '. ' + L('Confirm the real facts before approving, so no made-up licence number or date goes live.') + ' <a class="btn" style="padding:4px 10px;font-size:13px" href="#/j/' + j.id + '/brands">' + L('Brand facts') + '</a></div>';
  }

  /* Brand facts: the real licence, start year, payout times, app details and tagline of the job's brands */
  function brandFacts(id) {
    Promise.all([api('jobs/' + id), api('jobs/' + id + '/brands')]).then(function (r) {
      var j = r[0].job, brands = r[1].brands;
      main.innerHTML = '<p><a href="#/j/' + id + '">← ' + esc(j.name) + '</a></p><h2>' + L('Brand facts') + '</h2>'
        + '<p class="muted" style="max-width:860px">' + L('A new brand starts with example data so the templates look real: licence number, start year, payout times, app details. Enter the real facts here; the sites are rebuilt with them. A field left empty keeps the example data (marked «example»); «Hide» removes the fact from the pages where they can do without it.') + '</p>'
        + (brands.length ? brands.map(function (b) {
          return '<form class="card mform bfacts" data-b="' + esc(b.id) + '"><div class="row"><h3 style="margin:0">' + esc(b.name) + '</h3><span class="sp"></span>'
            + '<button type="button" class="bf-fill">' + L('Fill from the project') + '</button></div>'
            + '<p class="muted bf-msg" style="margin:6px 0 12px;font-size:13px">' + L('«Fill from the project»: Vertex AI reads the project information and documents and fills in only what they state, with the quoted words. Check the values, then save.') + '</p>'
            + '<div class="bfgrid">' + b.fields.map(function (f) {
              var hidden = f.confirmed && f.value === '' && f.hide;
              return '<div class="field" data-k="' + f.key + '"><label class="lbl" for="bf-' + esc(b.id) + '-' + f.key + '">' + esc(factLabel(f)) + '</label>'
                + '<input type="text" id="bf-' + esc(b.id) + '-' + f.key + '" data-kind="' + f.kind + '" value="' + esc(f.value) + '" placeholder="' + esc(f.example) + '"' + (hidden ? ' disabled' : '') + '>'
                + '<span class="bfstate">' + (f.project ? '<span class="chip ok">' + L('from the project') + '</span>' : f.confirmed ? (hidden ? '<span class="chip">' + L('hidden') + '</span>' : '<span class="chip ok">' + L('confirmed') + '</span>') : f.example !== '' ? '<span class="chip warn">' + L('example') + '</span>' : '<span class="chip auto">' + L('not shown') + '</span>')
                + (f.example !== '' && !f.confirmed ? ' <button type="button" class="linkish bf-keep">' + L('keep the example') + '</button>' : '')
                + (f.hide ? ' <label class="inl"><input type="checkbox" class="bf-hide"' + (hidden ? ' checked' : '') + '> ' + L('Hide') + '</label>' : '') + '</span><small class="bfq muted"></small></div>';
            }).join('') + '</div>'
            + '<div class="row"><button class="primary" type="submit">' + L('Save and update the sites') + '</button><span class="bf-err err"></span></div></form>';
        }).join('') : '<p class="muted">' + L('This job has no brands of its own.') + '</p>');
      [].forEach.call(main.querySelectorAll('.bfacts'), function (form) {
        var bid = form.dataset.b, msg = form.querySelector('.bf-msg'), err = form.querySelector('.bf-err');
        [].forEach.call(form.querySelectorAll('.bf-hide'), function (c) {
          c.onchange = function () { var inp = c.closest('.field').querySelector('input[type=text]'); inp.disabled = c.checked; if (c.checked) inp.value = ''; };
        });
        [].forEach.call(form.querySelectorAll('.bf-keep'), function (k) {
          k.onclick = function () { var inp = k.closest('.field').querySelector('input[type=text]'); inp.value = inp.placeholder; inp.focus(); };
        });
        form.querySelector('.bf-fill').onclick = function () {
          var btn = this;
          btn.disabled = true;
          msg.className = 'muted bf-msg';
          msg.textContent = L('Reading the project…');
          api('jobs/' + id + '/brands/' + encodeURIComponent(bid) + '/fill', {}, 'POST').then(function (x) {
            btn.disabled = false;
            var keys = Object.keys(x.facts || {});
            keys.forEach(function (k) {
              var fld = form.querySelector('[data-k="' + k + '"]');
              if (!fld) return;
              var inp = fld.querySelector('input[type=text]');
              inp.value = x.facts[k].value;
              inp.disabled = false;
              fld.querySelector('.bfq').textContent = L('From the project: «{0}»', x.facts[k].quote);
              fld.classList.add('bffound');
            });
            msg.className = keys.length ? 'ok bf-msg' : 'muted bf-msg';
            msg.textContent = keys.length ? L('{0} facts found in the project. Check them and save.', keys.length) : L('The project information and documents do not state any of these facts. Add them there, or type them here.');
          }).catch(function (e) { btn.disabled = false; msg.className = 'err bf-msg'; msg.textContent = L(e.message || String(e)); });
        };
        form.onsubmit = function (e) {
          e.preventDefault();
          var facts = {};
          [].forEach.call(form.querySelectorAll('.field[data-k]'), function (fld) {
            var inp = fld.querySelector('input[type=text]'), hide = fld.querySelector('.bf-hide');
            if (hide && hide.checked) facts[fld.dataset.k] = '';
            else if (inp.value.trim() !== '') facts[fld.dataset.k] = inp.value.trim();
          });
          err.textContent = '';
          api('jobs/' + id + '/brands/' + encodeURIComponent(bid), { facts: facts }, 'PUT').then(function () { location.hash = '#/j/' + id; })
            .catch(function (x) { err.textContent = L(x.message || String(x)); });
        };
      });
    }).catch(fail);
  }

  function seoWire(id, d, jerr) {
    var all = document.getElementById('seoall'), none = document.getElementById('seonone'), w = document.getElementById('aiwrite');
    if (all) all.onclick = function () { all.disabled = true; api('jobs/' + id + '/seo', { status: 'approved' }, 'POST').then(function () { job(id); }).catch(jerr); };
    if (none) none.onclick = function () { if (confirm(L('Withdraw the SEO approval of every site in this job?'))) api('jobs/' + id + '/seo', { status: null }, 'POST').then(function () { job(id); }).catch(jerr); };
    if (w) w.onclick = function () {
      var again = +w.dataset.again, n = +w.dataset.n;
      if (again && !confirm(again === n ? L('All {0} approved sites already have AI text. Write it again for all of them?', n) : L('{0} of the {1} approved sites already have AI text; it will be written again for them too. Go on?', again, n))) return;
      w.disabled = true; w.textContent = L('Starting…'); api('jobs/' + id + '/content', {}, 'POST').then(function () { job(id); }).catch(function (x) { w.disabled = false; jerr(x); }); };
    var box = document.getElementById('sites');
    if (box.dataset.seo) return;
    box.dataset.seo = '1'; // one listener for every card's buttons
    box.addEventListener('click', function (e) {
      var b = e.target.closest('[data-seo],[data-aiw],[data-aiun]');
      if (!b) return;
      var sid = b.dataset.seo || b.dataset.aiw || b.dataset.aiun, jid = location.hash.split('/')[2], done = function () { job(jid); }, err = function (x) { alert(L(x.message || String(x))); };
      if (b.dataset.seo) {
        var v = b.dataset.v;
        if (v === 'changes') {
          var note = prompt(L('What should change in this template? (the note stays on the card)'), b.dataset.note || '');
          if (note === null) return;
          api('sites/' + sid + '/seo', { status: 'changes', note: note }, 'PUT').then(done).catch(err);
        } else api('sites/' + sid + '/seo', { status: v || null }, 'PUT').then(done).catch(err);
      } else if (b.dataset.aiw) {
        b.disabled = true;
        api('jobs/' + jid + '/content', { sites: [sid] }, 'POST').then(done).catch(function (x) { b.disabled = false; err(x); });
      } else if (confirm(L('Put the template’s text back on this site? The AI text is removed and the site is rebuilt.'))) {
        api('sites/' + sid + '/ai', null, 'DELETE').then(done).catch(err);
      }
    });
  }
  function seoCard(s) {
    var st = seoState(s), c = s.content || {}, note = s.seo && s.seo.note;
    var chips = (st === 'approved' ? '<span class="chip ok">' + L('Approved by SEO') + '</span>' : st === 'changes' ? '<span class="chip warn" title="' + esc(note || '') + '">' + L('Needs changes') + '</span>' : '<span class="chip auto">' + L('Not reviewed') + '</span>')
      + (c.state === 'queued' ? '<span class="chip auto">' + L('Text queued') + '</span>' : c.state === 'writing' ? '<span class="chip warn">' + L('AI is writing…') + '</span>'
        : c.state === 'error' ? '<span class="chip bad" title="' + esc(c.error || '') + '">' + L('Text failed') + '</span>' : s.ai ? '<span class="chip ok" title="' + esc(L('{0} texts rewritten, {1} below sections · {2}', c.fields || 0, c.after || 0, c.model || '')) + '">' + L('AI text') + '</span>' : '');
    var busy = c.state === 'queued' || c.state === 'writing';
    return '<div class="seorow"><div class="chips">' + chips + '</div>'
      + (st === 'changes' && note ? '<p class="seonote">' + esc(note) + '</p>' : '')
      + (c.state === 'error' ? '<p class="seonote err">' + esc(c.error || '') + '</p>' : '')
      + '<div class="acts">' + (st !== 'approved' ? '<button type="button" data-seo="' + s.id + '" data-v="approved">' + L('Approve') + '</button>' : '<button type="button" data-seo="' + s.id + '" data-v="">' + L('Withdraw approval') + '</button>')
      + '<button type="button" data-seo="' + s.id + '" data-v="changes" data-note="' + esc(note || '') + '">' + L('Needs changes') + '</button>'
      + (st === 'approved' && !busy ? '<button type="button" data-aiw="' + s.id + '">' + (s.ai ? L('Write again') : L('Write the text')) + '</button>' : '')
      + (s.ai && !busy ? '<button type="button" data-aiun="' + s.id + '">' + L('Template text back') + '</button>' : '') + '</div></div>';
  }

  function priceModels(v) { // the chosen model, the suggestions and every model with requests, once each
    var out = [];
    [v.model].concat(v.models, v.used_models, Object.keys(v.prices)).forEach(function (m) { if (m && out.indexOf(m) < 0) out.push(m); });
    return out;
  }

  /* Vertex AI connection: an API key (Vertex Express, Gemini) or a service account (any region, Gemini and Claude) */
  function vertexPage() {
    api('vertex').then(function (v) {
      main.innerHTML = settingsTabs('vertex') + '<h3>Vertex AI</h3><p class="muted" style="max-width:860px">' + L('The model that writes the sites’ text after the SEO review. Connect with an API key (Vertex Express: Gemini models, small quotas) or a service account key (your Google Cloud project’s quotas; also Claude models). Keys are stored on the server and never shown again.') + '</p>'
        + '<form id="vf" class="card mform" style="max-width:760px">'
        + '<div class="field"><span class="lbl">' + L('Connect with') + '</span><label class="inl"><input type="radio" name="vmode" value="key"' + (v.mode !== 'sa' ? ' checked' : '') + '> ' + L('API key (Vertex Express)') + '</label><label class="inl"><input type="radio" name="vmode" value="sa"' + (v.mode === 'sa' ? ' checked' : '') + '> ' + L('Service account (JSON key)') + '</label></div>'
        + '<div id="v-key"><div class="field"><label class="lbl" for="v-api">' + L('API key') + '</label><span class="muted" style="font-size:13px">' + (v.api_key_set ? L('Saved (ends in {0}). Leave empty to keep it.', esc(v.api_key_end)) : L('Google Cloud console → Vertex AI → API keys (Express mode).')) + '</span><input type="password" id="v-api" autocomplete="off" placeholder="' + (v.api_key_set ? '••••••••' + esc(v.api_key_end) : 'AQ.…') + '">'
        + (v.api_key_set ? '<label class="inl"><input type="checkbox" id="v-clearkey"> ' + L('Remove the saved key') + '</label>' : '') + '</div></div>'
        + '<div id="v-sa"><div class="field"><label class="lbl" for="v-json">' + L('Service account key (JSON)') + '</label><span class="muted" style="font-size:13px">' + (v.sa_set ? L('Saved: {0}. Leave empty to keep it.', esc(v.sa_email)) : L('IAM → Service accounts → Keys → Add key → JSON. The account needs the «Vertex AI User» role.')) + '</span>'
        + '<textarea id="v-json" rows="5" placeholder="{ &quot;type&quot;: &quot;service_account&quot;, … }"></textarea><label class="btn" style="justify-self:start" for="v-file">' + L('Choose the .json file') + '</label><input type="file" id="v-file" accept=".json,application/json" hidden>'
        + (v.sa_set ? '<label class="inl"><input type="checkbox" id="v-clearsa"> ' + L('Remove the saved key') + '</label>' : '') + '</div>'
        + '<div class="field"><label class="lbl" for="v-proj">' + L('Project ID') + '</label><span class="muted" style="font-size:13px">' + L('Taken from the key when empty.') + '</span><input type="text" id="v-proj" value="' + esc(v.project_id) + '"></div>'
        + '<div class="field"><label class="lbl" for="v-loc">' + L('Region') + '</label><span class="muted" style="font-size:13px">' + L('e.g. us-central1, europe-west4, or global.') + '</span><input type="text" id="v-loc" value="' + esc(v.location) + '"></div>'
        + '<div class="field"><label class="lbl" for="v-cloc">' + L('Region for Claude models') + '</label><span class="muted" style="font-size:13px">' + L('Only if Claude runs in another region (e.g. us-east5). Empty = the region above.') + '</span><input type="text" id="v-cloc" value="' + esc(v.claude_location) + '"></div></div>'
        + '<div class="field"><label class="lbl" for="v-model">' + L('Model') + '</label><span class="muted" style="font-size:13px">' + L('A Gemini model id, or a Claude model id (service account only). Pick one or type another.') + '</span><input type="text" id="v-model" list="v-models" value="' + esc(v.model) + '"><datalist id="v-models">' + v.models.map(function (m) { return '<option value="' + esc(m) + '">'; }).join('') + '</datalist></div>'
        + '<div class="row"><button class="primary" type="submit">' + L('Save') + '</button><button type="button" id="v-test"' + (v.ready ? '' : ' disabled') + '>' + L('Test the connection') + '</button><span id="v-msg" role="status"></span></div></form>'
        + '<form id="vp" class="card mform" style="max-width:760px;margin-top:18px"><h3 style="margin-top:0">' + L('Prices') + '</h3>'
        + '<p class="muted" style="margin-top:0;font-size:14px">' + L('USD per 1 million tokens, from the Vertex AI price list of your Google Cloud account. A request\u2019s cost is worked out when it is made; requests made before a price was set get it when you save.') + '</p>'
        + usageLine(v.usage, true)
        + '<table class="utable"><thead><tr><th>' + L('Model') + '</th><th>' + L('Input, $ / 1M') + '</th><th>' + L('Output, $ / 1M') + '</th></tr></thead><tbody>'
        + priceModels(v).map(function (m) {
          var p = v.prices[m] || {};
          return '<tr data-m="' + esc(m) + '"><td><code>' + esc(m) + '</code>' + (v.used_models.indexOf(m) < 0 ? '' : ' <span class="chip auto">' + L('used') + '</span>') + '</td>'
            + '<td><input type="number" step="any" min="0" class="p-in" value="' + (p.in != null ? p.in : '') + '" style="width:110px"></td><td><input type="number" step="any" min="0" class="p-out" value="' + (p.out != null ? p.out : '') + '" style="width:110px"></td></tr>';
        }).join('') + '</tbody></table>'
        + '<div class="row" style="margin-top:10px"><button class="primary" type="submit">' + L('Save prices') + '</button><span id="vp-msg" role="status"></span></div></form>';
      var show = function () { var sa = document.querySelector('input[name="vmode"]:checked').value === 'sa'; document.getElementById('v-sa').hidden = !sa; document.getElementById('v-key').hidden = sa; };
      [].forEach.call(document.querySelectorAll('input[name="vmode"]'), function (r) { r.onchange = show; });
      show();
      var msg = document.getElementById('v-msg'), say = function (t, cls) { msg.className = cls || ''; msg.textContent = t; };
      document.getElementById('v-file').onchange = function () {
        var f = this.files[0];
        if (!f) return;
        var r = new FileReader();
        r.onload = function () { document.getElementById('v-json').value = String(r.result); };
        r.readAsText(f);
      };
      document.getElementById('vf').onsubmit = function (e) {
        e.preventDefault();
        var g = function (i) { var el = document.getElementById(i); return el ? el.value : ''; }, c = function (i) { var el = document.getElementById(i); return !!(el && el.checked); };
        api('vertex', { mode: document.querySelector('input[name="vmode"]:checked').value, api_key: g('v-api'), sa_json: g('v-json'), clear_key: c('v-clearkey'), clear_sa: c('v-clearsa'),
          project_id: g('v-proj'), location: g('v-loc'), claude_location: g('v-cloc'), model: g('v-model') }, 'PUT')
          .then(function () { vertexPage(); setTimeout(function () { var m = document.getElementById('v-msg'); if (m) { m.className = 'ok'; m.textContent = L('Saved.'); } }, 300); })
          .catch(function (x) { say(L(x.message || String(x)), 'err'); });
      };
      document.getElementById('vp').onsubmit = function (e) {
        e.preventDefault();
        var prices = {};
        [].forEach.call(document.querySelectorAll('#vp tr[data-m]'), function (tr) {
          var i = tr.querySelector('.p-in').value, o = tr.querySelector('.p-out').value;
          if (i !== '' || o !== '') prices[tr.dataset.m] = { in: i === '' ? null : +i, out: o === '' ? null : +o };
        });
        api('vertex/prices', { prices: prices }, 'PUT').then(function () { vertexPage(); setTimeout(function () { var m = document.getElementById('vp-msg'); if (m) { m.className = 'ok'; m.textContent = L('Saved.'); } }, 300); })
          .catch(function (x) { var m = document.getElementById('vp-msg'); m.className = 'err'; m.textContent = L(x.message || String(x)); });
      };
      document.getElementById('v-test').onclick = function () {
        say(L('Testing…'), 'muted');
        api('vertex/test', {}, 'POST').then(function (r) {
          if (r.ok) say(L('Connected: {0} answered in {1} ms.', r.model, r.ms), 'ok'); else say(r.error, 'err');
        }).catch(function (x) { say(L(x.message || String(x)), 'err'); });
      };
    }).catch(fail);
  }

  /* Vertex AI cost (generator/vertex.php ai_usage): USD with enough decimals for small amounts, and a summary line */
  function usd(n) { n = +n || 0; return '$' + (n === 0 ? '0' : n < 0.01 ? n.toFixed(4) : n < 1 ? n.toFixed(3) : n.toFixed(2)); }
  function num(n) { return (+n || 0).toLocaleString(); }
  var KIND = { text: 'site text', facts: 'brand facts', test: 'connection test' };
  function usageLine(u, withKinds) {
    if (!u || !u.calls) return '';
    var kinds = withKinds && u.by_kind.length > 1 ? ' (' + u.by_kind.map(function (k) { return L(KIND[k.k] || k.k) + ' ' + usd(k.cost); }).join(', ') + ')' : '';
    return '<p class="usage"><b>Vertex AI: ' + usd(u.cost) + '</b>' + kinds + ' · ' + L('{0} requests', num(u.calls)) + ' · ' + L('{0} tokens in, {1} out', num(u.input), num(u.output))
      + (u.unpriced ? ' · <span class="warnx">' + L('{0} requests without a price ({1}):', num(u.unpriced), esc(u.unpriced_models.join(', '))) + ' <a href="#/settings/vertex">' + L('set the price') + '</a></span>' : '') + '</p>';
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
      var refreshing = d.sites.filter(function (s) { return s.status === 'refresh' || s.status === 'refreshing'; }).length; // «Update the data» in progress
      var byType = {};
      d.sites.forEach(function (s) { (byType[s.type] = byType[s.type] || []).push(s); });
      if (!document.getElementById('jobhead')) {
        main.innerHTML = '<p><a href="#/p/' + j.project_id + '">← ' + esc(j.project_name) + '</a></p><div id="jobhead"></div><div id="sites"></div><h3>' + L('How different are the sites?') + '</h3><div id="diffnote"></div><div id="diff" class="muted">' + L('Shown when the sites are built.') + '</div>';
      }
      document.getElementById('jobhead').innerHTML = '<div class="row"><h2>' + esc(j.name) + '</h2><span class="chip">' + esc(j.market.toUpperCase()) + '</span><span class="sp"></span>'
        + (j.status === 'stopped' ? '<button id="retry" class="primary">' + L('Continue the build') + '</button>' : failed && !running ? '<button id="retry">' + L('Retry failed ({0})', failed) + '</button>' : '')
        + (running ? '' : '<a class="btn" href="#/j/' + j.id + '/edit">' + L('Edit job') + '</a><button id="restyle" title="' + L('New seed, style, palette and hero layout for every site; the variations stay') + '">' + L('New looks') + '</button><button id="refresh" title="' + L('Pages written again with the current brand data, licence and AI text, and new preview pictures; design and text stay, no difference checks. About 2 seconds per site.') + '">' + L('Update the data') + '</button><button id="rebuild" title="' + L('Same plans (seeds, styles, variations), current engine; sites under the difference thresholds are re-planned') + '">' + L('Rebuild all') + '</button>') + (built ? '<button id="briefs" aria-expanded="' + BRIEFS.open + '" aria-controls="bpanel">' + L('AI briefs') + ' ↓</button>' : '') + '<button class="danger" id="delj">' + L('Delete job') + '</button></div>'
        + (built && BRIEFS.open ? briefPanel(j, d) : '')
        + '<div style="margin:6px 0 2px;font-size:14px"><label style="display:inline-flex;gap:8px;align-items:center"><input type="checkbox" id="jcmp"' + (+j.compare ? ' checked' : '') + '> ' + L('Count this job in comparisons') + '</label>'
        + '<div class="muted" style="font-size:13px;margin-top:2px">' + L('Untick for a draft or test job: other jobs are not compared with its sites or rebuilt to differ from them. Its own sites are still checked.') + '</div></div>'
        + '<p class="muted">' + (running ? '' : L('{0} of {1} sites built', built, d.sites.length) + (failed ? L(', {0} failed', failed) : '') + '. ') + L('Open a site to see every page, the phone view, the developer panel and the AI brief.') + '</p>'
        + '<div class="bar"><i style="width:' + Math.round(100 * (built + failed) / Math.max(1, d.sites.length)) + '%"></i></div>'
        + (j.status === 'stopped' ? '<p class="note">' + L('The build was stopped before it finished ({0} of {1} sites built). Continue the build to finish the rest.', built, d.sites.length) + '</p>' : '')
        + (j.status === 'queued' ? '<div class="notice" role="status"><span class="spin" aria-hidden="true"></span><span><b>' + L('Waiting for another job') + '.</b> ' + esc(L('Job "{0}" is building now. This job starts automatically when it is done; one job builds at a time, so the generator stays responsive.', j.busy ? j.busy.name : '…')) + '</span></div>'
        : running && refreshing && j.status !== 'queued' ? '<div class="notice" role="status"><span class="spin" aria-hidden="true"></span><span><b>' + L('Updating the data: {0} sites left', refreshing) + '.</b> '
          + L('Pages are written again and new preview pictures taken, a few sites at a time; each card comes back when its site is done. Design and text stay. You can leave this page.') + '</span></div>'
        : running ? '<div class="notice" role="status"><span class="spin" aria-hidden="true"></span><span><b>' + (j.status === 'checking' ? L('Checking the differences') : j.status === 'planned' ? L('Starting the build') : L('Rebuilding: {0} of {1} sites done', built, d.sites.length)) + '.</b> '
          + (j.status === 'checking' ? L('Screenshots are compared site by site; a site that looks too alike is rebuilt, so its card can still change.') : L('Sites are built one by one; each card comes back when its site is ready.'))
          + ' ' + L('The comparison table updates when the job is done. You can leave this page; the build carries on.') + '</span></div>' : '')
        + usageLine(j.usage, true)
        + (!running && built ? seoPanel(j, d) : '')
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
      briefWire(j, d);
      seoWire(id, d, jerr);
      var cmp = document.getElementById('jcmp');
      cmp.onchange = function () { cmp.disabled = true; api('jobs/' + id + '/compare', { compare: cmp.checked }, 'PUT').then(function () { cmp.disabled = false; diff(id, d); }).catch(function (x) { cmp.disabled = false; cmp.checked = !cmp.checked; jerr(x); }); };
      var rs = document.getElementById('restyle');
      if (rs) rs.onclick = function () { if (!confirm(L('Give every site of this job a new seed, style, palette and hero layout? The variations stay the same.'))) return; rs.disabled = true; rs.textContent = L('Starting…'); api('jobs/' + id + '/restyle', null, 'POST').then(function () { job(id); }).catch(function (x) { rs.disabled = false; rs.textContent = L('New looks'); jerr(x); }); };
      var rf = document.getElementById('refresh');
      if (rf) rf.onclick = function () { rf.disabled = true; rf.textContent = L('Starting…'); api('jobs/' + id + '/refresh', null, 'POST').then(function () { job(id); }).catch(function (x) { rf.disabled = false; rf.textContent = L('Update the data'); jerr(x); }); };
      var rb = document.getElementById('rebuild');
      if (rb) rb.onclick = function () { rb.disabled = true; rb.textContent = L('Starting…'); api('jobs/' + id + '/rebuild', null, 'POST').then(function () { job(id); }).catch(function (x) { rb.disabled = false; rb.textContent = L('Rebuild all'); jerr(x); }); };
      clearTimeout(poll);
      var writing = d.sites.some(function (s) { return s.content && (s.content.state === 'queued' || s.content.state === 'writing'); });
      if (running || writing) {
        restoreScroll();
        poll = setTimeout(function () { if (location.hash === '#/j/' + id) job(id); }, 4000);
      }
      if (!running) {
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

  window.GEN = { api: api, esc: esc, L: L, fail: fail, main: main, RU: RU, pageName: pageLabel, lang: function () { return LANG; } };
<?php readfile(__DIR__ . '/markets.js'); ?>
<?php readfile(__DIR__ . '/types.js'); ?>
<?php readfile(__DIR__ . '/docs.js'); ?>

  function route() {
    clearTimeout(poll);
    var h = location.hash.replace(/^#\/?/, '').split('/');
    if (h[0] === 'vertex' || (h[0] === 'settings' && h[1] === 'vertex')) return vertexPage();
    if (h[0] === 'settings') return settings();
    if (h[0] === 'markets') return GEN.markets(h);
    if (h[0] === 'types') return GEN.types(h);
    if (h[0] === 'docs') return GEN.docs(h);
    if (h[0] === 'p' && h[2] === 'new') return jobForm(h[1]);
    if (h[0] === 'p') return project(h[1]);
    if (h[0] === 'j' && h[2] === 'edit') return jobForm(null, h[1]);
    if (h[0] === 'j' && h[2] === 'brands') return brandFacts(h[1]);
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
