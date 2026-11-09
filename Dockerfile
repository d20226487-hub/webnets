# Local gallery + AI settings page in one container.
# The image holds only the runtime (PHP 8.3 + Python 3 + rsync, Chromium for the generator's visual checks). docker-compose.yml mounts the project at /src;
# build/docker-start.sh copies it to /app on the container's disk (fast builds) and links ai/ + dist/ back to the host.
# Debian base on purpose: the slug code uses glibc iconv transliteration (Alpine/musl gives different results).
FROM php:8.3-cli-bookworm

RUN apt-get update \
 && apt-get install -y --no-install-recommends python3 rsync chromium fonts-dejavu-core fonts-liberation \
 && rm -rf /var/lib/apt/lists/*

ENV PHP=php \
    CHROME=/usr/bin/chromium \
    PYTHONIOENCODING=utf-8 \
    PHP_CLI_SERVER_WORKERS=4 \
    SX_SRC=/src \
    TZ=UTC

WORKDIR /app
EXPOSE 8080

CMD ["sh", "/src/build/docker-start.sh"]
