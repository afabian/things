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

- The PHP API is the only component that writes to the database
- WebSocket server has no direct DB access — it polls the API
- Qt viewer connects to WS for state changes, then HTTP-fetches `/viewer.get` for full item data
- PHP models call `notify_ws()` after any `app_state` mutation, sending a UDP datagram to port 8766

---

## Directory Layout

```
things/
├── www/          PHP web app (Firebox MVC) — rsynced to server
├── viewer/       Qt6 desktop viewer — built locally, run locally
├── wsserver/     Qt6 WebSocket server — built locally, binary deployed to server
├── schema.sql    Initial DB schema — run once
└── deploy.sh     Rsync www/ to server + restart wsserver service
```

---

## Components

### PHP/MySQL Backend (`www/`)

Firebox MVC framework. Routing: `?go=controller.method` or clean URL `/things/controller.method`.

**Critical Firebox rules:**
- Closing `}` must be at column 0 in all controller/model/lib files (compiler block detection)
- Views must be pure HTML (no inline PHP content) — all JS in external `.js` files
- `api_exit($data, $code)` calls `exit` — `post()` is always empty in API controllers
- `api_input()` returns `array_merge($_GET, $parsed_json_body)`
- Lib files in `lib/` are auto-injected into every compiled file
- Run `rm -f /var/www/html/things/parsed/dev/*.php` after Firebox changes to clear cache

**Lib utilities:**
- `api_exit.php` — JSON response + exit
- `api_input.php` — merges GET params with JSON body
- `db_esc.php` — `mysqli_real_escape_string` wrapper (requires connection established first)
- `location_path.php` — recursive CTE, returns path array from root to leaf
- `notify_ws.php` — sends UDP datagram to WS server notify port

### WebSocket Server (`wsserver/`)

C++/Qt6. Polls `GET /things/state.get` every 10s as a fallback; also listens on UDP port 8766 for immediate push notifications from PHP. Broadcasts app_state JSON to all connected WebSocket clients on change.

Ports:
- **8765** — WebSocket (nginx proxies `ws://server/things/ws` here)
- **8766** — UDP notify (PHP → server, localhost only)

Service: `things-ws.service` managed by systemd. Binary at `/var/www/html/things/wsserver/things-ws`.

First-time install: `bash /var/www/html/things/wsserver/setup-service.sh`

Deploy update: `rsync -a wsserver/build/things-ws 10.0.0.10:/var/www/html/things/wsserver/ && ssh 10.0.0.10 "sudo systemctl restart things-ws"`

### Qt Desktop Viewer (`viewer/`)

Tray application. Full-screen on item scan, hidden otherwise.

- Connects to `ws://[server]/things/ws` for state push
- On `last_scanned_item_id` change → HTTP-fetches `/viewer.get` for item + reference data
- Reconnects every 3s on WS disconnect; reports server down after 2s disconnected
- Full-screen layout: DocViewer (70% left) + ItemPanel (30% right)
- DocViewer renders: PDF (QPdfView), images (QLabel), markdown (QTextBrowser)
- HID scanner: reads evdev device directly, exclusive grab via `EVIOCGRAB`; configure device in Settings
- Display wake: D-Bus `org.gnome.ScreenSaver.SetActive(false)` on each new scan
- Mute: suppresses auto-show; tray icon grays out
- Error overlay: red banner when WS disconnected; `ErrorOverlay` class is reusable
- Settings persist via QSettings; apply instantly (no Apply button)

Build: `cd viewer && make`
Run: `make run`

---

## Database Schema

All tables use `UNSIGNED INT AUTO_INCREMENT` primary keys.

### FK cascade rules
- `locations.parent_id → locations` RESTRICT (can't delete location with children)
- `items.location_id → locations` RESTRICT (can't delete location with items)
- `item_labels.item_id → items` CASCADE (labels deleted with item)
- `item_references.item_id → items` CASCADE (refs deleted with item)
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
| created_at / updated_at | TIMESTAMP | |

### `item_labels`
Multiple QR codes per item (bin label + reel label etc.).

| column | type |
|--------|------|
| id | INT UNSIGNED AI PK |
| item_id | INT UNSIGNED FK→items CASCADE |
| qr_serial | VARCHAR(100) UNIQUE |
| created_at | TIMESTAMP |

### `locations`
Hierarchical, unlimited depth.

| column | type | notes |
|--------|------|-------|
| id | INT UNSIGNED AI PK | |
| name | VARCHAR(255) | |
| parent_id | INT UNSIGNED FK→self NULL | null = root |
| qr_serial | VARCHAR(100) UNIQUE | one QR per location |
| created_at / updated_at | TIMESTAMP | |

### `item_references`
| column | type | notes |
|--------|------|-------|
| id | INT UNSIGNED AI PK | |
| item_id | INT UNSIGNED FK→items CASCADE | |
| name | VARCHAR(255) | display label |
| file_type | ENUM('pdf','image','md') | |
| file_path | VARCHAR(500) | relative to web root |
| display_order | INT | ascending; 0 shown first |
| created_at | TIMESTAMP | |

### `app_state`
Single row (id=1).

| column | type | notes |
|--------|------|-------|
| current_location_id | INT FK NULL | active working location |
| follow_mode | TINYINT(1) | 1 = item scan updates current location |
| last_scanned_qr | VARCHAR(100) NULL | |
| last_scanned_item_id | INT FK NULL | drives Qt viewer |
| updated_at | TIMESTAMP | |

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
| `GET items.detail&id=N` | Returns item + location_path + labels + references |
| `POST items.update&id=N` | Body: name, part_number, description, location_id |
| `POST items.adjust_quantity&id=N` | Body: `{mode: "total"\|"delta", value: int}` |
| `POST items.move&id=N` | Moves item to current_location_id |
| `POST items.add_label&id=N` | Body: `{qr_serial}` |
| `POST items.delete_label&label_id=N` | Removes label |
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
| `POST references.update&id=N` | Body: name, display_order |
| `POST references.delete_ref&id=N` | Deletes file from disk + DB row |

### Viewer
| endpoint | description |
|----------|-------------|
| `GET viewer.get` | Returns `{item, reference}` for current last_scanned_item_id |

---

## WebSocket Message Format

Sent by wsserver to all connected clients on any app_state change:

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

Key additions to `/etc/nginx/sites-enabled/default`:

```nginx
# WebSocket proxy — must be before PHP location
location = /things/ws {
    proxy_pass http://127.0.0.1:8765;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_read_timeout 86400;
}

# PHP — before things rewrite to prevent loop
location ~ \.php$ { ... }

# Clean URL routing
location = /things/scanner { rewrite ^ /things/index.php?go=pwa.scanner last; }
location ~ ^/things/([a-zA-Z0-9_]+\.[a-zA-Z0-9_]+)/?$ {
    rewrite ^/things/([a-zA-Z0-9_.]+)/?$ /things/index.php?go=$1 last;
}
```

---

## User Workflows

### Scan → Quick Reference
1. Scan item QR (HID scanner or phone)
2. `scan.process` updates `last_scanned_item_id`, calls `notify_ws()`
3. WS server receives UDP, fetches state, broadcasts to all clients
4. Qt viewer receives WS message, HTTP-fetches `/viewer.get`, goes full-screen
5. Web UI and PWA state bars update instantly via WS push

### Adjust Quantity
1. Scan item QR on phone → item shown in PWA
2. Tap +Stock or -Stock → enter amount → OK

### Move Item(s) to New Location
1. Toggle follow **OFF** on phone
2. Scan destination location QR → current_location updates
3. Scan item QR → tap "Move Here"
4. Repeat for batch moves; toggle follow **ON** when done

### Add New Item (unknown QR)
1. Scan fresh QR from roll → "Assign" button appears on phone
2. Fill in name, part number, location → Create + Assign

---

## Open Items

- **Bulk quantity entry** — inline qty editing in items list (deferred)
- **Location tree web UI improvements** — reorder, visual nesting (future)
- **Websocket: Qt viewer state bar** — viewer shows item but not follow/location in the panel; could display it
- **Authentication** — currently none; LAN-only single-user, revisit if needed
