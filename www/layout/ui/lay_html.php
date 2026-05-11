<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Things</title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #181818; color: #ccc; font-family: monospace; font-size: 12px; }
#nav { position: fixed; left: 0; top: 0; width: 140px; height: 100vh; background: #111; border-right: 1px solid #2a2a2a; padding: 6px 0; }
#nav .logo { font-size: 13px; color: #fff; font-weight: bold; padding: 4px 10px 8px; border-bottom: 1px solid #2a2a2a; margin-bottom: 4px; }
#nav a { display: block; padding: 3px 10px; color: #999; text-decoration: none; }
#nav a:hover { color: #fff; background: #1e1e1e; }
#nav a.active { color: #fff; background: #252525; border-left: 2px solid #4a8; }
#nav .section { padding: 8px 10px 2px; color: #555; font-size: 10px; text-transform: uppercase; }
#nav .state-box { margin: 8px 6px 0; padding: 4px 6px; background: #1a1a1a; border: 1px solid #2a2a2a; font-size: 10px; }
#nav .state-box .lbl { color: #555; }
#nav .state-box .val { color: #aaa; word-break: break-all; }
#nav .state-box a { padding: 2px 0; font-size: 10px; display: inline; }
#main { margin-left: 140px; padding: 10px; }
h2 { font-size: 12px; font-weight: bold; color: #eee; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px solid #2a2a2a; }
h3 { font-size: 11px; font-weight: bold; color: #ccc; margin: 10px 0 4px; }
table { width: 100%; border-collapse: collapse; }
th { text-align: left; padding: 3px 6px; color: #666; font-weight: normal; border-bottom: 1px solid #2a2a2a; font-size: 11px; }
td { padding: 3px 6px; border-bottom: 1px solid #1e1e1e; vertical-align: middle; }
tr:hover td { background: #1e1e1e; }
input[type=text], input[type=number], select, textarea {
    background: #222; border: 1px solid #383838; color: #ccc;
    padding: 2px 5px; font-family: monospace; font-size: 12px; outline: none;
}
input[type=text]:focus, input[type=number]:focus, select:focus, textarea:focus { border-color: #4a8; }
textarea { resize: vertical; }
button, input[type=submit] {
    background: #252525; border: 1px solid #3a3a3a; color: #ccc;
    padding: 2px 8px; cursor: pointer; font-size: 11px; font-family: monospace;
}
button:hover { background: #2e2e2e; border-color: #4a4a4a; color: #fff; }
.btn-ok { background: #1a2e1a; border-color: #2a4a2a; color: #8c8; }
.btn-ok:hover { background: #1e381e; }
.btn-del { background: #2e1a1a; border-color: #4a2a2a; color: #c88; }
.btn-del:hover { background: #381e1e; }
.row { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
.lbl { color: #666; width: 90px; flex-shrink: 0; text-align: right; }
.msg { padding: 3px 8px; margin-bottom: 6px; display: none; }
.msg-ok { background: #1a2e1a; color: #8c8; display: block; }
.msg-err { background: #2e1a1a; color: #c88; display: block; }
a { color: #5af; text-decoration: none; }
a:hover { text-decoration: underline; }
.path { color: #666; font-size: 11px; }
.path span + span::before { content: ' / '; }
.tag { display: inline-block; background: #222; border: 1px solid #333; padding: 1px 5px; margin: 1px; font-size: 11px; }
.panel { background: #1a1a1a; border: 1px solid #2a2a2a; padding: 8px; margin-top: 8px; }
.qty { text-align: right; }
.mono { font-family: monospace; }
</style>
</head>
<body>
<?php fbx_execute_plugins('prehtml'); ?>
<div id="nav">
    <div class="logo">Things</div>
    <div class="section">Inventory</div>
    <a href="?go=ui.items" <?=($fbx['action']=='ui.items')?'class="active"':''?>>Items</a>
    <a href="?go=ui.locations" <?=($fbx['action']=='ui.locations')?'class="active"':''?>>Locations</a>
    <div id="state-panel" class="state-box">
        <div class="lbl">location</div>
        <div class="val" id="nav-location">--</div>
        <div class="lbl" style="margin-top:3px">follow</div>
        <div class="val"><a href="#" id="nav-follow" onclick="toggleFollow();return false">--</a></div>
    </div>
</div>
<div id="main">
    <?=$content['body']?>
</div>
<script>
function applyState(s) {
    $('#nav-location').text(s.current_location_name || '(none)');
    $('#nav-follow').text(s.follow_mode ? 'ON' : 'OFF').css('color', s.follow_mode ? '#8c8' : '#c88');
}

function refreshState() {
    $.getJSON('?go=state.get', applyState);
}

function toggleFollow() {
    $.post('?go=state.toggle_follow', refreshState);
}

function connectStateWs() {
    var url = (location.protocol === 'https:' ? 'wss://' : 'ws://') + location.host + '/things/ws';
    var ws  = new WebSocket(url);
    ws.onmessage = function(e) { applyState(JSON.parse(e.data)); };
    ws.onclose   = function()  { setTimeout(connectStateWs, 3000); };
}

refreshState();
connectStateWs();
setInterval(refreshState, 30000); // fallback if WS is down
</script>
</body>
</html>
