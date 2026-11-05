#!/bin/sh
# Local gallery with every demo and its review pages (no publishing needed).
# Rebuild first if needed: python build/gallery.py --local
cd "$(dirname "$0")/.." || exit 1
( sleep 1; (xdg-open http://localhost:8080/ || open http://localhost:8080/) >/dev/null 2>&1 ) &
php -S localhost:8080 -t dist build/local-router.php
