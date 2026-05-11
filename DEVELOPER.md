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

Web app changes are live immediately after pull. Viewer changes need a manual rebuild:

```bash
ssh 10.0.0.10 "cd ~/things/viewer && cmake --build build"
```

---

## Directory Layout (`www/`)

```
www/
├── controller/     One file per controller; routes ?go=controller.method
├── model/          Query and action files grouped by controller name
├── view/           Pure HTML views (no inline PHP or JS)
├── layout/         Page wrapper templates
├── lib/            Auto-injected utilities (api_exit, api_input, db_esc, etc.)
├── plugins/        Firebox dev plugins (debug, profiler, queries, menu)
├── js/             External JS files for views
├── settings.php    App config — no secrets
└── settings.local.php  ← gitignored; secrets and local overrides go here
```

---

## PHP/MySQL Backend

Firebox MVC framework. Routing: `?go=controller.method` or clean URL `/things/controller.method`.

**Critical Firebox rules:**
- Closing `}` must be at column 0 in all controller/model/lib files (compiler block detection)
- Views must be pure HTML — no inline PHP content, all JS in external `.js` files
- `api_exit($data, $code)` calls `exit` — `post()` is always empty in API controllers
- `api_input()` returns `array_merge($_GET, $parsed_json_body)`
- Lib files in `lib/` are auto-injected into every compiled file
- Clear compiled cache: `rm -f ~/things/www/parsed/dev/*.php`
- `$GLOBALS['fbx']['site_root']` gives the path to `www/` — use for file I/O

**Secrets:** put API keys and passwords in `www/settings.local.php` (gitignored).
`settings.php` loads it at the end if it exists. Never put secrets in `settings.php` itself.

---

## WebSocket Server (`wsserver/`)

C++/Qt6. Listens on two ports:
- **8765** — WebSocket clients (nginx proxies `ws://server/things/ws` here)
- **8766** — UDP notify from PHP (localhost only)

On UDP datagram: immediately fetches `state.get` and broadcasts to all WS clients if changed.
10-second fallback poll catches anything missed.

Service runs as `afabian`, managed by systemd. Rebuilds automatically on `deploy.sh`.

---

## Qt Desktop Viewer (`viewer/`)

Tray application. Full-screen on item scan, hidden otherwise. Built and run on the server.

- Connects to `ws://[server]/things/ws` for state push
- On `last_scanned_item_id` change: HTTP-fetches `/viewer.get` for full item + reference data
- Reconnects every 3s on WS disconnect; reports server down after 2s disconnected
- Full-screen layout: DocViewer (70% left) + ItemPanel (30% right)
- DocViewer: QPdfView (PDF), QLabel (images), QTextBrowser (markdown)
  - PDF loading: write to QTemporaryFile, check `status()` after load (not return value — API differs between Qt 6.2 and 6.4)
- HID scanner: evdev direct read with `EVIOCGRAB` exclusive grab; device configured in Settings
- Display wake: D-Bus `org.gnome.ScreenSaver.SetActive(false)` on each new scan
- Mute: suppresses auto-show, tray icon grays out
- Error overlay: `ErrorOverlay` class — reusable floating dark-red banner

GNOME autostart: `~/.config/autostart/things-viewer.desktop`

---

## AI Extract

Users can query reference documents (PDF or image) using Claude to extract specific sections.
Each query can return multiple results — e.g. "pinout and register table" → two separate docs.

**Flow:**
1. User selects a source reference and types a query in the web UI item detail page
2. `POST references.ai_generate&item_id=N` with `{ref_id, query}`
3. PHP reads the file, base64-encodes it, sends to Anthropic API
4. Claude returns a JSON array of `{name, content}` pairs
5. Each pair saved as a new `.md` file in `uploads/{item_id}/` and inserted into `item_references` at `display_order=0`
6. Results appear in the viewer immediately via WS push

**Key files:**
- `lib/anthropic.php` — `call_anthropic($file_type, $content, $query)` → array of `{name, content}` or null
- `model/references/do_ai_generate.php` — reads file, calls Claude, saves results
- Model default: `claude-sonnet-4-6` (override via `anthropic_model` in settings)

**Prompt strategy:** always instructs Claude to respond with a JSON array, even for single results.
Falls back to treating the full response as one result if JSON parsing fails.

---

## Database Schema

All tables use `UNSIGNED INT AUTO_INCREMENT` primary keys.

### FK cascade rules
- `locations.parent_id → locations` RESTRICT (can't delete location with children)
- `items.location_id → locations` RESTRICT (can't delete location with items)
- `item_labels.item_id → items` CASCADE
- `item_references.item_id → items` CASCADE
- `app_state.current_location_id → locations` SET NULL
- `app_state.last_scanned_item_id → items` SET NULL

### `items`
| column | type | notes |
|--------|------|-------|
| id | INT UNSIGNED AI PK | |
| name | VARCHAR(255) | |
| part_number | VARCHAR(100) NULL | |
| description | TEXT NULL | |
| location_id | INT UNSIGNED FK | current storage location |
| quantity | INT UNSIGNED | |

### `item_labels`
Multiple QR codes per item.

| column | type |
|--------|------|
| item_id | INT UNSIGNED FK→items CASCADE |
| qr_serial | VARCHAR(100) UNIQUE |

### `locations`
Hierarchical, unlimited depth.

| column | type | notes |
|--------|------|-------|
| parent_id | INT UNSIGNED FK→self NULL | null = root |
| qr_serial | VARCHAR(100) UNIQUE | one QR per location |

### `item_references`
| column | type | notes |
|--------|------|-------|
| item_id | INT UNSIGNED FK→items CASCADE | |
| file_type | ENUM('pdf','image','md') | |
| file_path | VARCHAR(500) | relative to www/ |
| display_order | INT | ascending; 0 shown first in viewer |

### `app_state`
Single row (id=1).

| column | notes |
|--------|-------|
| current_location_id | active working location |
| follow_mode | 1 = item scan updates current location |
| last_scanned_qr | |
| last_scanned_item_id | drives Qt viewer display |

---

## API Endpoints

All via Firebox routing. Clean URLs: `/things/controller.method`

### State
| endpoint | description |
|----------|-------------|
| `GET state.get` | Returns app_state + current_location_name |
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

### References
| endpoint | description |
|----------|-------------|
| `POST references.upload&item_id=N` | Multipart: file + name |
| `POST references.ai_generate&item_id=N` | Body: `{ref_id, query}` → array of new refs |
| `POST references.update&id=N` | Body: name, display_order |
| `POST references.delete_ref&id=N` | Deletes file + DB row |

### Viewer
| endpoint | description |
|----------|-------------|
| `GET viewer.get` | Returns `{item, reference}` for current last_scanned_item_id |

---

## WebSocket Message Format

Broadcast to all clients on any app_state change:

```json
{
  "current_location_id": 2,
  "current_location_name": "Cabinet A",
  "follow_mode": false,
  "last_scanned_qr": "ABC123",
  "last_scanned_item_id": 5
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

location ~ \.php$ { ... }  # must come before things rewrites

location = /things/scanner { rewrite ^ /things/index.php?go=pwa.scanner last; }

location ~ ^/things/([a-zA-Z0-9_]+\.[a-zA-Z0-9_]+)/?$ {
    rewrite ^/things/([a-zA-Z0-9_.]+)/?$ /things/index.php?go=$1 last;
}
```

---

## User Workflows

### Scan → Quick Reference
1. Scan item QR → `scan.process` updates app_state + calls `notify_ws()`
2. WS server receives UDP, fetches state, broadcasts to all clients
3. Qt viewer receives WS message, HTTP-fetches `/viewer.get`, goes full-screen

### Adjust Quantity
1. Scan item on phone → tap +Stock or -Stock → enter amount

### Move Item(s) to New Location
1. Toggle follow **OFF**, scan destination location, scan item → tap "Move Here"
2. Repeat for batch; toggle follow **ON** when done

### AI Extract from Datasheet
1. Upload PDF datasheet as a reference on item detail page
2. In AI Extract section: select source doc, type query (e.g. "pinout and register table")
3. Click Extract — Claude creates one or more new markdown reference entries
4. New entries appear in viewer immediately

### Add New Item (unknown QR)
1. Scan fresh QR → "Assign" button appears on phone
2. Fill in name, part number, location → Create + Assign

---

## Open Items

- **Qt viewer E2E** — not run against live server since WS refactor
- **AI Extract E2E** — API key confirmed; full PDF flow not tested
- **HID scanner udev rule** — pending scanner being plugged in
- **Bulk quantity entry** — deferred
- **Old git branches** — `api`, `webui` on origin; safe to delete
