# Traefik-fronted deployment

Slots the website gallery into the shared Traefik proxy that already handles TLS, the VPN-only IP allow-list
and routing for Drop Sherlock and AI Content Machine on the same VPS. This mode exposes **no host ports**:
only Traefik is reachable from outside.

```
client → Traefik (TLS + allow-vpn-only) → Caddy (basicauth + headers) → gallery (php -S :8080)
```

Same pattern as Drop Sherlock: Caddy is the internal proxy on the `traefik_proxy` network, serving HTTPS with a
self-signed cert that Traefik accepts through `insecure-backend`. Basic auth stays on as defense in depth, which
matters here because `/settings/` edits `ai/*.md` and starts rebuilds.

---

## Prerequisites

- A working Traefik instance on the VPS with:
  - `websecure` entryPoint terminating TLS for public clients;
  - `allow-vpn-only` middleware (IP allow-list);
  - `insecure-backend` serversTransport (`insecureSkipVerify: true`);
  - an external Docker network named `traefik_proxy` that Traefik joins
    (create once with `docker network create traefik_proxy`).
- DNS for the subdomain pointed at the VPS.
- Docker Compose v2.20+ (the override uses `!reset`).

## Steps

### 1. Get the code

```bash
git clone https://github.com/d20226487-hub/website-gallery.git /opt/website-gallery
cd /opt/website-gallery
```

### 2. Generate the self-signed cert for the Traefik → Caddy hop

```bash
sh deploy/traefik/gen-cert.sh website-gallery.mrba-stage1.xyz
```

This writes `caddy-certs/selfsigned.crt` and `.key` (10-year expiry). It uses local `openssl`, or an `alpine/openssl`
container if openssl isn't installed. Traefik never validates this cert, so the CN is cosmetic.

### 3. Configure `.env`

```bash
cp .env.example .env
docker run --rm caddy:2-alpine caddy hash-password --plaintext 'YOUR-PASS'
```

Paste the hash into `BASIC_AUTH_PASSWORD_HASH`, escaping every `$` as `$$`, and set `SITE_HOST` to the public
hostname. `.env` and `caddy-certs/` are git-ignored.

### 4. Bring the stack up with the Traefik override

```bash
docker compose -f docker-compose.yml -f docker-compose.traefik.yml up -d --build
```

The override:

- removes the gallery's host port (`127.0.0.1:8090`), so the gallery is reachable only from Caddy;
- adds `website-gallery-caddy` (Caddy 2, `Caddyfile.traefik`, certs mounted at `/certs`);
- puts Caddy on the external `traefik_proxy` network so Traefik reaches `https://website-gallery-caddy:443`.

### 5. Add the router to Traefik

Append [`traefik-router.yml`](./traefik-router.yml) to your existing dynamic Traefik config (the file that
already defines `allow-vpn-only`, `insecure-backend` and the other tools). Replace the placeholder
`website-gallery.mrba-stage1.xyz` with the real hostname. Traefik picks it up automatically.

### 6. Verify

From inside the VPN:

```bash
curl -u 'admin:YOUR-PASSWORD' -I https://website-gallery.mrba-stage1.xyz
# expected: 200 OK
curl -I https://website-gallery.mrba-stage1.xyz
# expected: 401 (Caddy basic auth)
```

From outside the VPN: 403 Forbidden (allow-vpn-only), or a timeout, depending on your edge config.

Sanity checks on the host:

```bash
docker compose -f docker-compose.yml -f docker-compose.traefik.yml ps
docker logs --tail 50 website-gallery-caddy | grep -i error
docker logs --tail 5 satellite-gallery        # "Gallery ready" + PHP server started
```

---

## Updating

```bash
cd /opt/website-gallery
git pull
docker compose -f docker-compose.yml -f docker-compose.traefik.yml up -d --build
docker compose -f docker-compose.yml -f docker-compose.traefik.yml restart gallery   # re-syncs the code
```

Or rebuild every demo from the settings page ("Rebuild review pages"), which re-syncs the code first.

## Headers set by Caddy

- `X-Frame-Options: SAMEORIGIN` and `frame-ancestors 'self'`: the phone preview loads review pages in a
  same-origin iframe, so `DENY` would break it.
- CSP allows inline CSS/JS and Google Fonts only; every other resource is same-origin.
- `X-Robots-Tag: noindex, nofollow`: these are demo sites with fictional brands.
- HSTS, `nosniff`, `strict-origin-when-cross-origin`, gzip/zstd.

## Going back to the plain local gallery

Drop the override flag: `docker compose up -d` brings back `http://localhost:8090` with no Caddy.

## Renaming the subdomain later

1. **DNS**: repoint the A record.
2. **Traefik router**: `Host(...)` in your copy of `traefik-router.yml`.
3. **`.env`**: `SITE_HOST` (informational only).

The cert CN doesn't need to match.
