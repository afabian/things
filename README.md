# Things

Electronics lab inventory and quick-reference system.

## What It Does

- Tracks location and quantity of lab items using QR codes
- Displays full-screen datasheets and pinouts on a desktop monitor when you scan an item
- Works simultaneously from a wireless HID scanner, a phone PWA, and a desktop viewer

## Components

| Component | Description |
|-----------|-------------|
| `www/` | PHP/MySQL web app (Firebox MVC) — inventory management + API |
| `wsserver/` | C++/Qt WebSocket server — pushes state changes to all clients |
| `viewer/` | C++/Qt desktop tray app — full-screen reference display |

---

## New Server Installation

These steps set up a fresh server. The server hosts the web app, WebSocket server, and desktop viewer.

### 1. Prerequisites

```bash
# Web server stack
sudo apt install nginx php8.3-fpm mysql-server git

# Qt 6 (for wsserver and viewer)
sudo apt install qt6-base-dev qt6-pdf-dev libqt6svg6-dev libqt6websockets6-dev \
                 cmake build-essential
```

### 2. Firebox MVC framework

```bash
git clone git@github.com:afabian/firebox.git /var/www/html/firebox
```

### 3. Clone this repo

```bash
git clone git@github.com:afabian/things.git ~/things
```

### 4. Database

```bash
# Create DB and users
sudo mysql << 'SQL'
CREATE DATABASE things;
CREATE USER 'things'@'%' IDENTIFIED BY 'things';
CREATE USER 'things_admin'@'%' IDENTIFIED BY 'things_admin';
GRANT ALL PRIVILEGES ON things.* TO 'things_admin'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON things.* TO 'things'@'%';
GRANT REFERENCES ON things.* TO 'things_admin'@'%';
FLUSH PRIVILEGES;
SQL

# Load schema
mysql -u things_admin -pthings_admin things < ~/things/schema.sql
```

### 5. Web server symlink

```bash
# Point nginx at the git clone
sudo ln -s /home/$USER/things/www /var/www/html/things

# Allow nginx (www-data) to traverse your home directory
chmod a+x /home/$USER

# Create runtime directories
mkdir -p ~/things/www/parsed/dev ~/things/www/parsed/prod ~/things/www/uploads
chmod 777 ~/things/www/parsed ~/things/www/parsed/dev \
          ~/things/www/parsed/prod ~/things/www/uploads
```

### 6. Nginx config

Add to `/etc/nginx/sites-enabled/default` inside the `server {}` block:

```nginx
# WebSocket proxy — before PHP location
location = /things/ws {
    proxy_pass http://127.0.0.1:8765;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 86400;
}

# PHP — before things rewrite to prevent rewrite loop
location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
}

# Clean URLs for things app
location = /things/scanner {
    rewrite ^ /things/index.php?go=pwa.scanner last;
}

location ~ ^/things/([a-zA-Z0-9_]+\.[a-zA-Z0-9_]+)/?$ {
    rewrite ^/things/([a-zA-Z0-9_.]+)/?$ /things/index.php?go=$1 last;
}
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### 7. Build and start the WebSocket server

```bash
cd ~/things/wsserver
cmake -B build -DCMAKE_BUILD_TYPE=Release
cmake --build build
bash setup-service.sh   # installs and starts the systemd service
```

### 8. Build the desktop viewer

```bash
cd ~/things/viewer
cmake -B build -DCMAKE_BUILD_TYPE=Release
cmake --build build
```

### 9. HID barcode scanner (optional)

Find the scanner's USB vendor and product IDs:

```bash
udevadm info /dev/input/by-id/YOUR-SCANNER | grep -E 'ID_VENDOR_ID|ID_MODEL_ID'
```

Create `/etc/udev/rules.d/99-barcode-scanner.rules`:

```
SUBSYSTEM=="input", ATTRS{idVendor}=="XXXX", ATTRS{idProduct}=="YYYY", \
    SYMLINK+="input/barcode-scanner", GROUP="input", MODE="0660"
```

```bash
sudo udevadm control --reload && sudo udevadm trigger
sudo usermod -aG input $USER   # re-login after this
```

Then set the device path in the viewer: Tray → Settings → Scanner device.

### 10. Auto-start the viewer on login

```bash
mkdir -p ~/.config/autostart
cat > ~/.config/autostart/things-viewer.desktop << EOF
[Desktop Entry]
Type=Application
Name=Things Viewer
Exec=$HOME/things/viewer/build/things-viewer
Icon=$HOME/things/viewer/cardboard-box.svg
X-GNOME-Autostart-enabled=true
EOF
```

---

## Deploying Updates

```bash
# On your dev machine — pushes changes and rebuilds server components
bash deploy.sh
```

To update the desktop viewer after code changes:

```bash
ssh server "cd ~/things && git pull && cd viewer && cmake --build build"
```

---

## Phone PWA

Open `http://server/things/scanner` in a mobile browser. No installation needed.
