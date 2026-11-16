"""Section pictures for the gallery's "By section" view: one example of every block design (section), shown on its own.

  python3 build/sections.py            (in the Docker container, or anywhere with Chromium: CHROME=/path/to/chrome)

Every section is shown in one gallery style (GALLERY_STYLE: white, black and grey, one font, one radius), so designs
compare, not their demos' colours. Each demo is built once in that style as dist/review/sec-<demo>/ (never from
generated sites: the gallery must not depend on any job); a design no demo shows gets a page of its own,
dist/review/sec-<block>--<design>/: a demo of a type whose section allows it, with that design pinned. These pages are
rebuilt on every run. Each design's page is copied with every other section hidden and screenshotted on desktop
(1280 px at half size) and on a phone (375 px in a frame). Writes dist/gallery/sections/<block>--<design>-d.png / -m.png and dist/gallery/sections.json
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


# The gallery's own style: every section is drawn in it, so sections compare by design, not by their demo's colours,
# fonts or corners. White background, black text, greys for everything else; one font, one corner radius, flat
# surfaces. It goes on top of a plain preset through the site config's "tokens", so it is never one of the styles
# the generator gives to sites (tokens/presets/ is left alone).
GALLERY_PRESET = 'fresh-minimal'
GALLERY_STYLE = {
    'theme': 'light',
    'fonts': {'display': {'family': 'Inter', 'weights': [600, 700]}, 'body': {'family': 'Inter', 'weights': [400, 500]},
              'mono': {'family': 'JetBrains Mono', 'weights': [400, 500]}},
    'color': {'bg': '#FFFFFF', 'surface': '#F4F4F4', 'surface2': '#E9E9E9', 'text': '#111111', 'muted': '#666666',
              'border': '#D9D9D9', 'accent': '#111111', 'onAccent': '#FFFFFF', 'accent2': '#555555',
              'heroText': '#111111', 'heroMuted': '#555555',
              'success': '#333333', 'warning': '#777777', 'danger': '#333333'},
    'shape': {'radiusSm': '6px', 'radius': '10px', 'radiusLg': '16px', 'buttonRadius': '8px', 'borderWidth': '1px'},
    'type': {'base': 16, 'scale': 1.25, 'headingWeight': 700, 'headingCase': 'none', 'headingTracking': '-0.01em',
             'lineHeight': 1.6, 'bodyWeight': 400},
    'space': {'density': 1},
    'surface': {'style': 'flat', 'shadow': 'none'},
    'decor': {'heroBg': '#FFFFFF', 'heroAccent': '#111111', 'heroOnAccent': '#FFFFFF'},
}


# Brand colours reach a page as attributes (a logo's colour, a brand's --brand, team kits), not through the style, so
# each brand would turn a different grey. In the gallery pages they all become the same two greys: the brand grey,
# and a dark grey for the dark ones (a mark's backing). White stays white, and greys are already the gallery's own.
BRAND_GREY, BRAND_DARK = '#555555', '#222222'
COLOUR_ATTR = re.compile(r'''((?:style|fill|stroke|stop-color|color)=["'])([^"']*)''', re.I)
HEX = re.compile(r'#([0-9A-Fa-f]{6}|[0-9A-Fa-f]{3})\b')
# Dark bands: some designs mix their own near-black from the text colour; in the gallery every dark band is the text black
DARK_MIX = re.compile(r'color-mix\(in srgb,\s*var\(--c-text\)\s*(?:8[5-9]|9\d)%,\s*var\(--c-bg\)\)')


def one_grey(m):
    h = m.group(1)
    if len(h) == 3:
        h = ''.join(x * 2 for x in h)
    r, g, b = (int(h[i:i + 2], 16) for i in (0, 2, 4))
    if r == g == b:
        return m.group(0)
    lum = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255
    return '#FFFFFF' if lum > 0.85 else BRAND_DARK if lum < 0.2 else BRAND_GREY


def same_greys(folder):
    """The gallery page in folder with every brand colour as the same grey and every dark band the same black."""
    for f in glob.glob(os.path.join(folder, '*.html')):
        s = open(f, encoding='utf-8').read()
        t = COLOUR_ATTR.sub(lambda m: m.group(1) + HEX.sub(one_grey, m.group(2)), s)
        t = DARK_MIX.sub('var(--c-text)', t)
        if t != s:
            open(f, 'w', encoding='utf-8').write(t)


def demo_configs():
    """The demos (sites/demos), the user's draft left out."""
    out = []
    for f in sorted(glob.glob('sites/demos/*.json')):
        try:
            c = json.load(open(f, encoding='utf-8'))
        except ValueError:
            continue
        if c.get('id') != 'vantro-kz-app':  # the user's draft is not a demo
            out.append(c)
    return out


COLOUR_LOOKS = {'neutral', 'brand', 'provider'}  # looks that only recolour; others (Google Play, App Store) change the layout


def merge(a, b):
    """A variation option's overrides on a config, as build/export-review-site.php merges them (objects merge, lists replace)."""
    out = dict(a)
    for k, x in b.items():
        out[k] = merge(out[k], x) if isinstance(x, dict) and isinstance(out.get(k), dict) and x else x
    return out


def build_in_gallery_style(c, name, pins=None, option=None):
    """Build demo config c in the gallery style as dist/review/<name>/ (one combination, no review bar), with one
    variation option's overrides and optional design pins {section: design} or {page: {section: design}}."""
    if option:
        c = merge(c, {k: x for k, x in option.items() if k != '_with'})
    cfg = {k: x for k, x in c.items() if k not in ('variations', 'variation_labels', 'look_variant', 'style', 'tokens')}
    if cfg.get('look') in COLOUR_LOOKS:
        del cfg['look']
    cfg['id'] = name
    cfg['preset'] = GALLERY_PRESET
    cfg['tokens'] = GALLERY_STYLE
    if pins:
        merged = dict(cfg.get('variants') or {})
        for k, x in pins.items():
            merged[k] = dict(merged.get(k) or {}, **x) if isinstance(x, dict) else x
        cfg['variants'] = merged
        cfg['pins_over_angle'] = True
    tmp = os.path.join('dist', 'qa', name + '.json')
    os.makedirs(os.path.dirname(tmp), exist_ok=True)
    json.dump(cfg, open(tmp, 'w', encoding='utf-8'), ensure_ascii=False)
    env = dict(os.environ, SX_REVIEW_OUT='dist/review')  # pages only: no review bar, colour copies or wrapper page
    r = subprocess.run([os.environ.get('PHP', 'php'), 'build/export-review-site.php', tmp], capture_output=True, text=True, encoding='utf-8', env=env)
    os.remove(tmp)
    if r.returncode != 0:
        print(f'{name}: build failed: {(r.stderr or r.stdout).strip()[-300:]}', file=sys.stderr)
        return False
    same_greys(os.path.join('dist', 'review', name))
    return True


def showcase(b, v, where):
    """Build a page that shows design v of block b: a demo of a type whose section allows it, in the gallery style,
    with that design pinned (also over the angle's list). Returns the page folder, or None when no demo can show it."""
    demos = {}
    for c in demo_configs():
        demos.setdefault(c.get('type'), []).append(c)
    name = f'{SHOW}{b}--{v}'
    for t, pid, sid in where:
        for c in demos.get(t, []):
            pins = {sid: v} if pid is None else {pid: {sid: v}}  # one-page types: {section: design}; multi-page: per page
            if not build_in_gallery_style(c, name, pins):
                continue
            before = set(found)
            scan(pages(f'dist/review/{name}/*.html'))
            if (b, v) in found:
                return name
            for k in set(found) - before:  # this page is discarded, so the other designs it showed cannot point at it
                del found[k]
            shutil.rmtree(f'dist/review/{name}', ignore_errors=True)
    return None


for old in glob.glob(f'dist/review/{SHOW}*'):  # the gallery-style pages are rebuilt on every run, so they follow the engine
    shutil.rmtree(old, ignore_errors=True) if os.path.isdir(old) else os.remove(old)
for c in demo_configs():  # every demo once in the gallery style: most designs are found here
    build_in_gallery_style(c, f"{SHOW}{c['id']}")
scan(pages(f'dist/review/{SHOW}*/*.html'))
for c in demo_configs():  # then each variation option on its own (colour options aside); a page adding no design is dropped
    for axis, options in (c.get('variations') or {}).items():
        if {'neutral', 'brand'} <= set(options):
            continue
        for o, over in list(options.items())[1:]:
            name = f"{SHOW}{c['id']}--{axis}-{o}"
            before = set(found)
            if build_in_gallery_style(c, name, option=over):
                scan(pages(f'dist/review/{name}/*.html'))
            if set(found) == before:
                shutil.rmtree(f'dist/review/{name}', ignore_errors=True)
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
