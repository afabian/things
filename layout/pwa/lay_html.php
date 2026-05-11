<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<title>Things Scanner</title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://unpkg.com/jsqr@1.4.0/dist/jsQR.js"></script>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; background: #111; color: #ddd; font-family: monospace; font-size: 13px; overflow: hidden; }
#camera-wrap { position: fixed; top: 0; left: 0; right: 0; bottom: 220px; background: #000; overflow: hidden; }
#video { width: 100%; height: 100%; object-fit: cover; display: block; }
#canvas { display: none; }
#qr-indicator { position: absolute; inset: 0; pointer-events: none; border: 3px solid transparent; transition: border-color 0.1s; }
#qr-indicator.flash { border-color: #4f4; }
#bottom { position: fixed; bottom: 0; left: 0; right: 0; height: 220px; background: #181818; border-top: 1px solid #2a2a2a; display: flex; flex-direction: column; padding: 6px; gap: 5px; }
#status { background: #222; border: 1px solid #2a2a2a; padding: 6px 8px; flex: 1; min-height: 0; overflow: hidden; }
#status-name { font-size: 14px; font-weight: bold; color: #eee; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
#status-detail { font-size: 11px; color: #888; margin-top: 2px; }
#state-bar { display: flex; justify-content: space-between; align-items: center; font-size: 11px; }
#cur-loc-label { color: #666; }
#cur-loc { color: #aaa; }
#follow-btn { background: #222; border: 1px solid #333; color: #aaa; padding: 2px 8px; font-size: 11px; font-family: monospace; }
#btn-row { display: flex; gap: 5px; }
.act { flex: 1; padding: 8px 2px; background: #222; border: 1px solid #333; color: #aaa; font-size: 12px; font-family: monospace; }
.act:disabled { opacity: 0.35; }
.act:not(:disabled):active { background: #2a2a2a; }
#response-bar { font-size: 11px; color: #888; text-align: center; min-height: 16px; }
/* Modals */
.modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 200; align-items: center; justify-content: center; }
.modal.show { display: flex; }
.modal-box { background: #1e1e1e; border: 1px solid #3a3a3a; padding: 14px; width: 290px; }
.modal-title { font-size: 13px; font-weight: bold; color: #eee; margin-bottom: 10px; }
.modal-row { display: flex; gap: 6px; margin-bottom: 8px; align-items: center; }
.modal-box input, .modal-box select, .modal-box textarea { background: #111; border: 1px solid #3a3a3a; color: #ddd; padding: 5px 6px; font-family: monospace; font-size: 13px; width: 100%; margin-bottom: 6px; }
.modal-box textarea { resize: none; }
.modal-btn { padding: 8px 14px; background: #252525; border: 1px solid #3a3a3a; color: #ccc; font-size: 12px; font-family: monospace; }
.modal-btn-ok { background: #1a3a1a; border-color: #2a5a2a; color: #8c8; }
.qty-step { padding: 8px 10px; background: #252525; border: 1px solid #3a3a3a; color: #ccc; font-size: 14px; font-family: monospace; }
#qty-input { width: 70px; text-align: center; font-size: 18px; }
.tab-btn { flex: 1; padding: 5px; background: #252525; border: 1px solid #333; color: #888; font-size: 12px; font-family: monospace; margin-bottom: 8px; }
.tab-btn.active { background: #1a2a3a; border-color: #2a4a6a; color: #8af; }
</style>
</head>
<body>
<?=$content['body']?>
</body>
</html>
