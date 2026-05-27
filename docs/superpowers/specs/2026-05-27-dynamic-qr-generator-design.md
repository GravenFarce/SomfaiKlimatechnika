# Dynamic QR Code Generator — Design Spec

**Date:** 2026-05-27
**Status:** Approved

---

## Overview

A private, single-user admin tool for creating and managing dynamic QR codes. Hosted at `somfaiklimatechnika.hu/qr-admin/` on Tárhely.eu (Start csomag, PHP + MySQL). The URL is intentionally obscure — not linked from the main site.

QR codes point to a short redirect URL (`somfaiklimatechnika.hu/r/{short_code}`). The destination behind that URL can be updated at any time without regenerating the QR code image.

---

## Architecture

- **Hosting:** Tárhely.eu shared hosting (PHP + MySQL)
- **Admin area:** `somfaiklimatechnika.hu/qr-admin/` — password-gated, session-based
- **Redirect handler:** `somfaiklimatechnika.hu/r/{short_code}` — logs scan, issues instant 301 redirect
- **Email:** PHPMailer for password reminder emails
- **QR generation:** Client-side JS library (qrcode.js) — no server dependency
- **IP geolocation:** ip-api.com (free, no API key required) for approximate city lookup on scan
- **No frameworks, no build tools** — plain PHP files + one CSS file + minimal JS

---

## Pages

| Page | Path | Description |
|------|------|-------------|
| Login | `/qr-admin/index.php` | Password input + "Elfelejtette jelszavát?" link below |
| Dashboard | `/qr-admin/dashboard.php` | List of all QR codes with name, destination, scan count, actions |
| Create/Edit | `/qr-admin/edit.php` | Form: name + destination URL. Shows generated QR image after save. Download button. |
| Analytics | `/qr-admin/analytics.php?id=X` | Scan count over time, city breakdown, device breakdown |
| Change password | `/qr-admin/change-password.php` | Current password + new password + confirm |

All pages check for a valid session at the top — redirect to login if not authenticated.

---

## Data Model

### `users`
| column | type | notes |
|--------|------|-------|
| id | INT | primary key, auto increment |
| password_hash | VARCHAR(255) | bcrypt |
| password_encrypted | TEXT | AES-256 encrypted copy for password reminder email |

### `qr_codes`
| column | type | notes |
|--------|------|-------|
| id | INT | primary key, auto increment |
| short_code | VARCHAR(10) | unique, randomly generated (6 chars, e.g. `abc123`) |
| name | VARCHAR(255) | user-defined label |
| destination_url | TEXT | where the QR redirects to |
| created_at | DATETIME | |

### `scans`
| column | type | notes |
|--------|------|-------|
| id | INT | primary key, auto increment |
| qr_code_id | INT | foreign key → qr_codes.id |
| scanned_at | DATETIME | |
| city | VARCHAR(255) | approximate city from IP via ip-api.com |
| device | VARCHAR(50) | `mobile`, `tablet`, or `desktop` (parsed from user agent) |

---

## Authentication

- **Single user** — no username, password only
- **Login:** entered password verified against bcrypt hash in `users` table
- **Session:** PHP session started on login, expires after 24 hours or on browser close
- **Initial password:** `password1` (seeded via setup script)
- **Password reminder:** "Elfelejtette jelszavát?" link on login page — sends current plain-text password to `varga.ferenc88@gmail.com` via PHPMailer. The plain-text password is stored AES-256 encrypted in the `users` table solely for this purpose.
- **Change password:** requires current password verification, then new password + confirmation. Updates both bcrypt hash and AES-encrypted copy.

---

## QR Code Management

- **Create:** enter name + destination URL → system generates a random 6-char short code → QR image rendered client-side pointing to `somfaiklimatechnika.hu/r/{short_code}` → downloadable as PNG
- **Edit:** name and destination URL can be updated at any time. Short code never changes — printed QR codes remain valid.
- **Delete:** removes QR code record and all associated scan records

---

## Redirect & Analytics

- **Redirect handler** (`/r/{short_code}`):
  1. Look up short code in database
  2. Call ip-api.com with visitor IP to get approximate city
  3. Parse user agent to determine device type
  4. Insert row into `scans`
  5. Issue HTTP 301 redirect to destination URL instantly

- **Analytics per QR code:**
  - Total scan count
  - Scans over time (table by date)
  - Breakdown by city
  - Breakdown by device type (mobile / tablet / desktop)

---

## File Structure

```
somfaiklimatechnika.hu/
├── r/
│   └── index.php          # Redirect handler (reads short_code from URL)
└── qr-admin/
    ├── index.php           # Login page
    ├── dashboard.php       # QR code list
    ├── edit.php            # Create / edit QR code
    ├── analytics.php       # Scan analytics
    ├── change-password.php # Change password
    ├── logout.php          # Destroys session
    ├── includes/
    │   ├── db.php          # Database connection
    │   ├── auth.php        # Session check helper
    │   └── mailer.php      # PHPMailer wrapper
    ├── css/
    │   └── style.css       # Admin UI styles
    └── setup/
        └── install.php     # One-time DB setup + seeds initial password
```

---

## Out of Scope

- Multiple users / roles
- QR code types other than URL (vCard, WiFi, etc.)
- Loading/splash page before redirect
- Public-facing QR code creation
