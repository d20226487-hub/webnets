#!/bin/sh
# Docker only: copy the project from the host mount (/src) into the container's own disk (/app).
# PHP reads thousands of small files per build, and that is ~20x slower over a Windows/macOS bind mount.
# ai/ and dist/ stay on the host (symlinks), so settings edits and rebuilt pages land in your project folder.
set -e
SRC="${SX_SRC:-/src}"
mkdir -p /app
rsync -a --delete --exclude /dist --exclude /ai --exclude /data --exclude /.git --exclude /node_modules "$SRC"/ /app/
mkdir -p "$SRC/dist"
[ -L /app/dist ] || { rm -rf /app/dist; ln -s "$SRC/dist" /app/dist; }
[ -L /app/ai ]   || { rm -rf /app/ai;   ln -s "$SRC/ai"   /app/ai; }
mkdir -p "$SRC/data"
[ -L /app/data ] || { rm -rf /app/data; ln -s "$SRC/data" /app/data; } # generator database and site configs
