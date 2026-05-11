# Things — Claude Session Notes

## Project Status

**Phase: Active development. Core complete, iterating on polish and features.**

Done: DB schema, Core API, Web UI, Phone PWA, nginx clean URLs, Qt viewer,
WebSocket server, delete item, delete location, AI Extract, embedded browser,
reference ordering fix, viewer trigger fix.

---

## Server Layout

Everything runs on 10.0.0.10 from a single git clone at `~/things/`:

```
/home/afabian/things/        ← git clone (source of truth)
  www/                       ← web app; /var/www/html/things symlinks here
  viewer/build/things-viewer ← built on server, GNOME autostart
  wsserver/build/things-ws   ← built on server, systemd service
/var/www/html/things         → symlink to ~/things/www
/etc/systemd/system/things-ws.service
~/.config/autostart/things-viewer.desktop
```

---

## Deploy Workflow

```bash
bash deploy.sh       # git pull on server + rebuild wsserver + restart service
```

After viewer changes: `ssh server "cd ~/things/viewer && cmake --build build"`
Then relaunch the viewer application on the server desktop.

---

## Key Technical Facts

- Firebox closing `}` must be at column 0 (compiler bug)
- Views must be pure HTML, no inline PHP, no inline JS
- `api_exit()` calls exit — post() always empty in API controllers
- `api_input()` returns merge of `$_GET` and JSON body
- Lib files in `lib/` are auto-injected by Firebox
- Clear parsed cache: `rm -f ~/things/www/parsed/dev/*.php`
- `$GLOBALS['fbx']['site_root']` = path to `www/` directory

---

## Secrets / Local Config

`www/settings.local.php` — gitignored, loaded at end of settings.php.

```php
<?php
$fbx['settings']['anthropic_api_key'] = 'sk-ant-...';
```

---

## WebSocket Architecture

```
PHP model → notify_ws() → UDP :8766 → StateServer
                                           ↓
                              GET /things/state.get  (+ 10s fallback poll)
                                           ↓
                              broadcast JSON to WS clients on change
```

WS server ports: **8765** (WebSocket), **8766** (UDP notify, localhost only)
Nginx proxies `ws://server/things/ws` → localhost:8765
PHP `notify_ws()` in: do_process_scan, do_toggle_follow, do_move_item

WS message includes `updated_at` from app_state — this is what the Qt viewer
uses to detect new scans (not just item_id changes, which misses re-scans of
the same item).

---

## API Endpoints

- `GET state.get` — app_state + current_location_name + updated_at
- `POST state.toggle_follow`
- `POST scan.process` — {qr_serial} → type/item/location/unknown
- `GET items.list_items` — q, location_id params
- `POST items.create` / `GET items.detail&id=N` / `POST items.update&id=N`
- `POST items.adjust_quantity&id=N` — {mode: total|delta, value}
- `POST items.move&id=N`
- `POST items.add_label&id=N` / `POST items.delete_label&label_id=N`
- `POST items.delete&id=N` — deletes DB row + upload dir
- `GET locations.tree` / `POST locations.create` / `POST locations.update&id=N`
- `POST locations.delete&id=N` — recursive subtree delete, items reassigned to parent
- `POST references.upload&item_id=N`
- `POST references.reorder&item_id=N` — {id, direction: "up"|"down"} — swap + renumber
- `POST references.ai_generate&item_id=N` — {ref_id|url, query} → array of new refs
- `POST references.update&id=N` / `POST references.delete_ref&id=N`
- `GET viewer.get` — {item, reference} for current last_scanned_item_id

---

## Location Delete Behavior

- Has items + has parent → items reassigned to parent, location deleted
- Has children → entire subtree deleted recursively; all items bubble up to parent
- Root location with items anywhere in subtree → refused

Implementation: recursive CTE to collect subtree IDs, bulk UPDATE items, bulk
DELETE locations with FOREIGN_KEY_CHECKS=0 to avoid ordering issues.

---

## Reference Ordering

All refs created with sequential display_order (count of existing refs).
Reorder uses server-side normalize+swap: fetch all refs for item in order,
swap target with neighbor, renumber 0,1,2,... Updates all in DB.
Fixes existing broken refs (all at display_order=0) on first reorder.

---

## AI Extract

- Source: existing ref (PDF/image) OR a URL
- URL fetch uses curl with Content-Type detection; falls back to URL extension; defaults to pdf
- Claude returns JSON array of {name, content} pairs
- Each pair saved as `.md` in uploads/{item_id}/, display_order = count of existing refs
- Falls back to treating whole response as single result if JSON parse fails
- Model: `claude-sonnet-4-6` (configurable via `anthropic_model` in settings)
- `lib/anthropic.php`: `call_anthropic()` + `fetch_url_content()`

---

## Qt Viewer Details

- `ApiPoller`: WS connection to `ws://[server]/things/ws`
  - First WS message: silently initializes m_lastItemId + m_lastUpdatedAt (no startup pop-up)
  - Triggers fetchViewerData() when `updated_at` changes AND last_scanned_item_id != 0
  - This handles re-scanning the same item (id unchanged but updated_at changes)
  - Server down: 2s grace → serverReachabilityChanged(false); reconnect every 3s
- `BrowserWindow`: QMainWindow + QWebEngineView, no browser chrome
  - Opened from tray menu "Open Management Website"
  - Hides on close (not destroyed), geometry saved/restored via QSettings
  - Application-level icon set in main.cpp so all windows inherit it
- PDF loading: check `status()` after load (not return value — Qt 6.2 vs 6.4 compat)
- `ScannerInput`: evdev direct read, EVIOCGRAB exclusive, emits barcodeScanned
- HID setup: udev rule for stable device path, user in `input` group

---

## wsserver Details

- `StateServer`: QWebSocketServer on :8765 + QUdpSocket on :8766
- Polls `GET /things/state.get` every 10s (fallback)
- On UDP datagram: fetch + broadcast immediately if changed
- Broadcasts full state.get JSON (including updated_at) to all WS clients
- Service runs as `afabian`, restarts automatically

---

## Open Items

- **Bulk quantity entry** — deferred
- **HID scanner udev rule** — pending scanner hardware identification
- **Old git branches** — `api`, `webui` on origin; safe to delete
