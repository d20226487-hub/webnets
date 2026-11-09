#!/bin/sh
# Docker only: copy the project from the host mount (/src) into the container's own disk (/app).
# PHP reads thousands of small files per build, and that is ~20x slower over a Windows/macOS bind mount.
# ai/, markets/, blueprints/ and dist/ stay on the host (symlinks), so settings and market edits and rebuilt pages land in your project folder.
set -e
SRC="${SX_SRC:-/src}"
mkdir -p /app
rsync -a --delete --exclude /dist --exclude /ai --exclude /markets --exclude /blueprints --exclude /data --exclude /.git --exclude /node_modules "$SRC"/ /app/
mkdir -p "$SRC/dist"
[ -L /app/dist ] || { rm -rf /app/dist; ln -s "$SRC/dist" /app/dist; }
[ -L /app/ai ]   || { rm -rf /app/ai;   ln -s "$SRC/ai"   /app/ai; }
[ -L /app/blueprints ] || { rm -rf /app/blueprints; ln -s "$SRC/blueprints" /app/blueprints; } # edited in the generator (Site types)
[ -L /app/markets ] || { rm -rf /app/markets; ln -s "$SRC/markets" /app/markets; } # edited in the generator (Markets)
mkdir -p "$SRC/data"
[ -L /app/data ] || { rm -rf /app/data; ln -s "$SRC/data" /app/data; } # generator database and site configs
