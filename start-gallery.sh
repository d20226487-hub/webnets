#!/bin/sh
# Start the local gallery in Docker and open it. Stop with: docker compose down
cd "$(dirname "$0")" || exit 1
docker compose up -d --build || { echo "Docker is not running. Start Docker and try again."; exit 1; }
url="http://localhost:${SX_PORT:-8090}/"
(xdg-open "$url" || open "$url") >/dev/null 2>&1 &
echo "Gallery: $url  (settings: ${url}settings)"
