# Things — Claude Session Notes

## Project Status

**Phase: Feature-complete MVP. All major components built and deployed.**

Done: DB schema, Core API, Web UI, Phone PWA, nginx clean URLs, Qt viewer, WebSocket server, Delete item.
Deferred: Bulk quantity entry.

---

## Directory Layout

```
things/
├── www/          PHP web app (Firebox MVC) — rsynced to server
├── viewer/       Qt6 desktop viewer — built/run locally
├── wsserver/     Qt6 WebSocket server — built locally, binary deployed
├── schema.sql    Run once against DB
└── deploy.sh     Rsync www/ + restart wsserver service
```

---

## Key Technical Facts

- Server: 10.0.0.10, SSH passwordless, nginx, PHP 8.3, MySQL
- Framework: Firebox MVC at `/home/afabian/firebox`
- Firebox closing `}` must be at column 0 (compiler bug)
- Views must be pure HTML, no inline PHP, no inline JS
- `api_exit()` calls exit — post() always empty in API controllers
- `api_input()` returns merge of `$_GET` and JSON body
- Lib files in `lib/` are auto-injected by Firebox
- Clear parsed cache: `rm -f /var/www/html/things/parsed/dev/*.php`

---

## WebSocket Architecture

```
PHP model → notify_ws() → UDP :8766 → StateServer
                                           ↓
                              GET /things/state.get  (also 10s fallback poll)
                                           ↓
                              broadcast JSON to WS clients on change
                                           ↓
               ┌─────────────────────────────────────────────┐
               │                         │                    │
          Qt viewer                  Web UI              Phone PWA
     (fetches /viewer.get          (state bar            (state bar
      on item_id change)            updates)              updates)
```

WS server ports: **8765** (WebSocket), **8766** (UDP notify, localhost only)
Nginx proxies `ws://server/things/ws` → localhost:8765
PHP `notify_ws()` in: do_process_scan, do_toggle_follow, do_move_item

---

## API Endpoints (clean URL format)

- `GET state.get` — app_state + location name
- `POST state.toggle_follow` — toggle follow mode
- `POST scan.process` — body: {qr_serial} → type/item/location/unknown
- `GET items.list_items` — q, location_id params
- `POST items.create` / `GET items.detail&id=N` / `POST items.update&id=N`
- `POST items.adjust_quantity&id=N` — body: {mode: total|delta, value}
- `POST items.move&id=N` — move to current_location_id
- `POST items.add_label&id=N` / `POST items.delete_label&label_id=N`
- `POST items.delete&id=N` — deletes DB row + upload dir
- `GET locations.tree` / `POST locations.create` / `POST locations.update&id=N`
- `POST references.upload&item_id=N` / `POST references.update&id=N` / `POST references.delete_ref&id=N`
- `GET viewer.get` — {item, reference} for current last_scanned_item_id

---

## viewer.get Response

```json
{
  "item": {
    "id": 1, "name": "STM32F103", "part_number": "STM32F103C8T6",
    "quantity": 5, "location_id": 3,
    "location_path": [{"id":1,"name":"Lab"},{"id":3,"name":"Drawer A"}]
  },
  "reference": {
    "id": 1, "name": "Datasheet", "file_type": "pdf",
    "url": "http://10.0.0.10/things/uploads/1/datasheet.pdf"
  }
}
```

---

## Qt Viewer Details

- `ApiPoller`: WS connection to `ws://[server]/things/ws`
  - First WS message: silently initialize (no startup pop-up)
  - On `last_scanned_item_id` change: HTTP-fetch `/viewer.get`, emit `stateChanged`
  - Server down: 2s grace, then `serverReachabilityChanged(false)`
  - Reconnect: every 3s on disconnect
- `DocViewer`: QStackedWidget — QPdfView / QLabel / QTextBrowser
  - PDF: write to QTemporaryFile, load via QPdfDocument (Qt 6.2 — no QIODevice overload)
  - QPdfDocument::load() returns `DocumentError` (not `Status`) in Qt 6.2
- `ErrorOverlay`: floating dark-red banner, reusable via `showMessage()`/`hideMessage()`
- `ScannerInput`: evdev direct read, EVIOCGRAB exclusive, emits `barcodeScanned`
- HID setup: `sudo usermod -aG input $USER`, then configure device in Settings
- Mute: tray icon grays out, auto-show suppressed; double-click tray = show/hide

---

## wsserver Details

- `StateServer`: QWebSocketServer + QUdpSocket
- Polls `GET /things/state.get` every 10s (fallback)
- On UDP datagram on :8766: immediately fetch + broadcast if changed
- Sends current state to new WS clients on connect
- Service: `/etc/systemd/system/things-ws.service` running as www-data
- First-time setup: `bash /var/www/html/things/wsserver/setup-service.sh`
- Redeploy: `rsync build/things-ws ... && ssh ... sudo systemctl restart things-ws`

---

## Open Items

- **Bulk quantity entry** — inline qty editing in items list (deferred by Andy)
- **Qt viewer E2E test** — haven't run `make run` against live server post-WS refactor
- **Location tree UI** — no reorder/visual nesting yet
- **Item search** — basic text search exists; no filtering by location tree
- **Authentication** — none, LAN-only single user

---

## Build Commands

```bash
# Web
bash deploy.sh

# Qt viewer
cd viewer && make        # build
make run                 # build + run

# WS server
cd wsserver && make      # build
# deploy: rsync build/things-ws to server, restart service
```

---

## DB Connection Info

- Host: localhost (server-side)
- DB: things / User: things / Pass: things
- Admin: things_admin / things_admin (for schema changes)
