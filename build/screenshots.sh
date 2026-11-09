#!/bin/sh
# Docker / Linux twin of build/screenshots.ps1: gallery screenshots of every listed demo's home page (desktop 1280x800
# at half size, phone 375px in a frame), from dist/sites/<id>/index.html. Needs chromium (CHROME=... to override).
# Usage: sh build/screenshots.sh [demo-id ...]   (no ids = every demo linked from docs/types/*.md)
set -e
cd "$(dirname "$0")/.."
CHROME="${CHROME:-chromium}"
src=dist/gallery/src; shots=dist/gallery/shots
mkdir -p "$src" "$shots"
ids="$*"
[ -n "$ids" ] || ids=$(grep -oh 'sites/demos/[a-z0-9-]*\.json)' docs/types/*.md | sed 's#sites/demos/##; s#\.json)##' | sort -u)
inject='<style>[class*="agegate"],[class*="age-gate"],[role="dialog"][id^="age-"]{display:none!important}html,body{overflow:visible!important}</style>'
for id in $ids; do
  [ -f "dist/sites/$id/index.html" ] || { echo "skip $id: no dist/sites/$id/index.html"; continue; }
  sed "s|</head>|$inject</head>|" "dist/sites/$id/index.html" > "$src/$id.html"
  printf '<!doctype html><html><head><meta charset="utf-8"><style>html,body{margin:0;background:#fff}iframe{display:block;width:375px;height:780px;border:0}</style></head><body><iframe src="%s.html"></iframe></body></html>' "$id" > "$src/$id-phone.html"
  base="file://$(pwd)/$src"
  "$CHROME" --headless=new --no-sandbox --disable-dev-shm-usage --disable-gpu --hide-scrollbars --allow-file-access-from-files --virtual-time-budget=5000 --force-device-scale-factor=0.5 --window-size=1280,800 --screenshot="$shots/$id-d.png" "$base/$id.html" >/dev/null 2>&1
  "$CHROME" --headless=new --no-sandbox --disable-dev-shm-usage --disable-gpu --hide-scrollbars --allow-file-access-from-files --virtual-time-budget=5000 --force-device-scale-factor=0.8 --window-size=520,780 --screenshot="$shots/$id-m.png" "$base/$id-phone.html" >/dev/null 2>&1
  echo "shot $id"
done
