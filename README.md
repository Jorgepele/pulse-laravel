# Pulse (Laravel port) — a Laravel learning project

[![CI](https://github.com/Jorgepele/pulse-laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/Jorgepele/pulse-laravel/actions/workflows/ci.yml)

> The core of [Pulse](https://github.com/Jorgepele/pulse-api) (a feedback &
> roadmap app — teams post feature requests, users upvote them) ported to
> Laravel, to learn Laravel and see how the MVC pattern maps across frameworks.
> A sibling of the [Rails port](https://github.com/Jorgepele/pulse-rails).
> Work in progress — learning in the open.

> El núcleo de Pulse (app de feedback y hoja de ruta: los equipos publican
> peticiones y los usuarios votan) portado a Laravel, para aprender Laravel y
> ver cómo se traslada el patrón MVC entre frameworks. Hermano del port a Rails.
> En desarrollo.

**Stack:** PHP 8.4 · Laravel 13 (API) · Laravel Sanctum · SQLite

---

## Why this port · Por qué este port

I already built Pulse as a Django REST API and ported it to Rails. Doing the
same core in Laravel is the fastest way to learn Laravel: the domain is
familiar, so I can focus on how Laravel does things — Eloquent, the router,
artisan, Sanctum — and compare it to Django and Rails.

Ya construí Pulse como API REST en Django y lo porté a Rails. Hacer el mismo
núcleo en Laravel es la forma más rápida de aprender Laravel: el dominio ya lo
conozco, así que me centro en *cómo* hace las cosas Laravel (Eloquent, el
router, artisan, Sanctum) y lo comparo con Django y Rails.

## What it does so far · Qué hace por ahora

- **Multi-tenant** domain: **Organization → Board → Post → Vote → Comment**,
  with users joined to organizations through **memberships** (owner/admin/member).
  Signing up creates your personal organization; boards belong to an org and
  their slug is unique per org.
- Auto-generated slug on boards and orgs, a default `open` status on posts, and
  `vote_count` / `comment_count` on each post.
- **Token authentication with Laravel Sanctum**: `register` / `login` / `me`
  endpoints returning a personal access token.
- **Tenant scoping**: a board is visible if it is public, or if you belong to the
  organization that owns it; posts and comments inherit their board's visibility.
  The rule lives in the `Board::scopeVisibleTo` scope, a port of Django's `visible_to`.
- JSON REST API under `/api` to list visible boards, list/create posts,
  **toggle an upvote** (vote once, vote again to remove it), and list/add
  comments. Reads are public; **writes require a token** and are attributed to
  the current user.
- Seed data (`php artisan db:seed`) so the API has something to show.
- Feature tests (13 tests) run in memory against the real routes.

This now covers the core Django domain, including the multi-tenant part
(organizations + memberships). The billing tables from the Django version
aren't ported here.

## Data model · Modelo de datos

The same multi-tenant feedback schema as the Django original, expressed here
with Eloquent models. Every board and post belongs to an organization; users
join organizations through memberships.

```mermaid
erDiagram
    User ||--o{ Organization : owns
    User ||--o{ Membership : "joins via"
    Organization ||--o{ Membership : has
    Organization ||--o{ Board : owns
    Board ||--o{ Post : contains
    User ||--o{ Post : authors
    Post ||--o{ Vote : receives
    User ||--o{ Vote : casts
    Post ||--o{ Comment : has
    User ||--o{ Comment : writes

    Organization {
        string name
        string slug
    }
    Membership {
        string role "owner / admin / member"
    }
    Board {
        string name
        bool is_public
    }
    Post {
        string title
        string status "open / planned / done ..."
    }
```

## How MVC maps across frameworks · Cómo se traslada el MVC

| Concept | Django | Rails | Laravel (this repo) |
|--------|--------|-------|---------------------|
| Model | `models.py` | Active Record | Eloquent (`app/Models`) |
| Schema change | migrations | migrations | migrations (`database/migrations`) |
| Controller | DRF views | `app/controllers` | `app/Http/Controllers/Api` |
| URL routing | `urls.py` | `routes.rb` | `routes/api.php` |
| Auto slug | `save()` override | `before_validation` | `creating` model event |
| Token auth | DRF tokens | token + concern | Sanctum (`Bearer` token) |

Note: Django and Rails send the token as `Authorization: Token <token>`;
Laravel Sanctum uses `Authorization: Bearer <token>`.

## Run it locally · Cómo ejecutarlo

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed     # optional --seed: demo users, board, posts
php artisan serve
```

API at `http://127.0.0.1:8000/api/`. After seeding you can log in with
`demo@pulse.dev` / `demo12345`.

## Main endpoints

Writes require an `Authorization: Bearer <token>` header, where the token comes
from `register` or `login`.

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/api/register` | — | Create an account, returns a token |
| `POST` | `/api/login` | — | Exchange email + password for a token |
| `GET`  | `/api/me` | token | The current user |
| `GET`  | `/api/boards` | — | List boards visible to the caller |
| `GET`  | `/api/boards/{id}` | — | A single board |
| `POST` | `/api/boards` | token | Create a board under your organization |
| `GET`  | `/api/posts?board_id={id}&status={status}` | — | List posts, filtered by board and/or status |
| `POST` | `/api/posts` | token | Create a feature request |
| `POST` | `/api/posts/{id}/vote` | token | Toggle your vote |
| `GET`  | `/api/comments?post={id}` | — | List comments on a post |
| `POST` | `/api/comments` | token | Add a comment |

## Tests · Estilo

```bash
php artisan test        # PHPUnit feature tests
vendor/bin/pint --test  # code style (Laravel Pint)
```

Both run on every push via GitHub Actions (see the CI badge above).

## Deploy · Despliegue

Ready to deploy to Render (Docker). See [DEPLOY.md](DEPLOY.md) for the
walkthrough. The live URL will go here once it's up.

## Ideas for next steps · Siguientes pasos

Scope board listing to the organizations you belong to.

---

MIT licensed. Built by [Jorge](https://github.com/Jorgepele) while learning Laravel.
