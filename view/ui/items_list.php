<? function items_list() { ?>
<h2>Items
    <span style="float:right;font-weight:normal">
        <input type="text" id="q" placeholder="search..." style="width:180px" oninput="loadItems()">
        &nbsp;<button class="btn-ok" onclick="showCreate(true)">+ New Item</button>
    </span>
</h2>

<div id="msg" class="msg"></div>

<div id="create-panel" class="panel" style="display:none;margin-bottom:8px">
    <h3>New Item</h3>
    <div class="row"><span class="lbl">Name*</span><input type="text" id="c-name" style="width:220px"></div>
    <div class="row"><span class="lbl">Part #</span><input type="text" id="c-part" style="width:180px"></div>
    <div class="row"><span class="lbl">Description</span><textarea id="c-desc" rows="2" style="width:320px"></textarea></div>
    <div class="row"><span class="lbl">Quantity</span><input type="number" id="c-qty" value="0" style="width:70px" min="0"></div>
    <div class="row"><span class="lbl">Location*</span><select id="c-loc" style="width:200px"></select></div>
    <div class="row"><span class="lbl"></span>
        <button class="btn-ok" onclick="createItem()">Create</button>
        <button onclick="showCreate(false)">Cancel</button>
    </div>
</div>

<table>
<thead><tr>
    <th>Name</th><th>Part #</th><th style="text-align:right">Qty</th><th>Location</th><th></th>
</tr></thead>
<tbody id="items-body"></tbody>
</table>
<script src="js/items_list.js"></script>
<? } ?>
