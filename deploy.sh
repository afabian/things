#!/bin/bash
set -e
SERVER=10.0.0.10

echo "Pulling latest on server ..."
ssh $SERVER "cd ~/things && git pull"

echo "Rebuilding WebSocket server ..."
ssh $SERVER "cd ~/things/wsserver && cmake --build build"

echo "Restarting WebSocket server ..."
ssh $SERVER "sudo systemctl restart things-ws"

echo "Done. http://$SERVER/things/"
