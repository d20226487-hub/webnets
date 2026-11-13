# WebNets on a small VPS with nginx

For a VPS shared with other tools (tested numbers are for 4 CPU cores):

| | Before | With `docker-compose.vps.yml` |
|---|---|---|
| Market check (96 test builds) | ~2.5 GB peak, ~4 s | ~60 MB peak, ~10 s |
| Screenshots after a build (30 sites) | ~1.3 GB peak, ~31 s | ~0.6 GB peak, ~47 s |
| Container ceiling | none | 1.5 GB RAM (+0.5 GB swap), 2 CPUs |
| Static files | PHP's built-in server | nginx |

CPU (100% = one core), measured on job 45 (30 sites with screenshots) and a market check:

| | Old settings, no cap | New defaults, no cap | VPS settings (2-CPU cap) |
|---|---|---|---|
| Job rebuild: peak / average CPU | 1170% / 360% | 490% / 210% | 210% / 170% |
| Job rebuild: time | 61 s | 82 s | 177 s |
| Market check: peak CPU / time | 400% / 5 s | 130% / 10 s | 120% / 10 s |

Builds, test builds and screenshots also run at lowered priority (`nice 10`), so the other tools on the box win when
they need the CPU. The 2-CPU cap is the hard limit; raise `cpus` to 3 for faster builds if the box has room.

## Steps

1. Put the project on the server, e.g. `/opt/webnets/satellite-system`.
2. Start the container with the VPS limits:
   `docker compose -f docker-compose.yml -f docker-compose.vps.yml up -d --build`
   It listens on `127.0.0.1:8090` only.
3. Create the login: `sudo htpasswd -c /etc/nginx/webnets.htpasswd <user>` (package `apache2-utils`).
4. Copy `webnets.conf` to `/etc/nginx/sites-available/`, set `server_name`, `root` (the project's `dist/` folder) and the
   certificate paths, link it into `sites-enabled/`, then `sudo nginx -t && sudo systemctl reload nginx`.
5. nginx (user `www-data`) must be able to read `dist/`: the container writes it as root with world-readable files;
   if the project folder is not readable, `sudo chmod o+rx` on the folders above `dist/`.

## Tuning

- `GEN_PROBE_PARALLEL` (default 4) and `GEN_SHOT_PARALLEL` (default 2) in `docker-compose.vps.yml`: lower them on a
  very small box (2 and 1: ~60 MB and ~0.45 GB peaks, checks ~2x slower), raise them on a bigger one.
- A swap file on the VPS (1–2 GB) keeps a rare peak from waking the kernel's OOM killer for the other tools.
- Disk: built sites take ~0.6 MB each (`dist/generated`); deleting a job or project removes its sites, brands,
  articles and logs.

## When something does not work

Open `https://<your server>/generator/api/health` (or look at the red box on the Projects page). It lists what is
missing: PHP extensions, folders the generator cannot write to (with the user PHP runs as), the database, python3
and Chromium. An error in the app now shows PHP's own message (file and line) instead of "not valid JSON"; the same
text is in the server log (`docker compose logs gallery`, or the PHP-FPM log without Docker).

Typical fixes:
- **Running in Docker (recommended):** the image has everything. Start it with both compose files as above; the
  container runs as root and writes `data/` and `dist/` in the project folder and the database on its volume.
- **Running PHP directly (php-fpm, no Docker):** install `php8.3-sqlite3 php8.3-mbstring python3 chromium`
  (Debian/Ubuntu names), then let the PHP user write the project's data folders:
  `sudo mkdir -p data dist/generated && sudo chown -R www-data: data dist/generated` (in the project folder), and
  set `SX_GEN_DB` / `SX_GEN_CACHE` in the pool config if the database should live elsewhere. nginx then needs a
  `fastcgi_pass` location for `/generator` and `/settings` instead of the `proxy_pass` ones in `webnets.conf`.
