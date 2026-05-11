# Things

Electronics lab inventory and quick-reference system.

## What It Does

- Tracks location and quantity of lab items using QR codes
- Displays full-screen datasheets and pinouts on a desktop monitor when you scan an item
- Works simultaneously from a wireless HID scanner, a phone PWA, and a desktop viewer

## Components

| Component | Where |
|-----------|-------|
| PHP/MySQL API + Web UI | `www/` — deployed to server |
| WebSocket state server | `wsserver/` — runs as a systemd service |
| Qt desktop viewer | `viewer/` — built and run locally |

## Requirements

**Server (10.0.0.10)**
- Ubuntu Linux, nginx, PHP 8.x, MySQL
- Firebox MVC framework at `/var/www/html/firebox/`
- Qt 6 runtime libraries (for the WS server binary)

**Development machine**
- Ubuntu Linux
- Qt 6 dev tools: `sudo apt install qt6-base-dev qt6-pdf-dev libqt6svg6-dev libqt6websockets6-dev`

**Hardware**
- Pre-printed serial-number QR code roll
- Wireless HID QR scanner (optional — configure in viewer Settings)
- Phone with a browser (for PWA at `/things/scanner`)

## Deploy

```bash
# Deploy web files to server
bash deploy.sh

# Build and deploy WebSocket server
cd wsserver && make
rsync -a build/things-ws things-ws.service setup-service.sh user@10.0.0.10:/var/www/html/things/wsserver/

# First-time service install (run on server)
bash /var/www/html/things/wsserver/setup-service.sh
```

## Run the Desktop Viewer

```bash
cd viewer
make run
```

Use the tray icon → Settings to configure the server URL and HID scanner device.

## First-Time Database Setup

```bash
ssh 10.0.0.10 "mysql -u things_admin -pthings_admin things" < schema.sql
```
