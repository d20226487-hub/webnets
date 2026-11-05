#!/bin/sh
# Docker entrypoint: sync the code, build the gallery index if missing, serve dist/ (+ /settings).
set -e
sh /src/build/docker-sync.sh
cd /app
[ -f dist/index.html ] || python3 build/gallery.py --local
echo "Gallery ready: http://localhost:${SX_PORT:-8090}/  (settings: /settings)"
exec php -S 0.0.0.0:8080 -t dist build/local-router.php
