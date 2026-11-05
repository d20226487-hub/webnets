"""Rebuild every gallery demo's review pages (dist/review/<id>/), then the local gallery (dist/index.html).
   Demos are the configs linked from docs/types/*.md. One-page types with "variations" use build/variations.php,
   everything else build/export-review-site.php. Used by the local settings page after the AI settings change.
   Usage: python build/rebuild-reviews.py [demo-id ...]   (no ids = all demos)"""
import glob, json, os, re, subprocess, sys, html, time

root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
os.chdir(root)
ONE_PAGE = {'landing', 'app', 'promo', 'vendor'}
php = os.environ.get('PHP', 'php')
if os.environ.get('SX_SRC') and os.path.isfile('build/docker-sync.sh'):
    subprocess.run(['sh', 'build/docker-sync.sh'], check=True)  # Docker: pick up code edited on the host

demos = []
for f in sorted(glob.glob('docs/types/*.md')):
    for m in re.finditer(r'sites/demos/([a-z0-9-]+)\.json\)', open(f, encoding='utf-8').read()):
        if m.group(1) not in demos:
            demos.append(m.group(1))
want = sys.argv[1:]
if want:
    demos = [d for d in demos if d in want]

start = time.time()
failed = []
for i, d in enumerate(demos, 1):
    cfg = json.load(open(f'sites/demos/{d}.json', encoding='utf-8'))
    main = f"dist/review/{cfg['id']}.html"
    title = None
    if os.path.exists(main):  # keep the review page's title
        t = re.search(r'<title>(.*?)</title>', open(main, encoding='utf-8').read(), re.S)
        title = html.unescape(t.group(1)) if t else None
    if cfg.get('variations') and cfg.get('type') in ONE_PAGE:
        cmd = [php, 'build/variations.php', f'sites/demos/{d}.json']
    else:
        cmd = [php, 'build/export-review-site.php', f'sites/demos/{d}.json'] + ([title] if title else [])
    r = subprocess.run(cmd, capture_output=True, text=True, encoding='utf-8')
    ok = r.returncode == 0
    if not ok:
        failed.append(d)
    print(f'[{i}/{len(demos)}] {d}: ' + ('ok' if ok else 'FAILED ' + r.stderr.strip()[-300:]), flush=True)

r = subprocess.run([sys.executable, 'build/gallery.py', '--local'], capture_output=True, text=True, encoding='utf-8')
print('local gallery: ' + (r.stdout.strip().splitlines() or ['?'])[-1], flush=True)
print(f'DONE in {time.time() - start:.0f}s' + (f', failed: {", ".join(failed)}' if failed else ''), flush=True)
