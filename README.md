# Things

Electronics lab inventory and quick-reference system.

## What It Does

- Tracks the location and quantity of items in your lab using QR codes
- Displays full-screen datasheets, pinouts, and reference documents when you scan an item
- Works with a wireless QR scanner, a phone, and a desktop PC simultaneously

## Components

| Component | Description |
|-----------|-------------|
| Web backend | PHP/MySQL API — the central brain |
| Web UI | Browser-based inventory management |
| Phone app | PWA — scan QR codes, adjust quantities, move items |
| Desktop viewer | Qt app — full-screen reference display, tray icon |

## Requirements

- PHP 8.x + MySQL (server at 10.0.0.10)
- Firebox MVC framework
- Qt 6 (for desktop viewer)
- A roll of pre-printed serial-number QR codes
- A wireless HID QR scanner (optional)
- A phone with a browser (for PWA)

## Setup

*(Installation instructions to be written once framework is ready.)*
