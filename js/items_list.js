var allLocations = [];

function showMsg(text, ok) {
    $('#msg').text(text).attr('class', 'msg ' + (ok ? 'msg-ok' : 'msg-err'));
    setTimeout(function() { $('#msg').attr('class', 'msg'); }, 3000);
}

function showCreate(show) {
    $('#create-panel').toggle(show);
    if (show) $('#c-name').focus();
}

function esc(s) {
    return $('<div>').text(String(s)).html();
}

function renderItems(items) {
    var html = '';
    items.forEach(function(it) {
        html += '<tr>' +
            '<td><a href="?go=ui.item&id=' + it.id + '">' + esc(it.name) + '</a></td>' +
            '<td class="mono">' + esc(it.part_number || '') + '</td>' +
            '<td class="qty">' + it.quantity + '</td>' +
            '<td>' + esc(it.location_name || '') + '</td>' +
            '<td><a href="?go=ui.item&id=' + it.id + '">edit</a></td>' +
            '</tr>';
    });
    $('#items-body').html(html || '<tr><td colspan="5" style="color:#555">No items found.</td></tr>');
}

function loadItems() {
    $.getJSON('?go=items.list_items&q=' + encodeURIComponent($('#q').val()), renderItems);
}

function locDepth(loc, map) {
    var d = 0, cur = loc;
    while (cur.parent_id && map[cur.parent_id]) { d++; cur = map[cur.parent_id]; if (d > 20) break; }
    return d;
}

function populateLocSelect(selectId) {
    var sel = $('#' + selectId);
    if (!sel.length) return;
    var map = {};
    allLocations.forEach(function(l) { map[l.id] = l; });
    sel.html('<option value="">-- choose location --</option>');
    allLocations.forEach(function(l) {
        sel.append($('<option>').val(l.id).text('  '.repeat(locDepth(l, map)) + l.name));
    });
}

function loadLocations(selectId) {
    if (allLocations.length) { populateLocSelect(selectId); return; }
    $.getJSON('?go=locations.tree', function(locs) {
        allLocations = locs;
        populateLocSelect(selectId);
    });
}

function createItem() {
    var loc = parseInt($('#c-loc').val());
    if (!$('#c-name').val().trim()) { showMsg('Name is required', false); return; }
    if (!loc) { showMsg('Location is required', false); return; }
    $.ajax({
        url: '?go=items.create', method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            name:        $('#c-name').val().trim(),
            part_number: $('#c-part').val().trim(),
            description: $('#c-desc').val().trim(),
            quantity:    parseInt($('#c-qty').val()) || 0,
            location_id: loc
        }),
        success: function(item) {
            if (item.error) { showMsg(item.error, false); return; }
            showCreate(false);
            $('#c-name, #c-part, #c-desc').val('');
            $('#c-qty').val('0');
            loadItems();
            showMsg('Created: ' + item.name, true);
        }
    });
}

loadItems();
loadLocations('c-loc');
