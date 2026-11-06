#!/bin/sh
# Self-signed cert for the Traefik -> Caddy hop. Traefik never validates it (insecure-backend), so CN is cosmetic.
# Uses local openssl if present, otherwise a throwaway alpine container.
cd "$(dirname "$0")/../.." || exit 1
export MSYS_NO_PATHCONV=1   # Git Bash on Windows: keep "/CN=..." from being rewritten as a path
set -e
mkdir -p caddy-certs
CN="${1:-website-gallery.mrba-stage1.xyz}"
ARGS="req -x509 -nodes -days 3650 -newkey rsa:2048 -keyout caddy-certs/selfsigned.key -out caddy-certs/selfsigned.crt -subj /CN=$CN"
if command -v openssl >/dev/null 2>&1; then
  openssl $ARGS
else
  docker run --rm -v "$PWD/caddy-certs:/work/caddy-certs" -w /work alpine/openssl $ARGS
fi
chmod 644 caddy-certs/selfsigned.crt
# Caddy runs as root in caddy:2-alpine, so 600 still lets it read the key.
chmod 600 caddy-certs/selfsigned.key
echo "Wrote caddy-certs/selfsigned.crt and .key (CN=$CN)"
