# Things — Claude Session Notes

## Project Status

**Phase: Feature-complete MVP. In testing.**

Done: DB schema, Core API, Web UI, Phone PWA, nginx clean URLs, Qt viewer, WebSocket server,
delete item, AI Extract. Deferred: bulk quantity entry.

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

Web changes take effect immediately on pull (no build step).
Viewer changes require manual rebuild: `ssh server "cd ~/things/viewer && cmake --build build"`

---

## Key Technical Facts

- Firebox closing `}` must be at column 0 (compiler bug)
- Views must be pure HTML, no inline PHP, no inline JS
- `api_exit()` calls exit — post() always empty in API controllers
- `api_input()` returns merge of `$_GET` and JSON body
- Lib files in `lib/` are auto-injected by Firebox
- Clear parsed cache: `rm -f ~/things/www/parsed/dev/*.php`
- `$GLOBALS['fbx']['site_root']` = path to `www/` directory (use for file paths)

---

## Secrets / Local Config

`www/settings.local.php` — gitignored, loaded at end of settings.php.
Put API keys and other secrets here. Never in settings.php itself.

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
- `POST state.toggle_follow`
- `POST scan.process` — body: {qr_serial} → type/item/location/unknown
- `GET items.list_items` — q, location_id params
- `POST items.create` / `GET items.detail&id=N` / `POST items.update&id=N`
- `POST items.adjust_quantity&id=N` — body: {mode: total|delta, value}
- `POST items.move&id=N`
- `POST items.add_label&id=N` / `POST items.delete_label&label_id=N`
- `POST items.delete&id=N` — deletes DB row + upload dir
- `GET locations.tree` / `POST locations.create` / `POST locations.update&id=N`
- `POST references.upload&item_id=N`
- `POST references.ai_generate&item_id=N` — body: {ref_id, query} → array of new refs
- `POST references.update&id=N` / `POST references.delete_ref&id=N`
- `GET viewer.get` — {item, reference} for current last_scanned_item_id

---

## AI Extract

Flow: user picks a source reference (PDF or image) + types a query → POST to
`references.ai_generate` → PHP reads file, calls Anthropic API → Claude returns JSON
array of `{name, content}` → each saved as a new `.md` reference at display_order=0.

Key files:
- `www/lib/anthropic.php` — `call_anthropic($file_type, $content, $query)` → array or null
- `www/model/references/do_ai_generate.php` — reads file, calls Claude, saves results
- Model: `claude-sonnet-4-6` (configurable via `anthropic_model` in settings)

Prompt always asks Claude to return a JSON array even for single results.
Falls back to treating the whole response as one result if JSON parse fails.

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

- `ApiPoller`: WS connection to `ws://[server]/things/ws` (derived from Settings::wsUrl())
  - First WS message: silently initialize (no startup pop-up)
  - On `last_scanned_item_id` change: HTTP-fetch `/viewer.get`, emit `stateChanged`
  - Server down: 2s grace → `serverReachabilityChanged(false)`; reconnect every 3s
- `DocViewer`: QStackedWidget — QPdfView / QLabel / QTextBrowser
  - PDF: QTemporaryFile + QPdfDocument::load(); check `status()` not return value (Qt 6.4 compat)
- `ErrorOverlay`: floating dark-red banner, reusable via `showMessage()`/`hideMessage()`
- `ScannerInput`: evdev direct read, EVIOCGRAB exclusive, emits `barcodeScanned`
- HID setup: `sudo usermod -aG input $USER`, udev rule for stable path, configure in Settings
- Mute: tray icon grays, auto-show suppressed

---

## wsserver Details

- `StateServer`: QWebSocketServer on :8765 + QUdpSocket on :8766
- Polls `GET /things/state.get` every 10s (fallback)
- On UDP datagram: fetch + broadcast immediately if changed
- Service runs as `afabian`, restarts automatically

---

## Open Items

- **Qt viewer E2E test** — not run against live server since WS refactor
- **AI Extract E2E test** — API key confirmed working; full PDF flow not tested yet
- **HID scanner udev rule** — pending scanner hardware being plugged in
- **Bulk quantity entry** — deferred
- **Old git branches** — `api`, `webui` on origin; safe to delete
