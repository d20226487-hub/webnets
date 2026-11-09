"""Visual prints of generated sites, for the generator's visual-difference threshold.

  python3 generator/visual.py <site id> [<site id> ...]

For each site: a first-screen screenshot of its home page on desktop (1280x800) and phone (375x667), taken with
headless Chromium at half scale, cut into 8x8 px cells; the print is the average RGB of every cell. The desktop
screenshot is kept as dist/generated/<site>/_thumb.png, the site's thumbnail on the job page. The age gate is
hidden. Prints the result as one JSON line: {site id: "{\"d\": base64, \"p\": base64}"}. Needs Chrome or Chromium
(CHROME=/path/to/chrome, else the usual locations); no Python packages.
"""
import base64, json, os, shutil, struct, subprocess, sys, tempfile, zlib

root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
HIDE = ('<style>[class*="agegate"],[class*="age-gate"],[role="dialog"][id^="age-"],#sx-devtoggle,#sx-skeltoggle,#sx-devview'
        '{display:none!important}html,body{overflow:visible!important}</style>')
SIZES = {'d': (1280, 800), 'p': (375, 667)}
PARALLEL = 6
CELL = 8


def find_chrome():
    for c in [os.environ.get('CHROME'), shutil.which('chromium'), shutil.which('chromium-browser'), shutil.which('google-chrome'),
              r'C:\Program Files\Google\Chrome\Application\chrome.exe', r'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
              '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome']:
        if c and os.path.exists(c):
            return c
    sys.exit('Google Chrome or Chromium not found; set CHROME=/path/to/chrome.')


def cells(path):
    """Average RGB per CELL x CELL block of a PNG (8-bit RGB/RGBA, as Chrome writes them), decoded with zlib only."""
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
    out, n = bytearray(), CELL * CELL
    for cy in range(h // CELL):
        for cx in range(w // CELL):
            r = g = b = 0
            for y in range(cy * CELL, cy * CELL + CELL):
                ln = rows[y]
                for x in range(cx * CELL, cx * CELL + CELL):
                    o = x * bpp
                    r += ln[o]
                    g += ln[o + 1]
                    b += ln[o + 2]
            out += bytes((r // n, g // n, b // n))
    return bytes(out)


def wait(p, timeout=60):
    """Wait for a Chromium process; one that hangs is killed (its screenshot is retried once, then skipped)."""
    try:
        p.wait(timeout=timeout)
    except subprocess.TimeoutExpired:
        p.kill()
        p.wait()


def main(ids):
    chrome = find_chrome()
    work = tempfile.mkdtemp(prefix='gvis-')
    jobs = []
    for sid in ids:
        page = os.path.join(root, 'dist', 'generated', sid, 'index.html')
        if not os.path.isfile(page):
            continue
        src = os.path.join(os.path.dirname(page), '_vis.html')
        open(src, 'w', encoding='utf-8').write(open(page, encoding='utf-8').read().replace('</head>', HIDE + '</head>', 1))
        for k, (w, h) in SIZES.items():
            png = os.path.join(work, '%s-%s.png' % (sid, k))
            cmd = [chrome, '--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu', '--hide-scrollbars',
                   '--no-first-run', '--user-data-dir=' + os.path.join(work, 'p-%s-%s' % (sid, k)), '--allow-file-access-from-files',
                   '--virtual-time-budget=4000', '--force-device-scale-factor=0.5', '--window-size=%d,%d' % (w, h),
                   '--screenshot=' + png, 'file://' + src.replace('\\', '/')]
            jobs.append((sid, k, png, cmd))
    running = []
    for j in jobs:
        if len(running) >= PARALLEL:
            wait(running.pop(0))
        running.append(subprocess.Popen(j[3], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL))
    for p in running:
        wait(p)
    prints = {}
    for sid, k, png, cmd in jobs:
        if not os.path.exists(png):
            wait(subprocess.Popen(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL))
        if os.path.exists(png):
            prints.setdefault(sid, {})[k] = base64.b64encode(cells(png)).decode()
            if k == 'd':  # the desktop first screen is the site's thumbnail on the job page
                shutil.copyfile(png, os.path.join(root, 'dist', 'generated', sid, '_thumb.png'))
    for sid in ids:
        src = os.path.join(root, 'dist', 'generated', sid, '_vis.html')
        if os.path.exists(src):
            os.remove(src)
    shutil.rmtree(work, ignore_errors=True)
    print(json.dumps({sid: json.dumps(p) for sid, p in prints.items() if len(p) == len(SIZES)}))


if __name__ == '__main__':
    main(sys.argv[1:])
