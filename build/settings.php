<?php
/**
 * Local AI settings page (served by build/local-router.php at /settings/).
 * View and edit the parts every AI brief is built from: ai/system.md, ai/types/<type>.md and ai/markets/<market>.md,
 * preview the assembled brief for any demo, and rebuild the review pages so the briefs pick up the changes.
 *
 * API (JSON): GET  api/files            every settings file with its text and the demos that use it
 *             POST api/save {file,text} write one settings file
 *             GET  api/preview?demo=id  system prompt + site brief for a demo's main combination
 *             POST api/rebuild          start build/rebuild-reviews.py in the background
 *             GET  api/status           the rebuild log
 */

$root = dirname(__DIR__);
$ai = "$root/ai";
$log = "$root/dist/qa/rebuild.log";
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$api = preg_match('#^/settings/api/(\w+)$#', $path, $m) ? $m[1] : null;

function sx_set_json($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/** Gallery demos (configs linked from docs/types/*.md) with their type and market. */
function sx_set_demos(string $root): array
{
    $out = [];
    foreach (glob("$root/docs/types/*.md") as $f) {
        preg_match_all('#sites/demos/([a-z0-9-]+)\.json\)#', file_get_contents($f), $mm);
        foreach ($mm[1] as $id) {
            $c = json_decode(file_get_contents("$root/sites/demos/$id.json"), true);
            $out[$id] = ['id' => $id, 'type' => $c['type'] ?? '', 'market' => $c['market'] ?? '', 'locale' => $c['locale'] ?? '', 'name' => $c['site']['name'] ?? $id];
        }
    }
    ksort($out);
    return $out;
}

if ($api === 'files') {
    $demos = sx_set_demos($root);
    $files = [];
    foreach (array_merge(["$ai/system.md"], glob("$ai/types/*.md"), glob("$ai/markets/*.md")) as $f) {
        $rel = substr($f, strlen($ai) + 1);
        $key = basename($f, '.md');
        $group = str_contains($rel, '/') ? dirname($rel) : 'system';
        $used = array_values(array_filter($demos, fn($d) => $group === 'system' || ($group === 'types' && $d['type'] === $key) || ($group === 'markets' && $d['market'] === $key)));
        $files[] = ['file' => $rel, 'group' => $group, 'key' => $key, 'text' => file_get_contents($f), 'mtime' => filemtime($f), 'used' => array_column($used, 'id')];
    }
    sx_set_json(['files' => $files, 'demos' => array_values($demos)]);
    return;
}

if ($api === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $file = $in['file'] ?? '';
    if (!preg_match('#^(system|types/[a-z-]+|markets/[a-z]+)\.md$#', $file) || !is_string($in['text'] ?? null)) {
        sx_set_json(['error' => 'Unknown settings file'], 400);
        return;
    }
    $text = str_replace("\r\n", "\n", $in['text']);
    file_put_contents("$ai/$file", rtrim($text) . "\n");
    sx_set_json(['ok' => true, 'mtime' => filemtime("$ai/$file")]);
    return;
}

if ($api === 'preview') {
    $id = $_GET['demo'] ?? '';
    $demos = sx_set_demos($root);
    if (!isset($demos[$id])) {
        sx_set_json(['error' => 'Unknown demo'], 400);
        return;
    }
    require_once "$root/lib/page.php";
    require_once "$root/lib/aibrief.php";
    $cfg = json_decode(file_get_contents("$root/sites/demos/$id.json"), true);
    unset($cfg['variations'], $cfg['variation_labels']);
    $cfg['urls'] = 'files';
    $cfg['_devspec'] = true;
    try {
        $pages = array_values(array_filter(sx_build_site($cfg, $root), fn($p) => empty($p['asset'])));
        sx_set_json(['system' => sx_ai_system($pages), 'brief' => sx_ai_brief($pages)]);
    } catch (Throwable $e) {
        sx_set_json(['error' => $e->getMessage()], 500);
    }
    return;
}

if ($api === 'rebuild' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    @mkdir(dirname($log), 0777, true);
    file_put_contents($log, "Started " . date('H:i:s') . "\n");
    $py = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
    $cmd = escapeshellarg($py) . ' ' . escapeshellarg("$root/build/rebuild-reviews.py") . ' >> ' . escapeshellarg($log) . ' 2>&1';
    if (PHP_OS_FAMILY === 'Windows') {
        pclose(popen('start "" /B cmd /c "' . $cmd . '"', 'r'));
    } else {
        exec($cmd . ' &');
    }
    sx_set_json(['ok' => true]);
    return;
}

if ($api === 'status') {
    $text = is_file($log) ? file_get_contents($log) : '';
    sx_set_json(['log' => $text, 'running' => $text !== '' && !str_contains($text, 'DONE')]);
    return;
}

if ($api !== null) {
    sx_set_json(['error' => 'Not found'], 404);
    return;
}
?><!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AI brief settings</title>
<script>/* theme and language before first paint; same saved choices as the gallery (sxg-theme, sxg-lang) */
(function(){var d=document.documentElement,g=function(k){try{return localStorage.getItem(k)}catch(e){return null}};var th=g('sxg-theme')||'light';if(th==='auto')d.removeAttribute('data-theme');else d.setAttribute('data-theme',th);
var l=g('sxg-lang')||((navigator.language||'').toLowerCase().indexOf('ru')===0?'ru':'en');d.setAttribute('data-lang',l);d.lang=l;})();</script>
<style>
:root{--bg:#F6F7F9;--panel:#FFFFFF;--line:#E1E4EA;--text:#14171F;--muted:#5D6575;--accent:#4F46E5;--accent-2:#EEF0FF;--ok:#13795B;--warn:#A15C00;color-scheme:light}
:root[data-theme="dark"]{--bg:#0E1116;--panel:#161A22;--line:#262C38;--text:#E7EAF0;--muted:#99A1B3;--accent:#8B85FF;--accent-2:#1E2140;--ok:#4ADE9B;--warn:#F2B35B;color-scheme:dark}
@media (prefers-color-scheme:dark){:root:not([data-theme]){--bg:#0E1116;--panel:#161A22;--line:#262C38;--text:#E7EAF0;--muted:#99A1B3;--accent:#8B85FF;--accent-2:#1E2140;--ok:#4ADE9B;--warn:#F2B35B;color-scheme:dark}}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:15px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
header{display:flex;flex-wrap:wrap;align-items:center;gap:10px 18px;padding:12px 24px;border-bottom:1px solid var(--line);background:var(--panel)}
header h1{margin:0;font-size:18px}header a{color:var(--accent);text-decoration:none;font-weight:600}header .sp{flex:1}
.seg{display:inline-flex;border:1px solid var(--line);border-radius:8px;overflow:hidden}.seg button{border:0;border-radius:0;padding:6px 10px;font-size:13px;background:var(--panel);color:var(--muted)}
.seg button+button{border-left:1px solid var(--line)}.seg button[aria-pressed="true"]{background:var(--accent-2);color:var(--accent)}
button{font:600 14px system-ui,sans-serif;border-radius:8px;border:1px solid var(--line);background:var(--panel);color:var(--text);padding:8px 14px;cursor:pointer}
button.primary{background:var(--accent);border-color:var(--accent);color:#fff}button:disabled{opacity:.5;cursor:default}
button:focus-visible,a:focus-visible,textarea:focus-visible,select:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
.wrap{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:calc(100vh - 62px)}
nav{border-right:1px solid var(--line);padding:16px 12px;background:var(--panel);overflow:auto}
nav h2{margin:16px 8px 6px;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}nav h2:first-child{margin-top:0}
nav a{display:flex;justify-content:space-between;gap:8px;padding:7px 10px;border-radius:8px;color:var(--text);text-decoration:none;font-size:14px}
nav a:hover{background:var(--bg)}nav a[aria-current]{background:var(--accent-2);color:var(--accent);font-weight:600}nav a small{color:var(--muted);font-weight:400}
main{padding:20px 28px;min-width:0}
.top{display:flex;flex-wrap:wrap;align-items:baseline;gap:8px 16px}.top h2{margin:0;font-size:22px}.top code{color:var(--muted)}
.help{color:var(--muted);margin:6px 0 14px;max-width:820px}
.used{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 14px}.used span,.used a{padding:2px 10px;border-radius:999px;background:var(--bg);border:1px solid var(--line);font-size:12px;color:var(--muted);text-decoration:none}.used a:hover{border-color:var(--accent);color:var(--accent)}
textarea{width:100%;min-height:52vh;padding:14px;border:1px solid var(--line);border-radius:10px;background:var(--panel);color:var(--text);font:13.5px/1.55 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;resize:vertical}
.bar{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-top:10px}.st{color:var(--muted);font-size:13px}.st.ok{color:var(--ok)}.st.warn{color:var(--warn)}
pre{white-space:pre-wrap;word-break:break-word;margin:0;padding:14px;border:1px solid var(--line);border-radius:10px;background:var(--panel);font:12.5px/1.5 ui-monospace,Menlo,Consolas,monospace;max-height:60vh;overflow:auto}
select{font:14px system-ui,sans-serif;padding:7px 10px;border-radius:8px;border:1px solid var(--line);background:var(--panel);color:var(--text);max-width:100%}
.cols{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:14px}.cols h3{margin:0 0 6px;font-size:13px;color:var(--muted)}
@media (max-width:760px){.wrap{grid-template-columns:1fr}nav{border-right:0;border-bottom:1px solid var(--line)}main{padding:16px}header{padding:12px 16px}.cols{grid-template-columns:1fr}}
</style>
</head>
<body>
<header>
  <h1 id="h1"></h1>
  <a href="/" id="back"></a>
  <span class="sp"></span>
  <div class="seg" role="group" id="langs" aria-label="Language"><button type="button" data-set-lang="en">EN</button><button type="button" data-set-lang="ru">RU</button></div>
  <div class="seg" role="group" id="themes"><button type="button" data-set-theme="light"></button><button type="button" data-set-theme="dark"></button><button type="button" data-set-theme="auto"></button></div>
  <span class="st" id="rbst"></span>
  <button type="button" class="primary" id="rebuild"></button>
</header>
<div class="wrap">
  <nav id="nav"></nav>
  <main id="main"></main>
</div>
<script>
(function () {
  var docEl = document.documentElement, files = [], demos = [], cur = null, dirty = false, lastLog = null;
  var L = {
    en: { title: 'AI brief settings', back: '← Gallery', light: 'Light', dark: 'Dark', auto: 'Auto', theme: 'Theme', rebuild: 'Rebuild review pages',
      gSystem: 'System prompt', gTypes: 'Site types', gMarkets: 'Markets', check: 'Check', preview: 'Preview a brief', type1: 'Site type', market1: 'Market',
      hSystem: 'The system prompt: role, target stack and acceptance checklist. Developers paste it once into the AI tool\'s system prompt or custom instructions. Placeholders: {scope}, {site}, {type}, {angle}, {language}. Shown in every page\'s AI brief tab and at the top of the site brief files.',
      hTypes: 'Guidance for this site type: what it is for and what a developer must get right. Added to every AI brief of this type under "Site type guidance".',
      hMarkets: 'Market context (age, allowed products, notes) and the rules the AI must not break in this market. Added to every AI brief for this market under "Market guidance".',
      noDemo: 'No gallery demo uses it yet', allDemos: 'Used in the AI brief of every gallery demo (%d).', openDemo: 'Open this demo in a new tab', save: 'Save', revert: 'Revert', unsaved: 'Unsaved changes', saved: 'Saved. Rebuild the review pages to update the briefs in the gallery.',
      discard: 'Discard unsaved changes to %s?', rebuildDirty: 'You have unsaved changes; rebuild without them?', leave: 'Unsaved changes',
      pHelp: 'The system prompt and the site brief for a demo\'s main version, assembled from the saved settings (no rebuild needed).', show: 'Show', building: 'Building…',
      sysT: 'System prompt', briefT: 'Site brief', rebuilding: 'Rebuilding… ', last: 'Last rebuild: ', edLabel: 'Settings text' },
    ru: { title: 'Настройки брифа для ИИ', back: '← Галерея', light: 'Светлая', dark: 'Тёмная', auto: 'Авто', theme: 'Тема', rebuild: 'Пересобрать страницы демо',
      gSystem: 'Системный промпт', gTypes: 'Типы сайтов', gMarkets: 'Рынки', check: 'Проверка', preview: 'Предпросмотр брифа', type1: 'Тип сайта', market1: 'Рынок',
      hSystem: 'Системный промпт: роль, целевой стек и чек-лист приёмки. Разработчик один раз вставляет его в системный промпт или пользовательские инструкции ИИ-инструмента. Подстановки: {scope}, {site}, {type}, {angle}, {language}. Показывается во вкладке «Бриф для ИИ» каждой страницы и в начале файлов брифа сайта.',
      hTypes: 'Рекомендации для этого типа сайта: для чего он и что разработчик должен сделать правильно. Добавляются в каждый бриф этого типа в раздел «Site type guidance».',
      hMarkets: 'Контекст рынка (возраст, разрешённые продукты, примечания) и правила, которые ИИ не должен нарушать на этом рынке. Добавляются в каждый бриф для этого рынка в раздел «Market guidance».',
      noDemo: 'Пока не используется ни в одном демо', allDemos: 'Используется в брифе для ИИ каждого демо галереи (%d).', openDemo: 'Открыть это демо в новой вкладке', save: 'Сохранить', revert: 'Отменить', unsaved: 'Есть несохранённые изменения', saved: 'Сохранено. Пересоберите страницы демо, чтобы обновить брифы в галерее.',
      discard: 'Отменить несохранённые изменения в %s?', rebuildDirty: 'Есть несохранённые изменения. Пересобрать без них?', leave: 'Есть несохранённые изменения',
      pHelp: 'Системный промпт и бриф сайта для основной версии демо, собранные из сохранённых настроек (пересборка не нужна).', show: 'Показать', building: 'Сборка…',
      sysT: 'Системный промпт', briefT: 'Бриф сайта', rebuilding: 'Пересборка… ', last: 'Последняя пересборка: ', edLabel: 'Текст настройки' }
  };
  var lang = docEl.getAttribute('data-lang') === 'ru' ? 'ru' : 'en';
  function t(k) { return L[lang][k]; }
  function keep(k, v) { try { localStorage.setItem(k, v); } catch (e) { /* private window: lasts for this visit */ } }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function api(path, body) {
    return fetch('/settings/api/' + path, body ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) } : {}).then(function (r) { return r.json(); });
  }
  var GROUP = function (g) { return t({ system: 'gSystem', types: 'gTypes', markets: 'gMarkets' }[g]); };

  function chrome() {
    document.title = t('title');
    document.getElementById('h1').textContent = t('title');
    document.getElementById('back').textContent = t('back');
    document.getElementById('themes').setAttribute('aria-label', t('theme'));
    document.querySelectorAll('[data-set-theme]').forEach(function (b) { b.textContent = t(b.dataset.setTheme); });
    document.querySelectorAll('[data-set-lang]').forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.setLang === lang)); });
    var th = docEl.getAttribute('data-theme') || 'auto';
    document.querySelectorAll('[data-set-theme]').forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.setTheme === th)); });
    if (!document.getElementById('rebuild').disabled) document.getElementById('rebuild').textContent = t('rebuild');
    else document.getElementById('rebuild').textContent = t('rebuild');
    document.getElementById('nav').setAttribute('aria-label', t('title'));
  }
  function drawNav() {
    var h = '';
    ['system', 'types', 'markets'].forEach(function (g) {
      h += '<h2>' + esc(GROUP(g)) + '</h2>';
      files.filter(function (f) { return f.group === g; }).forEach(function (f) {
        h += '<a href="#' + f.file + '"' + (cur && cur.file === f.file ? ' aria-current="page"' : '') + '>' + esc(g === 'system' ? 'system.md' : f.key) + '<small>' + f.used.length + '</small></a>';
      });
    });
    h += '<h2>' + esc(t('check')) + '</h2><a href="#preview"' + (location.hash === '#preview' ? ' aria-current="page"' : '') + '>' + esc(t('preview')) + '</a>';
    document.getElementById('nav').innerHTML = h;
  }
  function show(keepText) {
    var key = decodeURIComponent(location.hash.slice(1)) || 'system.md';
    if (!keepText && dirty && cur && cur.file !== key && !confirm(t('discard').replace('%s', cur.file))) { location.hash = cur.file; return; }
    var pending = keepText && cur && document.getElementById('ed') ? document.getElementById('ed').value : null;
    if (!keepText) dirty = false;
    if (key === 'preview') { cur = null; drawNav(); return showPreview(); }
    cur = files.filter(function (f) { return f.file === key; })[0] || files[0];
    drawNav();
    var title = cur.group === 'system' ? t('gSystem') : t(cur.group === 'types' ? 'type1' : 'market1') + ': ' + cur.key;
    var help = t({ system: 'hSystem', types: 'hTypes', markets: 'hMarkets' }[cur.group]);
    document.getElementById('main').innerHTML = '<div class="top"><h2>' + esc(title) + '</h2><code>ai/' + esc(cur.file) + '</code></div>'
      + '<p class="help">' + esc(help) + '</p>'
      + (cur.group === 'system'  // the system prompt is in every brief: a count, not 27 chips
        ? '<p class="help">' + esc(t('allDemos').replace('%d', cur.used.length)) + '</p>'
        : '<div class="used">' + (cur.used.length ? cur.used.map(function (d) {
            return '<a href="/review/' + encodeURIComponent(d) + '/index.html" target="_blank" rel="noopener" title="' + esc(t('openDemo')) + '">' + esc(d) + ' ↗</a>';
          }).join('') : '<span>' + esc(t('noDemo')) + '</span>') + '</div>')
      + '<textarea id="ed" spellcheck="false" aria-label="' + esc(t('edLabel')) + '">' + esc(pending !== null ? pending : cur.text) + '</textarea>'
      + '<div class="bar"><button type="button" class="primary" id="save">' + esc(t('save')) + '</button><button type="button" id="revert">' + esc(t('revert')) + '</button><span class="st" id="st"></span></div>';
    var ed = document.getElementById('ed'), st = document.getElementById('st');
    function mark() { dirty = ed.value !== cur.text; st.className = 'st warn'; st.textContent = dirty ? t('unsaved') : ''; }
    if (pending !== null) mark();
    ed.addEventListener('input', mark);
    document.getElementById('revert').onclick = function () { ed.value = cur.text; dirty = false; st.textContent = ''; };
    document.getElementById('save').onclick = function () {
      api('save', { file: cur.file, text: ed.value }).then(function (r) {
        if (r.error) { st.className = 'st warn'; st.textContent = r.error; return; }
        cur.text = ed.value.replace(/\s+$/, '') + '\n'; dirty = false;
        st.className = 'st ok'; st.textContent = t('saved');
      });
    };
  }
  function showPreview() {
    var sel = document.getElementById('demo') ? document.getElementById('demo').value : null;
    document.getElementById('main').innerHTML = '<div class="top"><h2>' + esc(t('preview')) + '</h2></div><p class="help">' + esc(t('pHelp')) + '</p>'
      + '<select id="demo">' + demos.map(function (d) { return '<option value="' + esc(d.id) + '"' + (d.id === sel ? ' selected' : '') + '>' + esc(d.id + ' · ' + d.type + ' · ' + d.market) + '</option>'; }).join('') + '</select> <button type="button" id="go">' + esc(t('show')) + '</button>'
      + '<div class="cols" style="margin-top:14px"><div><h3>' + esc(t('sysT')) + '</h3><pre id="psys"></pre></div><div><h3>' + esc(t('briefT')) + '</h3><pre id="pbrief"></pre></div></div>';
    document.getElementById('go').onclick = function () {
      document.getElementById('psys').textContent = t('building'); document.getElementById('pbrief').textContent = '';
      api('preview?demo=' + encodeURIComponent(document.getElementById('demo').value)).then(function (r) {
        document.getElementById('psys').textContent = r.error || r.system; document.getElementById('pbrief').textContent = r.error ? '' : r.brief;
      });
    };
  }
  function setLang(l) {
    lang = l; docEl.setAttribute('data-lang', l); docEl.lang = l; keep('sxg-lang', l);
    chrome(); show(true); paintStatus();
  }
  function setTheme(th) {
    if (th === 'auto') docEl.removeAttribute('data-theme'); else docEl.setAttribute('data-theme', th);
    keep('sxg-theme', th); chrome();
  }
  document.querySelectorAll('[data-set-lang]').forEach(function (b) { b.onclick = function () { setLang(b.dataset.setLang); }; });
  document.querySelectorAll('[data-set-theme]').forEach(function (b) { b.onclick = function () { setTheme(b.dataset.setTheme); }; });

  var poll = null, rbst = document.getElementById('rbst'), rb = document.getElementById('rebuild');
  function paintStatus() {
    if (!lastLog) return;
    var last = (lastLog.log || '').trim().split('\n').pop() || '';
    rb.disabled = lastLog.running;
    rbst.className = 'st' + (lastLog.running ? ' warn' : (/DONE/.test(last) ? ' ok' : ''));
    rbst.textContent = lastLog.running ? t('rebuilding') + last : (/DONE/.test(last) ? t('last') + last : '');
  }
  function status() {
    api('status').then(function (r) {
      lastLog = r; paintStatus();
      if (r.running && !poll) poll = setInterval(status, 2000);
      if (!r.running && poll) { clearInterval(poll); poll = null; }
    });
  }
  rb.onclick = function () {
    if (dirty && !confirm(t('rebuildDirty'))) return;
    rb.disabled = true; api('rebuild', {}).then(function () { setTimeout(status, 500); });
  };
  window.addEventListener('hashchange', function () { show(false); });
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = t('leave'); } });
  chrome();
  api('files').then(function (r) { files = r.files; demos = r.demos; show(false); status(); });
})();
</script>
</body>
</html>
