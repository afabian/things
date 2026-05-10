# Things — Developer Documentation

## System Overview

"Things" is a two-role system:
1. **Inventory management** — location and quantity tracking for a single-user electronics lab
2. **Quick reference** — full-screen display of datasheets/pinouts triggered by QR scan

All state is centralized in the PHP/MySQL backend. All other components (phone PWA, Qt viewer, HID scanner relay) are thin clients that read/write through the API.

---

## Architecture

```
[Wireless HID Scanner] ──keystroke──> [Qt Tray App]
                                            │
                          ┌─────── POST /api/scan ───────┐
                          │                               │
[Phone PWA] ──────────> [PHP/MySQL API] <──────────── [Web UI]
                          │
                    GET /api/viewer
                          │
                    [Qt Viewer Display]
```

- The API is the only component that touches the database
- Qt app is stateless — it polls the API and forwards scanner input
- Phone PWA and Web UI are both browser-based; PWA is mobile-optimized with camera

---

## Components

### PHP/MySQL Backend (Firebox framework)
- Framework: Firebox MVC at `/home/afabian/firebox`
- All business logic lives here
- API layer (controllers + models) is fully unit-testable with no UI dependency
- Presentation layer (views) cannot bypass the API to touch DB directly

### Web UI
- Browser-based inventory management: items, locations, reference files
- Not designed for mobile or distance viewing
- Used for setup tasks: creating items/locations, uploading reference docs, re-parenting locations

### Phone PWA
- Served by the PHP backend, runs in a mobile browser
- Layout: top half = live camera view; bottom half = action buttons
- Buttons send `POST /api/scan` with `{ qr_serial, action }` and display response text
- Actions: (default), Add Stock, Remove Stock, Move Here, Assign QR
- Follow mode toggle is prominent
- Displays current location and follow mode state at all times

### Qt Desktop Viewer
- Runs as a tray icon; goes full-screen when the API signals a new item scan
- Polls `GET /api/viewer` on a short interval (~500ms)
- Forwards HID scanner keystrokes to `POST /api/scan`
- Full-screen layout: two columns
  - Left (~70%): reference document display (image, PDF, or rendered markdown)
  - Right (~30%): item name, part number, quantity, location path
  - Shows top-priority reference document (lowest `display_order`)
- Wakes the display when entering presentation mode (see below)
- Minimizes/hides when user closes full-screen (does not exit — stays in tray)
- Exits cleanly from tray icon menu
- Multiple instances supported; all display the same state

#### Display Wake on Presentation (Qt)
When entering full-screen presentation mode, the Qt app must wake the display if it is asleep.

- Environment: GNOME on Wayland
- Use `QDBusInterface` to call `org.gnome.ScreenSaver.SetActive(false)` on the session bus
- Interface: `org.gnome.ScreenSaver`, path: `/org/gnome/ScreenSaver`
- No subprocess needed; requires `QT += dbus` in the Qt project file

#### HID Scanner Input (Qt)
- Wireless QR scanners act as HID keyboards: output barcode text + Enter
- Qt app captures this input via a global keyboard hook or a focused hidden input window
- Parsed QR serial is forwarded to `POST /api/scan` immediately
- Exact capture mechanism TBD (Linux: XInput/evdev; evaluate options during implementation)

---

## Database Schema

All tables use `UNSIGNED INT AUTO_INCREMENT` primary keys.

### `items`
| column | type | notes |
|--------|------|-------|
| id | INT UNSIGNED AI PK | |
| name | VARCHAR(255) | |
| part_number | VARCHAR(100) NULL | |
| description | TEXT NULL | |
| location_id | INT UNSIGNED FK→locations | current storage location |
| quantity | INT UNSIGNED | total in stock |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### `item_labels`
Multiple QR codes can map to one item (e.g., label on bin + label on reel).

| column | type | notes |
|--------|------|-------|
| id | INT UNSIGNED AI PK | |
| item_id | INT UNSIGNED FK→items | |
| qr_serial | VARCHAR(100) UNIQUE | serial from pre-printed roll |
| created_at | TIMESTAMP | |

### `locations`
Hierarchical — unlimited depth. Moving a location moves all children and their items implicitly (via parent_id traversal).

| column | type | notes |
|--------|------|-------|
| id | INT UNSIGNED AI PK | |
| name | VARCHAR(255) | |
| parent_id | INT UNSIGNED FK→self NULL | null = root |
| qr_serial | VARCHAR(100) UNIQUE | one QR per location |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### `item_references`
Reference documents attached to items. Displayed in `display_order` order; lowest = highest priority.

| column | type | notes |
|--------|------|-------|
| id | INT UNSIGNED AI PK | |
| item_id | INT UNSIGNED FK→items | |
| name | VARCHAR(255) | display label |
| file_type | ENUM('pdf','image','md') | |
| file_path | VARCHAR(500) | path on server |
| display_order | INT | ascending; 0 = show first |
| created_at | TIMESTAMP | |

### `app_state`
Single row (id=1). Global shared state across all clients.

| column | type | notes |
|--------|------|-------|
| id | INT UNSIGNED AI PK | always 1 |
| current_location_id | INT UNSIGNED FK→locations NULL | active working location |
| follow_mode | TINYINT(1) | 1 = item scan updates current location |
| last_scanned_qr | VARCHAR(100) NULL | most recent QR serial seen |
| last_scanned_item_id | INT UNSIGNED FK→items NULL | drives viewer display |
| updated_at | TIMESTAMP | |

---

## Global Application State

| field | behavior |
|-------|----------|
| `current_location` | Updated when: location QR scanned; item QR scanned (if follow ON) |
| `follow_mode` | ON: scanning item updates current location to item's location. OFF: current location is locked — enables move workflow |
| `last_scanned_item_id` | Updated on every item scan; drives Qt viewer display |

---

## API Endpoints

Base path: `/api`

### State
| method | path | description |
|--------|------|-------------|
| GET | `/state` | Returns full app_state row |
| PUT | `/state/follow` | Toggle follow_mode |

### Scanning (core action)
| method | path | description |
|--------|------|-------------|
| POST | `/scan` | Process a QR scan. Body: `{ qr_serial, action? }`. Returns entity info or `{ status: "unknown", qr_serial }` if not found. Updates app_state. |

Actions passed with `/scan`:
- *(none)* — lookup only, update state
- `add_stock` — prompt for quantity delta/total
- `remove_stock` — prompt for quantity delta/total
- `move_here` — move item to current_location (follow must be OFF)
- `assign` — assign unknown QR to item or location (triggers form)

### Items
| method | path | description |
|--------|------|-------------|
| GET | `/items` | List/search items. Params: `q`, `location_id` |
| POST | `/items` | Create item |
| GET | `/items/{id}` | Item detail + location path + labels |
| PUT | `/items/{id}` | Update item fields |
| POST | `/items/{id}/quantity` | Adjust quantity. Body: `{ mode: "total"|"delta", value: int }` |
| POST | `/items/{id}/move` | Move item to current_location |
| POST | `/items/{id}/labels` | Assign a QR serial to this item |
| DELETE | `/items/{id}/labels/{label_id}` | Remove a QR label |

### Locations
| method | path | description |
|--------|------|-------------|
| GET | `/locations` | Full location tree |
| POST | `/locations` | Create location |
| GET | `/locations/{id}` | Location detail + children + items |
| PUT | `/locations/{id}` | Update name or re-parent (parent_id) |

### References
| method | path | description |
|--------|------|-------------|
| GET | `/items/{id}/references` | List reference docs for item |
| POST | `/items/{id}/references` | Upload reference file |
| PUT | `/references/{id}` | Update name or display_order |
| DELETE | `/references/{id}` | Remove reference |

### Viewer
| method | path | description |
|--------|------|-------------|
| GET | `/viewer` | Returns current display payload: item details + top reference file content/URL |

---

## User Workflows

### Quick Reference Lookup
1. Scan item QR (scanner or phone)
2. API updates `last_scanned_item_id` (and `current_location` if follow ON)
3. Qt viewer polls `/api/viewer`, goes full-screen, shows reference doc + item details

### Adjust Quantity
1. Scan item QR → phone app shows item
2. Use +/- buttons, or type total quantity, or type signed delta
3. POST to `/items/{id}/quantity`

### Move Item(s) to New Location
1. Toggle follow **OFF** on phone app
2. Scan destination location QR → current_location updates
3. Scan item QR → phone app offers "Move Here" button
4. Repeat step 3 for additional items (batch)
5. Toggle follow **ON** when done

### Add New Item (unknown QR)
1. Scan a fresh QR from the roll
2. API returns `{ status: "unknown" }`
3. Phone/web shows tabbed form; "Item" tab auto-focused
4. Fill in name, part number, description; quantity defaults to 0; location defaults to current_location
5. Submit → item created, QR assigned via `item_labels`

### Add New Location (unknown QR)
1. Scan a fresh QR from the roll
2. Same tabbed form; switch to "Location" tab
3. Fill in name; parent defaults to current_location
4. Submit → location created, QR assigned

### Move Location in Hierarchy
1. Web UI only
2. Select location, change parent_id
3. All children and their items implicitly follow (no data migration needed — hierarchy is parent_id traversal)

### Assign Additional QR to Existing Item
1. Scan unknown QR
2. In form, select "Item" tab → choose "assign to existing" → search and select item
3. QR added to `item_labels`

---

## Implementation Order

1. DB schema + migrations
2. Core API: `/scan`, `/state`, `/items` CRUD, `/locations` CRUD
3. Web UI: item and location management, reference file upload
4. Phone PWA: camera view, action buttons, follow toggle
5. Qt tray app: viewer display, API polling
6. Qt HID scanner input capture
7. Reference file rendering (PDF viewer, image display, markdown render)
8. Polish: search, location tree UI, batch operations

---

## Open Design Items

- Qt display wake: resolved — GNOME/Wayland via `QDBusInterface` `org.gnome.ScreenSaver.SetActive(false)`
- Qt HID input capture mechanism on Linux (XInput vs. evdev vs. focused window)
- PDF rendering in Qt (Qt PDF module vs. embedded viewer)
- Markdown rendering in Qt (QTextBrowser supports basic HTML — convert md→html server-side or in-client)
- Authentication: single-user system, but should API be protected if server is LAN-only?
