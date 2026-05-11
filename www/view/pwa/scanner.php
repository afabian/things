<? function scanner() { ?>
<div id="camera-wrap">
    <video id="video" autoplay playsinline muted></video>
    <canvas id="canvas"></canvas>
    <div id="qr-indicator"></div>
</div>

<div id="bottom">
    <div id="status">
        <div id="status-name">Point camera at a QR code</div>
        <div id="status-detail"></div>
    </div>

    <div id="state-bar">
        <span id="cur-loc-label">Location: <span id="cur-loc">--</span></span>
        <button id="follow-btn" onclick="toggleFollow()">Follow: --</button>
    </div>

    <div id="btn-row">
        <button class="act" id="btn-add"    onclick="pressAdd()"    disabled>+Stock</button>
        <button class="act" id="btn-remove" onclick="pressRemove()" disabled>-Stock</button>
        <button class="act" id="btn-move"   onclick="pressMove()"   disabled>Move Here</button>
        <button class="act" id="btn-assign" onclick="pressAssign()" disabled>Assign</button>
    </div>

    <div id="response-bar"></div>
</div>

<!-- Quantity modal -->
<div id="qty-modal" class="modal">
    <div class="modal-box">
        <div class="modal-title" id="qty-title">Add Stock</div>
        <div class="modal-row">
            <button class="qty-step" onclick="stepQty(-10)">-10</button>
            <button class="qty-step" onclick="stepQty(-1)">-1</button>
            <input type="number" id="qty-input" value="1" min="0">
            <button class="qty-step" onclick="stepQty(1)">+1</button>
            <button class="qty-step" onclick="stepQty(10)">+10</button>
        </div>
        <div class="modal-row">
            <button class="modal-btn modal-btn-ok" onclick="submitQty()" style="flex:1">OK</button>
            <button class="modal-btn" onclick="closeQtyModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- Assign QR modal -->
<div id="assign-modal" class="modal">
    <div class="modal-box">
        <div class="modal-title">Assign QR: <span id="assign-qr-val" style="color:#8af;font-size:11px"></span></div>
        <div class="modal-row">
            <button class="tab-btn active" id="tab-item-btn" onclick="switchTab('item')">New Item</button>
            <button class="tab-btn"        id="tab-loc-btn"  onclick="switchTab('location')">New Location</button>
        </div>
        <div id="tab-item-form">
            <input type="text" id="ai-name" placeholder="Name*">
            <input type="text" id="ai-part" placeholder="Part #">
            <select id="ai-loc"><option value="">-- Location* --</option></select>
        </div>
        <div id="tab-loc-form" style="display:none">
            <input type="text" id="al-name" placeholder="Location name*">
            <select id="al-parent"><option value="">(root / no parent)</option></select>
        </div>
        <div class="modal-row" style="margin-top:4px">
            <button class="modal-btn modal-btn-ok" onclick="submitAssign()" style="flex:1">Create + Assign</button>
            <button class="modal-btn" onclick="closeAssignModal()">Cancel</button>
        </div>
    </div>
</div>

<script src="js/pwa.js"></script>
<? } ?>
