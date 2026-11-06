"""How different are a demo's variations? Visual and code difference, in %, between home-page versions.

For every site type it compares
  - the separate demo sites (A, B, C home pages): the yardstick for "a different site";
  - every pair of versions of each switcher demo (configs with "variations").

Measures per pair (0% = identical, 100% = nothing in common):
  visual     share of the desktop first screen (1280x800) that visibly changes: the screenshot is cut into 8x8 px
             cells (at half scale) and a cell counts when its average colour moves by more than 40 (RGB distance)
  code       mean of css and markup, normalised so trivial rewrites do not count: per-site class prefix, whitespace,
             declaration order inside a rule, attribute order inside a tag
    css      share of the page's CSS rules that differ (one rule = one item)
    markup   share of 6-tag sequences of the HTML (tags and attributes, text removed) that differ
  skeleton   share of 6-tag sequences of tag names only (no attributes): the DOM tree, which code dialects do not change
  structure  share of blocks (block:layout, from the developer-panel data) that differ
  text       share of 3-word sequences of the visible text that differ
Review-only additions (switcher bar, developer panel, phone toggle) and the age gate are left out.

"Distinct versions" = the largest set of versions in which every pair differs by at least the threshold (greedy).

Usage (from the satellite-system folder; needs Google Chrome or Chromium, no Python packages):
  python build/variation-diff.py                     every site type
  python build/variation-diff.py casinos landing     some types, or demo ids (casinos-b-xx)
  python build/variation-diff.py --threshold 30      "distinct" means >= 30% (default 20)
Rebuild the review pages first (build/rebuild-reviews.py). Screenshots are cached in dist/qa/variation-diff/ and
re-taken only when a page is newer. Writes dist/qa/variation-diff.json and dist/qa/variation-diff.md.
"""
import json, os, re, sys, glob, html, zlib, struct, subprocess, itertools, statistics, shutil, hashlib, tempfile
from collections import Counter

root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
args = sys.argv[1:]
threshold = 20
if '--threshold' in args:
    i = args.index('--threshold')
    threshold = int(args[i + 1])
    del args[i:i + 2]
only = set(args)
work = os.path.join(root, 'dist', 'qa', 'variation-diff')
os.makedirs(work, exist_ok=True)
profiles = tempfile.mkdtemp(prefix='sxvd-')  # throwaway Chrome profiles, short path
HIDE = ('<style>.vbar,#sx-devtoggle,#sx-skeltoggle,#sx-devview,[class*="agegate"],[class*="age-gate"],[role="dialog"][id^="age-"]{display:none!important}'
        'html,body{overflow:visible!important}</style>')
PARALLEL = 6


def find_chrome():
    for c in [os.environ.get('CHROME'), r'C:\Program Files\Google\Chrome\Application\chrome.exe',
              r'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
              '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
              shutil.which('google-chrome'), shutil.which('chromium'), shutil.which('chromium-browser'), shutil.which('chrome')]:
        if c and os.path.exists(c):
            return c
    sys.exit('Google Chrome or Chromium not found; set CHROME=/path/to/chrome.')


CHROME = find_chrome()


def demos_by_type():
    """Demo ids per site type, as linked from docs/types/<type>.md (the gallery's source)."""
    out = {}
    for f in sorted(glob.glob(os.path.join(root, 'docs', 'types', '*.md'))):
        ids = []
        for m in re.finditer(r'sites/demos/([a-z0-9-]+)\.json\)', open(f, encoding='utf-8').read()):
            if m.group(1) not in ids and os.path.isfile(os.path.join(root, 'sites', 'demos', m.group(1) + '.json')):
                ids.append(m.group(1))
        out[os.path.basename(f)[:-3]] = ids
    return out


def cfg_of(demo):
    return json.load(open(os.path.join(root, 'sites', 'demos', demo + '.json'), encoding='utf-8'))


def homes(demo):
    """Home page of every combination: <prefix>index.html (multi-page) or every file (one-page variation sets)."""
    d = os.path.join(root, 'dist', 'review', demo)
    cfg = cfg_of(demo)
    if not cfg.get('variations'):
        return [os.path.join(d, 'index.html')]
    if 'pages' in json.load(open(os.path.join(root, 'blueprints', cfg['type'] + '.json'), encoding='utf-8')):
        return sorted(glob.glob(os.path.join(d, '*index.html')))
    return sorted(p for p in glob.glob(os.path.join(d, '*.html')) if not os.path.basename(p).startswith('_s'))


def png_cells(path, cell=8):
    """Average RGB per cell of a PNG (8-bit RGB/RGBA, as Chrome writes them), decoded with zlib only."""
    data = open(path, 'rb').read()
    pos, idat = 8, b''
    while pos < len(data):
        ln, typ = struct.unpack('>I4s', data[pos:pos + 8])
        body = data[pos + 8:pos + 8 + ln]
        if typ == b'IHDR':
            w, h, _, ctype = struct.unpack('>IIBB', body[:10])
        elif typ == b'IDAT':
            idat += body
        pos += 12 + ln
    bpp = {2: 3, 6: 4}[ctype]
    raw, stride = zlib.decompress(idat), w * bpp
    rows, prev, i = [], bytearray(stride), 0
    for _ in range(h):
        f = raw[i]
        line = bytearray(raw[i + 1:i + 1 + stride])
        i += 1 + stride
        if f == 1:
            for x in range(bpp, stride):
                line[x] = (line[x] + line[x - bpp]) & 255
        elif f == 2:
            for x in range(stride):
                line[x] = (line[x] + prev[x]) & 255
        elif f == 3:
            for x in range(stride):
                line[x] = (line[x] + ((line[x - bpp] if x >= bpp else 0) + prev[x]) // 2) & 255
        elif f == 4:
            for x in range(stride):
                a = line[x - bpp] if x >= bpp else 0
                b, c = prev[x], (prev[x - bpp] if x >= bpp else 0)
                p = a + b - c
                pa, pb, pc = abs(p - a), abs(p - b), abs(p - c)
                line[x] = (line[x] + (a if pa <= pb and pa <= pc else b if pb <= pc else c)) & 255
        rows.append(line)
        prev = line
    cells, n = [], cell * cell
    for cy in range(h // cell):
        for cx in range(w // cell):
            r = g = b = 0
            for y in range(cy * cell, cy * cell + cell):
                ln = rows[y]
                for x in range(cx * cell, cx * cell + cell):
                    o = x * bpp
                    r += ln[o]; g += ln[o + 1]; b += ln[o + 2]
            cells.append((r / n, g / n, b / n))
    return cells


def page_parts(path):
    """(blocks, text shingles, CSS rules, markup shingles) of a review page, without review-only additions."""
    s = open(path, encoding='utf-8').read()
    m = re.search(r'<script[^>]*id="sx-devdata"[^>]*>(.*?)</script>', s, re.S)
    dev = json.loads(m.group(1)) if m else {}
    blocks = sorted(f"{it['b']}:{it['v']}" for it in dev.get('items', []))
    c = re.sub(r'<script[^>]*id="sx-devdata".*?</script>', '', s, flags=re.S)
    c = re.sub(r'<div class="vbar".*?</nav></div></div>', '', c, flags=re.S)
    c = c.split('<button type="button" id="sx-skeltoggle"')[0].split('<button type="button" id="sx-devtoggle"')[0]
    t = re.sub(r'<script.*?</script>|<style.*?</style>|<template.*?</template>', ' ', c, flags=re.S)
    words = html.unescape(re.sub(r'<[^>]+>', ' ', t)).lower().split()
    text = set(tuple(words[i:i + 3]) for i in range(max(0, len(words) - 2)))
    if dev.get('prefix'):
        c = c.replace(dev['prefix'], 'P-')
    css = ' '.join(re.findall(r'<style[^>]*>(.*?)</style>', c, re.S))
    rules = set()
    for sel, body in re.findall(r'([^{}]+)\{([^{}]*)\}', re.sub(r'@media[^{]+\{', '', css)):
        decls = sorted(x.strip() for x in re.split(r';(?![^(]*\))', body) if x.strip())  # declaration order does not count
        rules.add(re.sub(r'\s+', ' ', sel.strip()) + '{' + ';'.join(decls) + '}')
    tags = re.findall(r'<(/?)([a-zA-Z][\w:-]*)([^>]*)>', re.sub(r'<style.*?</style>|<script.*?</script>', ' ', c, flags=re.S))
    toks = ['<' + sl + n + ' ' + ' '.join(sorted(re.sub(r'"[^"]{40,}"', '"…"', a) for a in re.findall(r'[^\s=]+(?:="[^"]*")?', at))) + '>'
            for sl, n, at in tags]  # attribute order does not count
    markup = set(tuple(toks[i:i + 6]) for i in range(max(0, len(toks) - 5)))
    names = [sl + n.lower() for sl, n, _ in tags]
    skeleton = set(tuple(names[i:i + 6]) for i in range(max(0, len(names) - 5)))
    return blocks, text, rules, markup, skeleton


def chrome_cmd(png, src, prof):
    return [CHROME, '--headless=new', '--user-data-dir=' + prof, '--no-first-run', '--disable-gpu', '--hide-scrollbars',
            '--allow-file-access-from-files', '--virtual-time-budget=4000', '--force-device-scale-factor=0.5',
            '--window-size=1280,800', '--screenshot=' + png, 'file:///' + src.replace('\\', '/').lstrip('/')]


def shoot(pages):
    """First-screen screenshot per page (cached while the page is unchanged). Returns {page: png}."""
    procs, out = [], {}
    for p in pages:
        # short names: deep project folders hit the Windows 260-character path limit
        png = os.path.join(work, hashlib.md5(os.path.relpath(p, root).replace(os.sep, '/').encode()).hexdigest()[:12] + '.png')
        if os.path.exists(png) and os.path.getmtime(png) >= os.path.getmtime(p):
            out[p] = (png, None)
            continue
        # copy next to the page (relative assets resolve) with a short name (Windows 260-character path limit)
        src = os.path.join(os.path.dirname(p), '_s%d.html' % len(out))
        open(src, 'w', encoding='utf-8').write(open(p, encoding='utf-8').read().replace('</head>', HIDE + '</head>', 1))
        out[p] = (png, src)
        if len(procs) >= PARALLEL:
            procs[-PARALLEL].wait()
        # a profile per screenshot: headless Chrome's helpers keep a profile locked for a while after it exits
        procs.append(subprocess.Popen(chrome_cmd(png, src, os.path.join(profiles, str(len(procs)))),
                                      stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL))
    for x in procs:
        try:
            x.wait(timeout=120)
        except subprocess.TimeoutExpired:
            x.kill()
    for png, src in out.values():
        if src and not os.path.exists(png):  # retry one at a time
            subprocess.run(chrome_cmd(png, src, os.path.join(profiles, 'r' + os.path.basename(src))), stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=120)
        if src:
            os.remove(src)
    shutil.rmtree(profiles, ignore_errors=True)
    missing = [p for p, (png, _) in out.items() if not os.path.exists(png)]
    if missing:
        sys.exit('No screenshot for: ' + ', '.join(missing))
    return {p: v[0] for p, v in out.items()}


def dist_sets(a, b):
    return 1 - (len(a & b) / len(a | b) if a | b else 1)


def compare(x, y):
    (ca, ba, ta, ra, ma, ka), (cb, bb, tb, rb, mb, kb) = x, y
    vis = sum(1 for p, q in zip(ca, cb) if sum((p[i] - q[i]) ** 2 for i in range(3)) ** .5 > 40) / max(1, min(len(ca), len(cb)))
    A, B = Counter(ba), Counter(bb)
    st = 1 - (sum((A & B).values()) / sum((A | B).values()) if A | B else 1)
    css, mk = dist_sets(ra, rb), dist_sets(ma, mb)
    return {'visual': vis, 'code': (css + mk) / 2, 'css': css, 'markup': mk, 'skeleton': dist_sets(ka, kb), 'structure': st, 'text': dist_sets(ta, tb)}


def distinct(n, ok):
    best = []
    for start in range(n):
        s = [start]
        for j in range(n):
            if j not in s and all(ok(j, k) for k in s):
                s.append(j)
        best = max(best, s, key=len)
    return len(best)


KEYS = ('visual', 'code', 'css', 'markup', 'skeleton', 'structure', 'text')
pct = lambda v: round(100 * v)
report = {'threshold': threshold, 'types': {}}
rows = []
for t, ids in demos_by_type().items():
    if only and t not in only and not (only & set(ids)):
        continue
    mains = [os.path.join(root, 'dist', 'review', d, 'index.html') for d in ids]
    mains = [p for p in mains if os.path.exists(p)]
    var = [d for d in ids if cfg_of(d).get('variations') and (not only or t in only or d in only)]
    pages = sorted(set(mains + [h for d in var for h in homes(d)]))
    if not pages:
        continue
    print(f'{t}: {len(pages)} pages', flush=True)
    shots = shoot(pages)
    feats = {p: (png_cells(shots[p]), *page_parts(p)) for p in pages}
    rec = {'sites': None, 'demos': {}}
    pairs = [compare(feats[a], feats[b]) for a, b in itertools.combinations(mains, 2)]
    if pairs:
        rec['sites'] = {k: pct(statistics.mean(p[k] for p in pairs)) for k in KEYS}
    for d in var:
        hs = homes(d)
        M = {(i, j): compare(feats[hs[i]], feats[hs[j]]) for i, j in itertools.combinations(range(len(hs)), 2)}
        if not M:
            continue
        r = {'versions': len(hs),
             'mean': {k: pct(statistics.mean(v[k] for v in M.values())) for k in KEYS},
             'range': {k: [pct(min(v[k] for v in M.values())), pct(max(v[k] for v in M.values()))] for k in ('visual', 'code')}}
        for k in ('visual', 'code'):
            r[f'distinct_{k}'] = distinct(len(hs), lambda a, b, k=k: M[(min(a, b), max(a, b))][k] * 100 >= threshold)
        rec['demos'][d] = r
        s = rec['sites'] or {}
        rows.append(f"| {t} | {s.get('visual', '–')}% · {s.get('code', '–')}% · {s.get('skeleton', '–')}% | {d} | {r['versions']} | "
                    f"{r['mean']['visual']}% · {r['mean']['code']}% | {r['range']['visual'][0]}–{r['range']['visual'][1]}% · "
                    f"{r['range']['code'][0]}–{r['range']['code'][1]}% | {r['distinct_visual']} · {r['distinct_code']} |")
    report['types'][t] = rec

json.dump(report, open(os.path.join(root, 'dist', 'qa', 'variation-diff.json'), 'w', encoding='utf-8'), indent=1)
md = ['# Variation difference', '',
      'Visual = share of the desktop first screen that visibly changes. Code = HTML markup and CSS rules that differ (class prefix, whitespace, declaration and attribute order normalised). Skeleton = DOM tree (tag names only).',
      f'Distinct = largest set of versions in which every pair differs by at least {threshold}%.', '',
      '| Type | Separate sites: visual · code · skeleton | Switcher demo | Versions | Mean: visual · code | Range: visual · code | Distinct: visual · code |',
      '|---|---|---|---|---|---|---|'] + rows
open(os.path.join(root, 'dist', 'qa', 'variation-diff.md'), 'w', encoding='utf-8', newline='\n').write('\n'.join(md) + '\n')
print('\n'.join(md))
print('\nWrote dist/qa/variation-diff.json and dist/qa/variation-diff.md')
