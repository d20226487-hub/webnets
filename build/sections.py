"""Section pictures for the gallery's "By section" view: one example of every block design (section), shown on its own.

  python3 build/sections.py            (in the Docker container, or anywhere with Chromium: CHROME=/path/to/chrome)

Finds each block design in the demos' review pages (never in generated sites: the gallery must not depend on any job).
A design that no demo shows gets an extra demo page of its own, dist/review/sec-<block>--<design>/: a demo of a type
whose section allows it, built with that design pinned. Each design's page is copied with every other section hidden
and screenshotted on desktop (1280 px at half size) and on a phone (375 px in a frame). Writes dist/gallery/sections/<block>--<design>-d.png / -m.png and dist/gallery/sections.json
(each design's pictures and the page it came from). Pages are found through their developer data (data-sxdev).
"""
import glob, html, json, os, re, shutil, subprocess, sys

root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
os.chdir(root)
CHROME = os.environ.get('CHROME') or shutil.which('chromium') or shutil.which('chromium-browser') or shutil.which('google-chrome')
if not CHROME:
    sys.exit('Chromium or Chrome not found; set CHROME=/path/to/chrome.')
OUT = 'dist/gallery/sections'
SRC = 'dist/gallery/sections-src'
FRAME = {'header', 'footer', 'age-gate', 'sticky-cta', 'compliance-bar', 'disclosure'}
HIDE = ('<style>body *:not([data-sxsec]):not([data-sxsec] *):not(:has([data-sxsec])){display:none!important}'
        ':has(>[data-sxsec]),:has([data-sxsec]){margin:0!important;padding:0!important;gap:0!important;border:0!important;'
        'grid-template-columns:minmax(0,1fr)!important;max-width:none!important;width:auto!important;min-height:0!important}'
        '[data-sxsec]{width:auto!important}.vbar,#sx-devtoggle,#sx-skeltoggle,#sx-devview,'
        '[class*="agegate"],[class*="age-gate"],[role="dialog"][id^="age-"]{display:none!important}html,body{overflow:visible!important}'
        'body{padding:0!important;margin:0!important}'
        'html{filter:grayscale(1)}</style>')  # one palette for every section (white, black, grey): designs compare, not colours
DEV = re.compile(r'<script type="application/json" id="sx-devdata">(.*?)</script>', re.S)


SHOW = 'sec-'  # extra demo pages for designs no demo shows: dist/review/sec-<block>--<design>/


def pages(pattern='dist/review/*/*.html'):
    """Demo review pages, the main combination first; the extra design pages after the real demos."""
    files = sorted(glob.glob(pattern), key=lambda f: (os.path.basename(os.path.dirname(f)).startswith(SHOW), os.path.basename(f) != 'index.html', f))
    return [f for f in files if not os.path.basename(f).startswith('_')]


_designs = {}


def current(block):
    """The designs a block has now (blocks/<block>/block.json), so old builds' retired designs are left out."""
    if block not in _designs:
        try:
            _designs[block] = set(json.load(open(f'blocks/{block}/block.json', encoding='utf-8'))['sx'].get('variants') or ['-'])
        except (OSError, ValueError, KeyError):
            _designs[block] = set()
    return _designs[block]


def mark(page, i):
    """The section's own element gets data-sxsec: the element carrying data-sxdev="i", or, when that is a block's
    leading <script> or <style> (an app hero's device script), the element right after it."""
    body = page.find('<body')
    m = re.compile(r'<(\w+)\b[^>]*\bdata-sxdev="' + str(i) + r'"[^>]*>').search(page, body)
    if not m:
        return page
    at = m.start()
    if m.group(1).lower() in ('script', 'style'):
        end = page.find('</' + m.group(1), m.end())
        nxt = re.compile(r'<(?!/|script|style|!--)(\w+)').search(page, end + 1)
        while nxt and nxt.group(1).lower() in ('script', 'style'):
            nxt = re.compile(r'<(?!/|script|style|!--)(\w+)').search(page, page.find('</' + nxt.group(1), nxt.end()) + 1)
        if not nxt:
            return page
        at = nxt.start()
    tag_end = page.find('>', at)
    return page[:tag_end] + ' data-sxsec' + page[tag_end:]


found = {}


def scan(files):
    """Record the first page showing each design (the section's first appearance on that page)."""
    quiet = 0  # pages in a row that showed no new design: the scan stops after a long stretch of them
    for f in files:
        quiet += 1
        if quiet > 4000:
            break
        try:
            h = open(f, encoding='utf-8').read()
        except OSError:
            continue
        m = DEV.search(h)
        if not m:
            continue
        try:
            items = json.loads(m.group(1)).get('items', [])
        except ValueError:
            continue
        first = True
        for i, it in enumerate(items):
            if it.get('n') != 0 or it.get('b') in FRAME:
                continue
            key = (it['b'], it.get('v') or '-')
            if key[1] not in current(key[0]):  # a design the block no longer has (an older build)
                continue
            if key not in found and f'data-sxdev="{i}"' in h:
                found[key] = {'file': f, 'index': i, 'first_screen': first}
                quiet = 0
            first = False
        if len(found) > 400:
            break



def wanted():
    """Every design a site type's section allows (blueprints/*.json), frame blocks aside: {(block, design): [(type, page id or None, section id)]}."""
    out = {}
    for f in sorted(glob.glob('blueprints/*.json')):
        bp = json.load(open(f, encoding='utf-8'))
        if bp.get('based_on'):  # a copied type shows nothing its base type does not
            continue
        t = os.path.basename(f)[:-5]
        multi = bool(bp.get('pages'))
        pages_ = bp.get('pages') or {'home': bp}
        for pid, pg in (pages_.items() if isinstance(pages_, dict) else enumerate(pages_)):
            for sec in pg.get('sections') or []:
                b = sec.get('block')
                if not b or b in FRAME:
                    continue
                for v in (sec.get('variants') or sorted(current(b))):
                    if v in current(b):
                        out.setdefault((b, v), []).append((t, pid if multi else None, sec.get('id')))
    return out


def showcase(b, v, where):
    """Build a demo page that shows design v of block b: the first demo of a type whose section allows it, with that
    design pinned (also over the angle's list). Returns the page folder, or None when no demo can show it."""
    demos = {}
    for f in sorted(glob.glob('sites/demos/*.json')):
        try:
            c = json.load(open(f, encoding='utf-8'))
        except ValueError:
            continue
        demos.setdefault(c.get('type'), []).append(c)
    name = f'{SHOW}{b}--{v}'
    for t, pid, sid in where:
        for c in demos.get(t, []):
            if c.get('id') == 'vantro-kz-app':  # the user's draft is not a demo
                continue
            cfg = {k: x for k, x in c.items() if k not in ('variations', 'variation_labels')}
            cfg['id'] = name
            pins = dict(cfg.get('variants') or {})
            if pid is None:  # one-page types: {section: design}; multi-page types: {page: {section: design}}
                pins[sid] = v
            else:
                pins[pid] = dict(pins.get(pid) or {}, **{sid: v})
            cfg['variants'] = pins
            cfg['pins_over_angle'] = True
            tmp = os.path.join('dist', 'qa', name + '.json')
            os.makedirs(os.path.dirname(tmp), exist_ok=True)
            json.dump(cfg, open(tmp, 'w', encoding='utf-8'), ensure_ascii=False)
            r = subprocess.run([os.environ.get('PHP', 'php'), 'build/export-review-site.php', tmp], capture_output=True, text=True, encoding='utf-8')
            os.remove(tmp)
            before = set(found)
            scan(pages(f'dist/review/{name}/*.html'))
            if (b, v) in found:
                return name
            for k in set(found) - before:  # this page is discarded, so the other designs it showed cannot point at it
                del found[k]
            shutil.rmtree(f'dist/review/{name}', ignore_errors=True)
            if os.path.isfile(f'dist/review/{name}.html'):
                os.remove(f'dist/review/{name}.html')
    return None


for old in glob.glob(f'dist/review/{SHOW}*'):  # extra design pages are rebuilt on every run, so they follow the engine
    shutil.rmtree(old, ignore_errors=True) if os.path.isdir(old) else os.remove(old)
scan(pages())
missing = []
for (b, v), where in sorted(wanted().items()):
    if (b, v) not in found and not showcase(b, v, where):
        missing.append(f'{b}.{v}')
if missing:
    print(f'No demo can show {len(missing)} designs (their market or type rules forbid them): {", ".join(missing)}', file=sys.stderr)

os.makedirs(OUT, exist_ok=True)
os.makedirs(SRC, exist_ok=True)
shots = []
for (b, v), x in sorted(found.items()):
    name = f'{b}--{v}'
    page = mark(open(x['file'], encoding='utf-8').read(), x['index']).replace('</head>', HIDE + '</head>', 1)
    base = os.path.dirname(os.path.abspath(x['file']))
    page = page.replace('<head>', f'<head><base href="file://{base}/">', 1)
    src = os.path.join(SRC, name + '.html')
    open(src, 'w', encoding='utf-8').write(page)
    open(os.path.join(SRC, name + '-phone.html'), 'w', encoding='utf-8').write(
        '<!doctype html><html><head><meta charset="utf-8"><style>html,body{margin:0;background:#fff}iframe{display:block;width:375px;height:780px;border:0}</style>'
        f'</head><body><iframe src="{name}.html"></iframe></body></html>')
    url = 'file://' + os.path.abspath(SRC)
    common = [CHROME, '--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu', '--hide-scrollbars', '--allow-file-access-from-files',
              '--virtual-time-budget=4000']
    shots.append(common + ['--force-device-scale-factor=0.5', '--window-size=1280,900', f'--screenshot={OUT}/{name}-d.png', f'{url}/{name}.html'])
    shots.append(common + ['--force-device-scale-factor=0.8', '--window-size=520,780', f'--screenshot={OUT}/{name}-m.png', f'{url}/{name}-phone.html'])
    x['name'] = name

running = []
for cmd in shots:
    if len(running) >= 6:
        running.pop(0).wait()
    running.append(subprocess.Popen(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL))
for p in running:
    p.wait()

catalog = []
for (b, v), x in sorted(found.items()):
    if not os.path.exists(f"{OUT}/{x['name']}-d.png"):
        continue
    rel = os.path.relpath(x['file'], 'dist').replace(os.sep, '/')
    catalog.append({'block': b, 'design': v, 'first_screen': x['first_screen'], 'page': rel,
                    'desktop': f"gallery/sections/{x['name']}-d.png", 'phone': f"gallery/sections/{x['name']}-m.png"})
json.dump(catalog, open('dist/gallery/sections.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print(f'{len(catalog)} section designs pictured ({len({c["block"] for c in catalog})} blocks) in {OUT}')
