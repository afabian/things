#!/bin/bash
set -e
SERVER=10.0.0.10
DEST=/var/www/html/things

echo "Deploying to $SERVER:$DEST ..."
rsync -a --delete \
    --exclude='.git/' \
    --exclude='parsed/' \
    --exclude='*.md' \
    --exclude='deploy.sh' \
    --exclude='claude_notes.md' \
    /home/afabian/things/ \
    $SERVER:$DEST/

echo "Setting up server directories ..."
ssh $SERVER "
    mkdir -p $DEST/parsed/dev $DEST/parsed/prod $DEST/uploads
    chmod 777 $DEST/parsed $DEST/parsed/dev $DEST/parsed/prod $DEST/uploads
"

echo "Done. http://$SERVER/things/"
