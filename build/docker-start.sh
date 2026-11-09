#!/bin/sh
# Docker entrypoint: sync the code, build the gallery index if missing, serve dist/ (+ /settings).
set -e
sh /src/build/docker-sync.sh
cd /app
# the generator's database moved from data/ (a host folder: slow in Docker) to the generator-db volume: copied over once,
# the old file kept next to it as generator.sqlite.before-volume
if [ -n "$SX_GEN_DB" ] && [ ! -f "$SX_GEN_DB" ] && [ -f /src/data/generator.sqlite ]; then
  mkdir -p "$(dirname "$SX_GEN_DB")"
  cp /src/data/generator.sqlite "$SX_GEN_DB"
  mv /src/data/generator.sqlite /src/data/generator.sqlite.before-volume
  echo "Generator database copied to $SX_GEN_DB"
fi
[ -f dist/index.html ] || python3 build/gallery.py --local
echo "Gallery ready: http://localhost:${SX_PORT:-8090}/  (settings: /settings)"
# opcache for the built-in server: without it every request parses the whole library again (~0.4 s each);
# timestamps stay validated, so code synced in by docker-sync.sh is picked up within a second
exec php -d opcache.enable_cli=1 -d opcache.revalidate_freq=1 -d opcache.jit_buffer_size=64M -d opcache.jit=tracing -S 0.0.0.0:8080 -t dist build/local-router.php
