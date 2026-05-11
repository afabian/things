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

function buildTree(locs, parentId) {
    var children = locs.filter(function(l) {
        return parentId === null ? !l.parent_id : l.parent_id === parentId;
    });
    if (!children.length) return '';
    var html = '<table style="margin-bottom:2px"><tbody>';
    children.forEach(function(loc) {
        var kids = locs.filter(function(l) { return l.parent_id === loc.id; });
        html += '<tr>' +
            '<td style="width:16px;color:#555">&gt;</td>' +
            '<td><strong style="color:#ddd">' + esc(loc.name) + '</strong></td>' +
            '<td style="width:140px;color:#555" class="mono">' + esc(loc.qr_serial) + '</td>' +
            '<td style="width:70px;color:#555">' + kids.length + ' child' + (kids.length === 1 ? '' : 's') + '</td>' +
            '<td style="width:60px"><a href="#" onclick="editLoc(' + loc.id + ');return false">edit</a></td>' +
            '</tr>';
        if (kids.length) {
            html += '<tr><td></td><td colspan="4" style="padding-left:14px">' +
                buildTree(locs, loc.id) + '</td></tr>';
        }
    });
    html += '</tbody></table>';
    return html;
}

function loadLocations() {
    $.getJSON('?go=locations.tree', function(locs) {
        locations = locs;
        populateParentSelects();
        $('#tree-container').html(locs.length ? buildTree(locs, null) :
            '<div style="color:#555">No locations yet.</div>');
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
