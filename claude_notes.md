# Things — Claude Session Notes

## Project Status

**Phase: Design complete, not yet started.**
Waiting on Firebox MVC framework (in progress by separate Claude instance at `/home/afabian/firebox`).
When Firebox is ready, begin with DB schema and API layer.

## Key Facts

- Single user (Andy), single-person electronics lab
- Server: 10.0.0.10, SSH passwordless, PHP/MySQL
- Framework: Firebox at `/home/afabian/firebox` — use for web backend and PWA
- Qt app: C++/Qt, tray icon, full-screen viewer, HID scanner relay
- QR codes: pre-printed rolls with unique serials — never print our own
- No auth required (LAN only, single user) — revisit if needed

## Database Tables

- `items` — name, part_number, description, location_id, quantity
- `item_labels` — maps qr_serial → item_id (multiple per item)
- `locations` — name, parent_id (self-ref, null=root), qr_serial (one per location)
- `item_references` — name, file_type(pdf/image/md), file_path, display_order per item
- `app_state` — single row: current_location_id, follow_mode, last_scanned_qr, last_scanned_item_id

All PKs: UNSIGNED INT AUTO_INCREMENT.

## Global State (app_state row)

| field | behavior |
|-------|----------|
| current_location_id | Updated by: location scan always; item scan if follow_mode=1 |
| follow_mode | 0=locked (enables move workflow), 1=follows item scans |
| last_scanned_item_id | Updated every item scan — drives Qt viewer |

## Core Scan Logic (POST /api/scan)

1. Look up qr_serial in item_labels → found = item
2. Look up qr_serial in locations.qr_serial → found = location
3. Neither → return { status: "unknown", qr_serial } → client shows assignment form
4. On item scan: update last_scanned_item_id; if follow_mode=1, update current_location_id to item's location_id
5. On location scan: update current_location_id

## Phone PWA Layout

- Top half: live camera view (Web camera API)
- Bottom half: action buttons + current location + follow mode toggle
- Buttons: default scan, +Stock, -Stock, Move Here, Assign QR
- Response text displayed after each action
- Follow toggle prominent — changes behavior significantly

## Qt Viewer

- Tray app, goes full-screen on item scan
- Polls GET /api/viewer ~500ms
- Two-column full-screen layout:
  - Left ~70%: top-priority reference doc (lowest display_order) — image/PDF/md
  - Right ~30%: item name, part number, quantity, location path
- Also forwards HID scanner keystrokes → POST /api/scan
- HID capture mechanism on Linux: TBD (XInput, evdev, or focused input window)
- Multiple instances OK — all show same state
- On entering presentation mode: wake display if asleep
  - GNOME/Wayland: `QDBusInterface` → `org.gnome.ScreenSaver` / `/org/gnome/ScreenSaver` → `SetActive(false)`
  - Requires `QT += dbus` in .pro file

## Unrecognized QR Flow

- API returns { status: "unknown", qr_serial }
- Client shows tabbed form: [Item] [Location]
- Item tab: name, part_number, description, quantity (default 0), location (default current_location)
- Location tab: name, parent (default current_location)
- On submit: create record + insert into item_labels or set locations.qr_serial

## Move Item Workflow

Follow must be OFF for this:
1. Scan destination location → current_location updates
2. Scan item → "Move Here" button appears on phone
3. POST /items/{id}/move → sets item.location_id = current_location_id
4. Repeat for batch moves

## Implementation Order

1. DB schema + seed
2. API: /scan, /state, /items CRUD, /locations CRUD, /references CRUD, /viewer
3. Web UI (Firebox views)
4. Phone PWA
5. Qt tray + viewer
6. Qt HID scanner input
7. PDF/image/markdown rendering in Qt

## Open Questions

- Qt HID input on Linux: XInput vs evdev vs focused window — decide during Qt implementation
- PDF rendering in Qt: Qt PDF module (Qt 6.4+) is cleanest option
- Markdown in Qt: render md→HTML server-side, display in QTextBrowser or QWebEngineView
- API auth: probably skip for now (LAN only, single user)
