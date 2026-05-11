var locations = [];

function esc(s) {
    return $('<div>').text(String(s||'')).html();
}

function showMsg(text, ok) {
    $('#msg').text(text).attr('class', 'msg ' + (ok ? 'msg-ok' : 'msg-err'));
    setTimeout(function() { $('#msg').attr('class', 'msg'); }, 3000);
}

function showCreate(show) {
    $('#create-panel').toggle(show);
    if (show) $('#c-name').focus();
}

function showEdit(show) {
    $('#edit-panel').toggle(show);
}

function buildTreeRows(locs, parentId, depth) {
    var children = locs.filter(function(l) {
        return parentId === null ? !l.parent_id : l.parent_id === parentId;
    });
    var html = '';
    children.forEach(function(loc) {
        var kids = locs.filter(function(l) { return l.parent_id === loc.id; });
        html += '<tr>' +
            '<td style="padding-left:' + (depth * 16 + 4) + 'px">' +
                '<strong style="color:#ddd">' + esc(loc.name) + '</strong></td>' +
            '<td class="mono" style="color:#555">' + esc(loc.qr_serial) + '</td>' +
            '<td style="color:#555">' + kids.length + ' ' + (kids.length === 1 ? 'child' : 'children') + '</td>' +
            '<td><a href="#" onclick="editLoc(' + loc.id + ');return false">edit</a></td>' +
            '</tr>';
        html += buildTreeRows(locs, loc.id, depth + 1);
    });
    return html;
}

function loadLocations() {
    $.getJSON('?go=locations.tree', function(locs) {
        locations = locs;
        populateParentSelects();
        var rows = buildTreeRows(locs, null, 0);
        $('#tree-container').html(rows
            ? '<table style="width:100%;border-collapse:collapse">' +
              '<colgroup><col><col style="width:160px"><col style="width:90px"><col style="width:50px"></colgroup>' +
              '<tbody>' + rows + '</tbody></table>'
            : '<div style="color:#555">No locations yet.</div>');
    });
}

function populateParentSelects() {
    ['c-parent', 'e-parent'].forEach(function(selId) {
        var sel = $('#' + selId);
        var cur = sel.val();
        sel.find('option:not(:first)').remove();
        locations.forEach(function(l) {
            sel.append($('<option>').val(l.id).text(l.name));
        });
        sel.val(cur);
    });
}

function createLocation() {
    var name = $('#c-name').val().trim();
    var qr   = $('#c-qr').val().trim();
    if (!name || !qr) { showMsg('Name and QR serial are required', false); return; }
    var parent = $('#c-parent').val();
    $.ajax({
        url: '?go=locations.create', method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({name: name, qr_serial: qr, parent_id: parent ? parseInt(parent) : null}),
        success: function(loc) {
            if (loc.error) { showMsg(loc.error, false); return; }
            $('#c-name, #c-qr').val('');
            showCreate(false);
            loadLocations();
            showMsg('Created: ' + loc.name, true);
        }
    });
}

function editLoc(id) {
    var loc = locations.find(function(l) { return l.id === id; });
    if (!loc) return;
    $('#e-id').val(id);
    $('#e-name').val(loc.name);
    $('#e-qr').text(loc.qr_serial);
    $('#e-parent').val(loc.parent_id || '');
    showEdit(true);
    $('#e-name').focus();
}

function deleteLocation() {
    var id  = parseInt($('#e-id').val());
    var loc = locations.find(function(l) { return l.id === id; });
    if (!confirm('Delete "' + (loc ? loc.name : 'this location') + '"?')) return;
    $.ajax({
        url: '?go=locations.delete&id=' + id, method: 'POST',
        success: function() {
            showEdit(false);
            loadLocations();
            showMsg('Deleted', true);
        },
        error: function(xhr) {
            var r = xhr.responseJSON;
            showMsg(r && r.error ? r.error : 'Delete failed', false);
        }
    });
}

function saveLocation() {
    var id     = parseInt($('#e-id').val());
    var name   = $('#e-name').val().trim();
    var parent = $('#e-parent').val();
    if (!name) { showMsg('Name is required', false); return; }
    $.ajax({
        url: '?go=locations.update&id=' + id, method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({name: name, parent_id: parent ? parseInt(parent) : null}),
        success: function(loc) {
            if (loc.error) { showMsg(loc.error, false); return; }
            showEdit(false);
            loadLocations();
            showMsg('Saved: ' + loc.name, true);
        }
    });
}

loadLocations();
