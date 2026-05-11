var lastQr      = null;
var lastResult  = null;
var scanCooldown = false;
var qtyMode     = 'add';
var assignType  = 'item';
var locations   = [];

var video, canvas, ctx;

$(function() {
    video  = document.getElementById('video');
    canvas = document.getElementById('canvas');
    ctx    = canvas.getContext('2d');
    startCamera();
    refreshState();
    setInterval(refreshState, 5000);
    loadLocations();
});

// --- Camera & Scanning ---

function startCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showResponse('Camera not available in this browser');
        return;
    }
    navigator.mediaDevices.getUserMedia({
        video: { facingMode: {ideal: 'environment'}, width: {ideal: 1280} }
    }).then(function(stream) {
        video.srcObject = stream;
        video.addEventListener('loadedmetadata', function() {
            video.play();
            requestAnimationFrame(scanFrame);
        });
    }).catch(function(err) {
        showResponse('Camera error: ' + err.message);
    });
}

function scanFrame() {
    requestAnimationFrame(scanFrame);
    if (video.readyState !== video.HAVE_ENOUGH_DATA) return;
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);
    var img  = ctx.getImageData(0, 0, canvas.width, canvas.height);
    var code = jsQR(img.data, img.width, img.height, {inversionAttempts: 'dontInvert'});
    if (code && code.data && !scanCooldown) {
        scanCooldown = true;
        setTimeout(function() { scanCooldown = false; }, 2000);
        flashIndicator();
        doScan(code.data);
    }
}

function flashIndicator() {
    $('#qr-indicator').addClass('flash');
    setTimeout(function() { $('#qr-indicator').removeClass('flash'); }, 300);
}

function doScan(qr) {
    lastQr = qr;
    $.ajax({
        url: '?go=scan.process', method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({qr_serial: qr}),
        success: function(result) {
            lastResult = result;
            updateStatus(result);
            refreshState();
        },
        error: function() { showResponse('Scan request failed'); }
    });
}

function updateStatus(result) {
    if (result.type === 'item') {
        var it = result.item;
        var label = it.name + (it.part_number ? '  [' + it.part_number + ']' : '');
        $('#status-name').text(label);
        $('#status-detail').text('Qty: ' + it.quantity);
        setButtons('item');
    } else if (result.type === 'location') {
        $('#status-name').text('Location: ' + result.location.name);
        $('#status-detail').text('Current location updated');
        setButtons('location');
    } else {
        $('#status-name').text('Unknown QR');
        $('#status-detail').text(result.qr_serial);
        setButtons('unknown');
    }
}

function setButtons(type) {
    var item    = type === 'item';
    var unknown = type === 'unknown';
    $('#btn-add, #btn-remove, #btn-move').prop('disabled', !item);
    $('#btn-assign').prop('disabled', !unknown);
}

function showResponse(msg) {
    $('#response-bar').text(msg);
    setTimeout(function() { $('#response-bar').text(''); }, 3000);
}

// --- State bar ---

function refreshState() {
    $.getJSON('?go=state.get', function(s) {
        $('#cur-loc').text(s.current_location_name || '--');
        var f = s.follow_mode;
        $('#follow-btn').text('Follow: ' + (f ? 'ON' : 'OFF'))
            .css('color', f ? '#8c8' : '#c88');
    });
}

function toggleFollow() {
    $.post('?go=state.toggle_follow', refreshState);
}

// --- Quantity modal ---

function pressAdd()    { openQtyModal('add'); }
function pressRemove() { openQtyModal('remove'); }

function openQtyModal(mode) {
    qtyMode = mode;
    $('#qty-title').text(mode === 'add' ? 'Add Stock' : 'Remove Stock');
    $('#qty-input').val('1');
    $('#qty-modal').addClass('show');
    setTimeout(function() { $('#qty-input').focus().select(); }, 100);
}

function closeQtyModal() { $('#qty-modal').removeClass('show'); }

function stepQty(delta) {
    var v = parseInt($('#qty-input').val()) || 0;
    $('#qty-input').val(Math.max(0, v + delta));
}

function submitQty() {
    if (!lastResult || lastResult.type !== 'item') return;
    var val   = parseInt($('#qty-input').val()) || 0;
    var delta = qtyMode === 'add' ? val : -val;
    $.ajax({
        url: '?go=items.adjust_quantity&id=' + lastResult.item.id,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({mode: 'delta', value: delta}),
        success: function(item) {
            closeQtyModal();
            $('#status-detail').text('Qty: ' + item.quantity);
            showResponse('Quantity: ' + item.quantity);
            lastResult.item.quantity = item.quantity;
        }
    });
}

// --- Move ---

function pressMove() {
    if (!lastResult || lastResult.type !== 'item') return;
    $.ajax({
        url: '?go=items.move&id=' + lastResult.item.id,
        method: 'POST',
        success: function(item) {
            showResponse('Moved to current location');
            refreshState();
        },
        error: function(xhr) {
            var r = xhr.responseJSON;
            showResponse(r && r.error ? r.error : 'Move failed');
        }
    });
}

// --- Assign modal ---

function pressAssign() {
    if (!lastQr) return;
    $('#assign-qr-val').text(lastQr);
    switchTab('item');
    $('#ai-name, #ai-part, #al-name').val('');
    populateLocDropdowns();
    $('#assign-modal').addClass('show');
    setTimeout(function() { $('#ai-name').focus(); }, 100);
}

function closeAssignModal() { $('#assign-modal').removeClass('show'); }

function switchTab(tab) {
    assignType = tab;
    if (tab === 'item') {
        $('#tab-item-form').show();
        $('#tab-loc-form').hide();
        $('#tab-item-btn').addClass('active');
        $('#tab-loc-btn').removeClass('active');
    } else {
        $('#tab-item-form').hide();
        $('#tab-loc-form').show();
        $('#tab-item-btn').removeClass('active');
        $('#tab-loc-btn').addClass('active');
    }
}

function loadLocations() {
    $.getJSON('?go=locations.tree', function(locs) { locations = locs; });
}

function populateLocDropdowns() {
    ['#ai-loc', '#al-parent'].forEach(function(sel) {
        var $s = $(sel);
        $s.find('option:not(:first)').remove();
        locations.forEach(function(l) {
            $s.append($('<option>').val(l.id).text(l.name));
        });
    });
}

function submitAssign() {
    if (assignType === 'item') {
        var name = $('#ai-name').val().trim();
        var loc  = parseInt($('#ai-loc').val());
        if (!name || !loc) { showResponse('Name and location required'); return; }
        $.ajax({
            url: '?go=items.create', method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                name: name, part_number: $('#ai-part').val().trim(),
                description: '', quantity: 0, location_id: loc
            }),
            success: function(item) {
                $.ajax({
                    url: '?go=items.add_label&id=' + item.id, method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({qr_serial: lastQr}),
                    success: function() {
                        closeAssignModal();
                        lastResult = {type: 'item', item: item};
                        updateStatus(lastResult);
                        showResponse('Created: ' + item.name);
                    }
                });
            }
        });
    } else {
        var locName = $('#al-name').val().trim();
        var parent  = $('#al-parent').val();
        if (!locName) { showResponse('Location name required'); return; }
        $.ajax({
            url: '?go=locations.create', method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                name: locName, qr_serial: lastQr,
                parent_id: parent ? parseInt(parent) : null
            }),
            success: function(loc) {
                closeAssignModal();
                lastResult = {type: 'location', location: loc};
                updateStatus(lastResult);
                locations.push({id: loc.id, name: loc.name, parent_id: loc.parent_id, qr_serial: loc.qr_serial});
                showResponse('Location created: ' + loc.name);
                refreshState();
            }
        });
    }
}
