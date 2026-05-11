<? function item_detail($item_id) { ?>
<?php if (!$item_id) { echo '<div class="msg msg-err">No item ID specified.</div>'; return; } ?>

<div style="margin-bottom:6px"><a href="?go=ui.items">&lt;- Items</a></div>
<div id="msg" class="msg"></div>

<h2 id="page-title">Item #<?=$item_id?></h2>

<div class="panel">
    <h3>Details</h3>
    <div class="row"><span class="lbl">Name*</span><input type="text" id="f-name" style="width:240px"></div>
    <div class="row"><span class="lbl">Part #</span><input type="text" id="f-part" style="width:180px"></div>
    <div class="row"><span class="lbl">Description</span><textarea id="f-desc" rows="3" style="width:340px"></textarea></div>
    <div class="row"><span class="lbl">Location</span><select id="f-loc" style="width:220px"></select></div>
    <div class="row"><span class="lbl">Quantity</span>
        <button onclick="adjQty(-1)">-</button>
        <input type="number" id="f-qty" style="width:60px;text-align:right" min="0">
        <button onclick="adjQty(1)">+</button>
        <button onclick="setQty()">set</button>
    </div>
    <div class="row"><span class="lbl">Path</span><span id="f-path" class="path" style="color:#666"></span></div>
    <div class="row"><span class="lbl"></span>
        <button class="btn-ok" onclick="saveItem()">Save</button>
        <button class="btn-del" onclick="if(confirm('Delete this item?'))deleteItem()">Delete</button>
    </div>
</div>

<div class="panel">
    <h3>QR Labels</h3>
    <div id="labels-list"></div>
    <div class="row" style="margin-top:6px">
        <input type="text" id="new-label" placeholder="QR serial" style="width:160px">
        <button class="btn-ok" onclick="addLabel()">Add Label</button>
    </div>
</div>

<div class="panel">
    <h3>Reference Documents</h3>
    <div id="refs-list"></div>
    <div style="margin-top:6px;padding-top:6px;border-top:1px solid #2a2a2a">
        <div class="row">
            <span class="lbl">File</span>
            <input type="file" id="ref-file" accept=".pdf,.png,.jpg,.jpeg,.gif,.webp,.svg,.md">
        </div>
        <div class="row">
            <span class="lbl">Display name</span>
            <input type="text" id="ref-name" style="width:200px" placeholder="(optional)">
        </div>
        <div class="row"><span class="lbl"></span>
            <button class="btn-ok" onclick="uploadRef()">Upload</button>
        </div>
    </div>
    <div style="margin-top:6px;padding-top:6px;border-top:1px solid #2a2a2a">
        <h3>AI Extract</h3>
        <div class="row">
            <span class="lbl">Source doc</span>
            <select id="ai-ref-id" style="width:220px"></select>
        </div>
        <div class="row">
            <span class="lbl">Query</span>
            <input type="text" id="ai-query" style="width:300px"
                placeholder="e.g. pinout and register table">
        </div>
        <div class="row">
            <span class="lbl"></span>
            <button class="btn-ok" onclick="aiExtract()">Extract</button>
            <span id="ai-status" style="color:#888;font-size:11px;margin-left:8px"></span>
        </div>
    </div>
</div>

<script>var ITEM_ID = <?=$item_id?>;</script>
<script src="js/item_detail.js"></script>
<? } ?>
