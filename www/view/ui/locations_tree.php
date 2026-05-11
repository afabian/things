<? function locations_tree() { ?>
<h2>Locations
    <span style="float:right;font-weight:normal">
        <button class="btn-ok" onclick="showCreate(true)">+ New Location</button>
    </span>
</h2>

<div id="msg" class="msg"></div>

<div id="create-panel" class="panel" style="display:none;margin-bottom:8px">
    <h3>New Location</h3>
    <div class="row"><span class="lbl">Name*</span><input type="text" id="c-name" style="width:200px"></div>
    <div class="row"><span class="lbl">Parent</span><select id="c-parent" style="width:200px"><option value="">(root)</option></select></div>
    <div class="row"><span class="lbl">QR Serial*</span><input type="text" id="c-qr" style="width:160px" placeholder="scan or type"></div>
    <div class="row"><span class="lbl"></span>
        <button class="btn-ok" onclick="createLocation()">Create</button>
        <button onclick="showCreate(false)">Cancel</button>
    </div>
</div>

<div id="edit-panel" class="panel" style="display:none;margin-bottom:8px">
    <h3>Edit Location</h3>
    <input type="hidden" id="e-id">
    <div class="row"><span class="lbl">Name*</span><input type="text" id="e-name" style="width:200px"></div>
    <div class="row"><span class="lbl">Parent</span><select id="e-parent" style="width:200px"><option value="">(root)</option></select></div>
    <div class="row"><span class="lbl">QR Serial</span><span id="e-qr" style="color:#888"></span></div>
    <div class="row"><span class="lbl"></span>
        <button class="btn-ok" onclick="saveLocation()">Save</button>
        <button onclick="showEdit(false)">Cancel</button>
    </div>
</div>

<div id="tree-container"></div>
<script src="js/locations_tree.js"></script>
<? } ?>
