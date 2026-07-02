# AGENTS.md

## Cursor Cloud specific instructions

This repo is the **ManaPHP** framework monorepo (Swoole-based PHP). It contains five runnable apps plus the shared `framework/` library:

| App | Type | Entry point | Notes |
| --- | --- | --- | --- |
| `app-api` | HTTP JSON API | `php public/index.php` | Router prefix `/api`; e.g. `curl http://127.0.0.1:9501/api` → `{"data":"Hello ManaPHP"}` |
| `app-admin` | HTTP MVC (admin) | `php -d opcache.enable_cli=on public/index.php` | Uses MySQL + Redis; `/` redirects to `/login` |
| `app-user` | HTTP MVC (user) | `php -d opcache.enable_cli=on public/index.php` | Uses MySQL + Redis; `/` redirects to `about` |
| `app-ws` | WebSocket server | `php public/index.php` | **Requires the Swoole extension** (no fallback). Route `/echo` echoes payloads |
| `app-cli` | Console app | `php manacli.php` (or `./manacli`) | Lists/runs commands; no HTTP server |

### Ports
- Every HTTP/WS server listens on **port 9501**. Only **one** app can run at a time locally unless you edit `config/server.php`.

### Runtime services (must be started each session; NOT started by the update script)
MySQL data and Redis are installed in the environment snapshot but the daemons are stopped on a fresh VM. Start them before running any app except `app-cli`:

```bash
sudo mysqld_safe --datadir=/var/lib/mysql >/tmp/mysqld.log 2>&1 &
sudo redis-server --daemonize yes --port 6379
```

- MySQL root credentials are `root` / `123456` (matches the default `DB_URL`). Databases `manaphp` (app data, seeded from `app-admin/install.sql` + `app-user/install.sql`) and `manaphp_unit_test` (tests) already exist in the snapshot.
- Redis runs on `localhost:6379` (apps use DB index 1).

### Config files (gitignored — recreated by the update script)
Each app boots by reading `<app>/config/.env`, which is **gitignored**. The `Kernel` throws `FileNotFoundException` if it is missing. The update script copies `<app>/config/.env.example` → `<app>/config/.env` when absent. If you change credentials/ports, edit `<app>/config/.env` (not the example).

### Swoole vs pure-PHP HTTP adapter
- `config/server.php` auto-detects the server adapter. With the Swoole extension loaded (it is, in the snapshot), HTTP apps run on the Swoole HTTP server (coroutine). Without Swoole they fall back to a pure-PHP `-S` server on 9501. `app-ws` has **no** fallback and needs Swoole.

### Lint / tests
- There is **no** dedicated linter (no phpstan/psalm/php-cs-fixer config) and **no** root `composer.json`. The only syntax check is `php -l <file>` (ignore `framework/.phpstorm.meta.php`, which is PhpStorm metadata, not code).
- The `tests/` suite + `phpunit.xml.dist` are **legacy/stale**: `tests/bootstrap.php` requires a non-existent `ManaPHP/Loader.php`, no PHPUnit is declared as a dependency, and the assertions target old framework behavior (they fail against current code). Do not treat these as a passing gate.

### vendor
`vendor/` directories are **committed** for each app and use PSR-4 (the framework is mapped in-place via `"ManaPHP\\": "../framework/"`). New framework classes resolve automatically without `composer install`. Running `composer install` only regenerates the autoloader (the "lock file is not up to date" warnings are harmless).
