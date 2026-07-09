# Deploying pulse-laravel to Render

A short walkthrough to put this API online on [Render](https://render.com)'s
free tier. It mirrors how `pulse-api` and `pulse-rails` are deployed, with one
difference: Render has no native PHP runtime, so this service is built from a
**Dockerfile**.

> Heads up: the free tier sleeps after inactivity (first request ~30 s) and its
> disk is reset on each deploy — that's why the container re-seeds demo data on
> every boot.

## What's already set up

- `Dockerfile` — a small `php:8.4-cli` image with the PHP extensions Laravel
  needs (`pdo_sqlite`, `mbstring`, `bcmath`) and Composer. On boot it migrates,
  seeds demo data, caches config, and serves on the `PORT` Render provides.
- `.dockerignore` — keeps local/dev state (`.env`, `vendor`, the local SQLite
  file) out of the build.
- `render.yaml` — a Blueprint describing the web service (Docker runtime and
  the env vars below).

## Steps

1. Push this repo to GitHub (already done: `Jorgepele/pulse-laravel`).
2. Generate an app key locally and copy the output (starts with `base64:`):
   ```bash
   php artisan key:generate --show
   ```
3. In Render: **New > Blueprint**, connect the repo. Render reads `render.yaml`
   and builds the Dockerfile.
4. Set the one secret it asks for:
   - **`APP_KEY`** — paste the `base64:...` value from step 2. Laravel needs a
     valid key or it refuses to boot, and Render can't generate this format.
5. Create the service and wait for the first build/deploy (the Docker build
   takes a few minutes the first time).
6. Verify:
   - `GET /api/boards` returns the seeded board.
   - `POST /api/login` with `demo@pulse.dev` / `demo12345` returns a token.

## Notes

- **Web server:** the container uses `php artisan serve` (PHP's built-in
  server). It's single-threaded — fine for a demo on the free tier, not for real
  traffic. Swapping in nginx + php-fpm or FrankenPHP would be the next step for a
  production deploy.
- **HTTPS:** Render terminates TLS at the edge; the app itself does not force
  SSL, which keeps the health check and local runs simple.
- Once it's live, add the URL to this repo's README (as `pulse-api` does).
