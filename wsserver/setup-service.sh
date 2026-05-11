#!/bin/bash
# Run once on the server to install the things-ws systemd service.
# The binary must already be deployed (run deploy.sh first).
set -e

SERVICE_FILE=/var/www/html/things/wsserver/things-ws.service
SYSTEMD_DIR=/etc/systemd/system

echo "Installing things-ws.service ..."
sudo cp "$SERVICE_FILE" "$SYSTEMD_DIR/things-ws.service"
sudo systemctl daemon-reload
sudo systemctl enable things-ws
sudo systemctl start things-ws

echo ""
echo "Status:"
sudo systemctl status things-ws --no-pager
