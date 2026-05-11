var allLocations = [];

function showMsg(text, ok) {
    $('#msg').text(text).attr('class', 'msg ' + (ok ? 'msg-ok' : 'msg-err'));
    setTimeout(function() { $('#msg').attr('class', 'msg'); }, 3000);
}

function esc(s) {
    return $('<div>').text(String(s||'')).html();
}

function locDepth(loc, map) {
    var d = 0, cur = loc;
    while (cur.parent_id && map[cur.parent_id]) { d++; cur = map[cur.parent_id]; if (d > 20) break; }
    return d;
}

function loadItem() {
    $.getJSON('?go=items.detail&id=' + ITEM_ID, function(item) {
        if (item.error) { $('#msg').text(item.error); return; }
        $('#page-title').text(item.name);
        $('#f-name').val(item.name);
        $('#f-part').val(item.part_number || '');
        $('#f-desc').val(item.description || '');
        $('#f-qty').val(item.quantity);
        $('#f-loc').val(item.location_id);
        $('#f-path').html(item.location_path.map(function(p) {
            return '<span>' + esc(p.name) + '</span>';
        }).join(''));
        renderLabels(item.labels);
        renderRefs(item.references);
    });
}

function loadLocations() {
    $.getJSON('?go=locations.tree', function(locs) {
        allLocations = locs;
        var map = {};
        locs.forEach(function(l) { map[l.id] = l; });
        var sel = $('#f-loc').empty();
        locs.forEach(function(l) {
            sel.append($('<option>').val(l.id).text('  '.repeat(locDepth(l, map)) + l.name));
        });
        loadItem();
    });
}

function saveItem() {
    $.ajax({
        url: '?go=items.update&id=' + ITEM_ID, method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            name:        $('#f-name').val().trim(),
            part_number: $('#f-part').val().trim(),
            description: $('#f-desc').val().trim(),
            location_id: parseInt($('#f-loc').val())
        }),
        success: function(item) {
            if (item.error) { showMsg(item.error, false); return; }
            showMsg('Saved', true);
            $('#page-title').text(item.name);
            $('#f-path').html(item.location_path.map(function(p) {
                return '<span>' + esc(p.name) + '</span>';
            }).join(''));
        }
    });
}

function adjQty(delta) {
    $.ajax({
        url: '?go=items.adjust_quantity&id=' + ITEM_ID, method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({mode: 'delta', value: delta}),
        success: function(item) { $('#f-qty').val(item.quantity); }
    });
}

function setQty() {
    $.ajax({
        url: '?go=items.adjust_quantity&id=' + ITEM_ID, method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({mode: 'total', value: parseInt($('#f-qty').val()) || 0}),
        success: function(item) { $('#f-qty').val(item.quantity); showMsg('Quantity updated', true); }
    });
}

function deleteItem() {
    $.ajax({
        url: '?go=items.delete&id=' + ITEM_ID, method: 'POST',
        success: function() { window.location = '?go=ui.items'; },
        error: function(xhr) {
            var r = xhr.responseJSON;
            showMsg(r && r.error ? r.error : 'Delete failed', false);
        }
    });
}

function renderLabels(labels) {
    var html = '';
    labels.forEach(function(l) {
        html += '<div class="row"><span class="tag mono">' + esc(l.qr_serial) + '</span>' +
            '<button class="btn-del" onclick="removeLabel(' + l.id + ')">x</button></div>';
    });
    $('#labels-list').html(html || '<div style="color:#555">No labels assigned.</div>');
}

function addLabel() {
    var serial = $('#new-label').val().trim();
    if (!serial) return;
    $.ajax({
        url: '?go=items.add_label&id=' + ITEM_ID, method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({qr_serial: serial}),
        success: function(item) {
            if (item.error) { showMsg(item.error, false); return; }
            $('#new-label').val('');
            renderLabels(item.labels);
        }
    });
}

function removeLabel(labelId) {
    $.post('?go=items.delete_label&id=' + ITEM_ID + '&label_id=' + labelId, loadItem);
}

function renderRefs(refs) {
    var html = '';
    refs.forEach(function(r, i) {
        html += '<div class="row">' +
            '<button onclick="moveRef(' + r.id + ',\'up\')"'   + (i === 0              ? ' disabled' : '') + '>^</button>' +
            '<button onclick="moveRef(' + r.id + ',\'down\')"' + (i === refs.length - 1 ? ' disabled' : '') + '>v</button>' +
            '<span class="tag">' + esc(r.file_type) + '</span>' +
            '<span>' + esc(r.name) + '</span>' +
            '<button class="btn-del" onclick="deleteRef(' + r.id + ')">x</button>' +
            '</div>';
    });
    $('#refs-list').html(html || '<div style="color:#555">No reference documents.</div>');

    // Populate AI source dropdown — only pdf and image refs are useful inputs
    var $sel = $('#ai-ref-id').empty().append('<option value="">-- choose source --</option>');
    refs.filter(function(r) { return r.file_type === 'pdf' || r.file_type === 'image'; })
        .forEach(function(r) { $sel.append($('<option>').val(r.id).text(r.name)); });
}

function moveRef(refId, direction) {
    $.ajax({
        url: '?go=references.reorder&item_id=' + ITEM_ID, method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({id: refId, direction: direction}),
        success: renderRefs
    });
}

function deleteRef(refId) {
    if (!confirm('Delete this reference?')) return;
    $.post('?go=references.delete_ref&id=' + refId, loadItem);
}

function aiExtract() {
    var refId = parseInt($('#ai-ref-id').val()) || 0;
    var url   = $('#ai-url').val().trim();
    var query = $('#ai-query').val().trim();
    if (!refId && !url) { showMsg('Select a source document or enter a URL', false); return; }
    if (!query)         { showMsg('Enter a query', false); return; }

    $('#ai-status').text('Thinking...');
    $('button').prop('disabled', true);

    $.ajax({
        url: '?go=references.ai_generate&item_id=' + ITEM_ID,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ref_id: refId, url: url, query: query}),
        timeout: 180000,
        success: function(refs) {
            $('button').prop('disabled', false);
            $('#ai-status').text('');
            if (refs.error) { showMsg(refs.error, false); return; }
            $('#ai-query').val('');
            $('#ai-url').val('');
            loadItem();
            showMsg('Created ' + refs.length + ' section' + (refs.length !== 1 ? 's' : ''), true);
        },
        error: function(xhr) {
            $('button').prop('disabled', false);
            $('#ai-status').text('');
            var r = xhr.responseJSON;
            showMsg(r && r.error ? r.error : 'AI request failed', false);
        }
    });
}

function uploadRef() {
    var fileEl = document.getElementById('ref-file');
    if (!fileEl.files.length) { showMsg('Select a file first', false); return; }
    var fd = new FormData();
    fd.append('file', fileEl.files[0]);
    fd.append('name', $('#ref-name').val().trim());
    $.ajax({
        url: '?go=references.upload&item_id=' + ITEM_ID, method: 'POST',
        data: fd, processData: false, contentType: false,
        success: function(ref) {
            if (ref.error) { showMsg(ref.error, false); return; }
            fileEl.value = '';
            $('#ref-name').val('');
            loadItem();
            showMsg('Uploaded: ' + ref.name, true);
        }
    });
}

loadLocations();
