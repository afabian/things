# Things — Developer Documentation

## System Overview

"Things" is a two-role system:
1. **Inventory management** — location and quantity tracking for a single-user electronics lab
2. **Quick reference** — full-screen display of datasheets/pinouts triggered by QR scan

All state lives in the PHP/MySQL backend. All other components are thin clients.

---

## Architecture

```
[HID Scanner] ──keystroke──> [Qt Viewer]
                                  │
                         POST /things/scan.process
                                  │
[Phone PWA] ─── HTTP ──> [PHP/MySQL API] <── HTTP ── [Web UI]
                                  │
                         notify_ws() UDP :8766
                                  │
                         [C++ WebSocket Server] ──WS push──> all clients
                                  │
                         GET /things/state.get (polls API, 10s fallback)
```

- PHP API is the only component that writes to the database
- WebSocket server has no direct DB access — it polls the PHP API
- Qt viewer connects to WS for state changes, then HTTP-fetches `/viewer.get` for full item data
- PHP models call `notify_ws()` after any `app_state` mutation (UDP datagram to port 8766)

---

## Server Layout

Everything runs from a single git clone:

```
/home/afabian/things/           ← git clone
  www/                          ← PHP web app
  viewer/build/things-viewer    ← Qt desktop viewer (built on server)
  wsserver/build/things-ws      ← WebSocket server (built on server)
  schema.sql                    ← run once to initialize DB
  deploy.sh                     ← deployment script

/var/www/html/things            → symlink to ~/things/www
/etc/systemd/system/things-ws.service
~/.config/autostart/things-viewer.desktop
```

---

## Deployment

```bash
bash deploy.sh    # git pull on server, rebuild wsserver, restart service
```

Viewer changes need a manual rebuild and relaunch:

```bash
ssh 10.0.0.10 "cd ~/things/viewer && cmake --build build"
# then relaunch the viewer on the server desktop
```

---

## Directory Layout (`www/`)

```
www/
├── controller/     One file per controller
├── model/          Query and action files grouped by controller name
├── view/           Pure HTML views (no inline PHP or JS)
├── layout/         Page wrapper templates
├── lib/            Auto-injected utilities
├── js/             External JS files for views
├── settings.php    App config — no secrets
└── settings.local.php  ← gitignored; secrets and local overrides
```

---

## PHP/MySQL Backend

Firebox MVC framework. Routing: `?go=controller.method` or clean URL `/things/controller.method`.

**Critical Firebox rules:**
- Closing `}` must be at column 0 in all controller/model/lib files
- Views must be pure HTML — no inline PHP, all JS in external `.js` files
- `api_exit($data, $code)` calls `exit` — `post()` always empty in API controllers
- `api_input()` returns `array_merge($_GET, $parsed_json_body)`
- Lib files in `lib/` are auto-injected into every compiled file
- Clear compiled cache: `rm -f ~/things/www/parsed/dev/*.php`
- `$GLOBALS['fbx']['site_root']` gives the path to `www/` — use for file I/O

**Secrets:** put API keys in `www/settings.local.php` (gitignored). `settings.php`
loads it at the end if it exists.

---

## WebSocket Server (`wsserver/`)

C++/Qt6. Listens on two ports:
- **8765** — WebSocket clients (nginx proxies `ws://server/things/ws` here)
- **8766** — UDP notify from PHP (localhost only)

On UDP datagram: immediately fetches `state.get` and broadcasts to all WS clients if changed.
10-second fallback poll. Service runs as `afabian`, managed by systemd.

The broadcast JSON includes `updated_at` from `app_state`, which the Qt viewer uses
to detect new scans (including re-scanning the same item).

---

## Qt Desktop Viewer (`viewer/`)

Tray application. Full-screen on item scan, hidden otherwise.

- Connects to `ws://[server]/things/ws` for state push
- Triggers on `updated_at` change (not just item_id change) — handles same-item rescans
- First WS message silently initializes state — no popup on app startup
- Full-screen layout: DocViewer (70% left) + ItemPanel (30% right)
- DocViewer: QPdfView (PDF), QLabel (images), QTextBrowser (markdown)
  - PDF: check `status()` after load, not return value (Qt 6.2 vs 6.4 API difference)
- `BrowserWindow`: embedded QWebEngineView for management website
  - Opened from tray menu, no browser chrome
  - Hides on close (preserved), geometry saved/restored via QSettings
- Application icon set at QApplication level in main.cpp — all windows inherit it
- HID scanner: evdev direct read with EVIOCGRAB exclusive grab
- Display wake: D-Bus `org.gnome.ScreenSaver.SetActive(false)` on each new scan
- Mute: suppresses auto-show, tray icon grays out
- Error overlay: `ErrorOverlay` class — reusable dark-red banner

---

## AI Extract

Users query reference documents (PDF, image, or external URL) using Claude to extract sections.
Multiple sections can be returned from one query (e.g. "pinout and register table" → two docs).

**Flow:**
1. User selects source (existing ref or URL) and types a query
2. `POST references.ai_generate&item_id=N` with `{ref_id, url, query}`
3. PHP fetches file (disk or URL), base64-encodes, sends to Anthropic API
4. Claude returns JSON array of `{name, content}` pairs
5. Each saved as `.md` in `uploads/{item_id}/`, appended to display_order
6. Results appear in viewer immediately via WS push

Key files:
- `lib/anthropic.php` — `call_anthropic()` and `fetch_url_content()`
- `model/references/do_ai_generate.php` — orchestrates fetch → Claude → save
- Default model: `claude-sonnet-4-6` (override via `anthropic_model` in settings.local.php)

---

## Database Schema

All tables use `UNSIGNED INT AUTO_INCREMENT` primary keys.

### FK cascade rules
- `locations.parent_id → locations` RESTRICT
- `items.location_id → locations` RESTRICT
- `item_labels.item_id → items` CASCADE
- `item_references.item_id → items` CASCADE
- `app_state.current_location_id → locations` SET NULL
- `app_state.last_scanned_item_id → items` SET NULL

### `items`
| column | type | notes |
|--------|------|-------|
| name | VARCHAR(255) | |
| part_number | VARCHAR(100) NULL | |
| description | TEXT NULL | |
| location_id | INT UNSIGNED FK | current storage location |
| quantity | INT UNSIGNED | |

### `item_labels`
Multiple QR codes per item. `qr_serial` UNIQUE.

### `locations`
Hierarchical. `parent_id` self-ref FK (null = root). `qr_serial` UNIQUE.

### `item_references`
| column | notes |
|--------|-------|
| file_type | ENUM('pdf','image','md') |
| file_path | relative to www/ |
| display_order | ascending; 0 shown first in viewer |

Files stored in `www/uploads/{item_id}/`. AI-generated files named `ai_{timestamp}_{uniqid}.md`.

### `app_state`
Single row (id=1).

| column | notes |
|--------|-------|
| current_location_id | active working location |
| follow_mode | 1 = item scan updates current location |
| last_scanned_qr | |
| last_scanned_item_id | drives Qt viewer display |
| updated_at | changes on every scan — used by viewer to detect new scans |

---

## API Endpoints

### State
| endpoint | description |
|----------|-------------|
| `GET state.get` | app_state + current_location_name + updated_at |
| `POST state.toggle_follow` | Toggles follow_mode |

### Scanning
| endpoint | description |
|----------|-------------|
| `POST scan.process` | Body: `{qr_serial}`. Updates app_state. Returns type=item/location/unknown |

### Items
| endpoint | description |
|----------|-------------|
| `GET items.list_items` | Params: `q`, `location_id` |
| `POST items.create` | Body: name, part_number, description, quantity, location_id |
| `GET items.detail&id=N` | Item + location_path + labels + references |
| `POST items.update&id=N` | Body: name, part_number, description, location_id |
| `POST items.adjust_quantity&id=N` | Body: `{mode: "total"\|"delta", value}` |
| `POST items.move&id=N` | Moves to current_location_id |
| `POST items.add_label&id=N` | Body: `{qr_serial}` |
| `POST items.delete_label&label_id=N` | |
| `POST items.delete&id=N` | Deletes item + labels + refs + upload directory |

### Locations
| endpoint | description |
|----------|-------------|
| `GET locations.tree` | Flat list: `[{id, name, parent_id, qr_serial}]` |
| `POST locations.create` | Body: name, parent_id, qr_serial |
| `POST locations.update&id=N` | Body: name, parent_id |
| `POST locations.delete&id=N` | Recursive subtree delete; items reassigned to parent |

Location delete rules:
- Has items + has parent → items move to parent, location deleted
- Has children → entire subtree deleted; all items bubble up to parent
- Root location with items in subtree → refused

### References
| endpoint | description |
|----------|-------------|
| `POST references.upload&item_id=N` | Multipart: file + name |
| `POST references.reorder&item_id=N` | Body: `{id, direction: "up"\|"down"}` — swap + renumber all |
| `POST references.ai_generate&item_id=N` | Body: `{ref_id\|url, query}` → array of new refs |
| `POST references.update&id=N` | Body: name |
| `POST references.delete_ref&id=N` | Deletes file + DB row |

### Viewer
| endpoint | description |
|----------|-------------|
| `GET viewer.get` | Returns `{item, reference}` for current last_scanned_item_id |

---

## WebSocket Message Format

```json
{
  "current_location_id": 2,
  "current_location_name": "Cabinet A",
  "follow_mode": false,
  "last_scanned_qr": "ABC123",
  "last_scanned_item_id": 5,
  "updated_at": "2026-05-10 21:00:00"
}
```

---

## Nginx Config (on server)

Key blocks in `/etc/nginx/sites-enabled/default`:

```nginx
location = /things/ws {
    proxy_pass http://127.0.0.1:8765;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_read_timeout 86400;
}

location ~ \.php$ { ... }  # before things rewrites

location = /things/scanner { rewrite ^ /things/index.php?go=pwa.scanner last; }

location ~ ^/things/([a-zA-Z0-9_]+\.[a-zA-Z0-9_]+)/?$ {
    rewrite ^/things/([a-zA-Z0-9_.]+)/?$ /things/index.php?go=$1 last;
}
```

---

## User Workflows

### Scan → Quick Reference
1. Scan item QR → `scan.process` updates app_state + calls `notify_ws()`
2. WS server receives UDP, fetches state, broadcasts (including updated_at)
3. Qt viewer detects updated_at change + non-zero item_id → fetches `/viewer.get` → full-screen

### Adjust Quantity
Scan item on phone → tap +Stock or -Stock → enter amount.

### Move Item(s) to New Location
Toggle follow OFF → scan destination location → scan item → tap "Move Here". Repeat for batch.

### AI Extract from Datasheet
Item detail page → References → AI Extract section → pick source doc or paste URL → type query → Extract.
Creates one or more markdown reference entries, shows immediately in viewer.

### Add New Item (unknown QR)
Scan fresh QR → "Assign" button on phone → fill in name, part, location → Create + Assign.

### Open Management Website
Tray icon → Open Management Website → embedded browser window (no chrome).

---

## Open Items

- **Bulk quantity entry** — deferred
- **HID scanner udev rule** — pending scanner hardware identification
- **Old git branches** — `api`, `webui` on origin; safe to delete
