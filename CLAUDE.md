# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Website for Somfai Klímatechnika Kft. (air-conditioning contractor, Esztergom, Hungary). Site copy, comments and UI strings are in **Hungarian** — keep new content in Hungarian. Live at `https://somfaiklimatechnika.hu`.

There are two independent parts in one repo, deployed together:

1. **Public site** — static `index.html` + `css/style.css` + `js/main.js` (no build step, no framework, no bundler). `contact.php` is the contact-form endpoint: it takes a POST (`name`, `phone`, `subject` required; `email`, `message` optional), sends mail via PHP `mail()` to `info@somfaiklimatechnika.hu` and returns `{"ok": bool}` JSON. SEO files (`robots.txt`, `sitemap.xml`, Open Graph / Schema.org markup in `index.html`) are hand-maintained.
2. **Dynamic QR code admin** (`qr-admin/` + `r/`) — plain PHP + MySQL, single-user, session-authenticated, intentionally not linked from the public site. Design spec and plan: `docs/superpowers/specs/2026-05-27-dynamic-qr-generator-design.md` and `docs/superpowers/plans/2026-05-27-dynamic-qr-generator.md`.

### QR system architecture

- QR images encode a short URL `SITE_URL/r/{short_code}`. [r/index.php](r/index.php) (routed by [r/.htaccess](r/.htaccess), which rewrites `/r/abc123` → `index.php?code=abc123`) looks up the code, logs a row in `scans` (city via ip-api.com, device via user-agent sniffing), then 301-redirects to `destination_url`. Editing the destination in the admin changes where already-printed QR codes go.
- `qr-admin/*.php` pages each call `require_auth()` ([includes/auth.php](qr-admin/includes/auth.php)); shared helpers live in `qr-admin/includes/` (`db.php` PDO singleton `get_db()`, `crypto.php` AES helpers, `qr_helpers.php`, `mailer.php`, `sidebar.php`). `qr-admin/includes/` and `qr-admin/setup/` are locked down by their own `.htaccess`.
- Schema (`users`, `qr_codes`, `scans`) is created by [qr-admin/setup/install.php](qr-admin/setup/install.php), run once by visiting it in the browser; its `.htaccess` blocks it afterwards.
- The password is stored both as a bcrypt hash and as an AES-256 encrypted copy (to support the "forgot password" reminder email) — by design per the spec.
- No Composer runtime dependencies (PHPMailer was dropped; `mailer.php` uses `mail()`).

## Commands

There is no build or lint step for the static site; open `index.html` directly or serve with a PHP server (`php -S localhost:8000`) to exercise the PHP endpoints (they need a reachable MySQL per `config.php`).

Tests (PHPUnit 11, unit tests for the pure helpers in `qr-admin/includes/`):

```
composer install
vendor/bin/phpunit                          # all tests
vendor/bin/phpunit tests/CryptoTest.php     # one file
vendor/bin/phpunit --filter test_encrypt_then_decrypt_returns_original
```

## Deployment

Pushing to `main` triggers [.github/workflows/deploy.yml](.github/workflows/deploy.yml), which mirrors the repo over SFTP to `public_html` on Tárhely.eu with `lftp mirror --reverse --delete` (anything on the server that is not in the repo gets deleted). It excludes `.git`, `.github/`, `tests/`, `docs/`, `phpunit.xml`, `composer.json`, `CLAUDE.md` and **`qr-admin/includes/config.php`**, so the production `config.php` (DB credentials, `ENCRYPTION_KEY`, `ADMIN_EMAIL`, `SITE_URL`) lives only on the server and is never overwritten. `config.php` is git-ignored; copy [qr-admin/includes/config.sample.php](qr-admin/includes/config.sample.php) to `config.php` and fill it in for local work. The committed history still contains older credentials from before it was untracked.
