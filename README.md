# Timer Panel

Web panel for the CS timer: maps, players, leaderboards and replays. The
Laravel application lives in [`laravel/`](laravel); everything at the root is
about running it.

```
.
├── .github/workflows/ci.yml   build the image, lint + test inside it, boot it
├── docker/                    Caddy, PHP ini, entrypoint
├── laravel/                   the application
├── Dockerfile                 multi-stage: vendor → assets → test → dev → production
├── docker-compose.yml         production-shaped stack
└── docker-compose.dev.yml     overlay: bind-mounted source + Vite hot reload
```

## Databases

Two, and neither is created or migrated by the stack:

- **Application data** (users, sessions, cache, queue, telescope) — sqlite, on
  the `app-database` volume. The only migrations in the repo are these.
- **Game data** (`maps`, `times`, `ranked_times`, `categories`) — an external
  MySQL server reached through the `game_mysql` connection
  (`laravel/config/database.php`). Read-only from the panel's point of view,
  configured entirely through the `GAME_DB_*` variables. There is no MySQL
  container: point `GAME_DB_HOST` at the real server.

## Running it

```bash
cp .env.example .env          # fill in GAME_DB_* and REPLAY_HOST_PATH
docker compose build
docker compose run --rm app php artisan key:generate --show   # paste into APP_KEY
docker compose up -d
```

The panel is on http://localhost:8080 and its health endpoint is `/up`.

### Development

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml --profile dev up
```

That bind-mounts `laravel/` into the container (no config/route/view caching,
opcache revalidates on every request), starts Vite with hot reload on :5173 and
Mailpit on http://localhost:8025.

### Useful commands

```bash
docker compose exec app php artisan about
docker compose exec app php artisan tinker
docker compose run --rm app php artisan migrate --force   # application db only
docker build --target test -t timerpanel:test . && docker run --rm timerpanel:test
```

The last one is exactly what CI runs: `pint --test` followed by `pest`.

## Replays

The `.rec` recordings are not part of the application. Set `REPLAY_HOST_PATH` to
the directory that holds them on the host; it is mounted at `/srv/replays`
inside the container, which is what `REPLAY_STORAGE_PATH` points at.

## CI

Every pull request and every push to `main` or `develop` builds the image,
runs Pint and Pest **inside** it, builds the production target and boots it to
check that `/up` answers. Tests that need the game database skip themselves,
so CI needs no database at all.

## Performance

Every list is read a page at a time: the page is cut in SQL (`ORDER BY … LIMIT …
OFFSET …`) before anything is joined, so the game database only looks up the
dozen rows on screen. Filters run in that same query, and pages and totals are
cached for a minute or two.

That only pays off if the game database has the right indexes. On 60,000
players and 250,000 runs the latest-runs page went from 857 ms to 0.8 ms and a
player profile from 1.2 s to 9 ms. [`docs/game-db-indexes.sql`](docs/game-db-indexes.sql)
lists them, with the numbers. The panel does not create them: it never changes
the game database.

## Map pictures

A map's picture is downloaded once, checked to be a real image, and kept on this
server; nothing is hot-linked. Where they come from is `MAP_IMAGE_SOURCES` in
`.env`: URL templates with `{map}`, tried in order (the default is gametracker.com's
CS 1.6 folder, then gametracker.rs, then its Source-game folders). A map with no
picture keeps a generated cover in its own colour.

```bash
docker compose exec app php artisan maps:fetch-images   # fetch them all now, politely
```

Some sources answer 200 with a "no picture" image for any name; those are refused by
their hash (`MAP_IMAGE_REJECT_HASHES`, gametracker.rs's is built in). For a map no
source has, an admin can upload one from the dashboard, or drop `<map>.jpg` into
`storage/app/private/map-images/`.
