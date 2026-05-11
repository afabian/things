<?php

function qry_get_item($id)
{
$id = (int)$id;
$result = mysqli_safe_query(
    "SELECT i.*, l.name AS location_name
     FROM items i LEFT JOIN locations l ON i.location_id = l.id
     WHERE i.id = $id",
    __FILE__, __LINE__
);
if (!mysqli_num_rows($result)) return null;
$row = mysqli_fetch_assoc($result);

$labels_result = mysqli_safe_query(
    "SELECT id, qr_serial FROM item_labels WHERE item_id = $id ORDER BY id",
    __FILE__, __LINE__
);
$labels = [];
while ($label = mysqli_fetch_assoc($labels_result))
{
$labels[] = ['id' => (int)$label['id'], 'qr_serial' => $label['qr_serial']];
}

$refs_result = mysqli_safe_query(
    "SELECT id, name, file_type, file_path, display_order
     FROM item_references WHERE item_id = $id ORDER BY display_order, id",
    __FILE__, __LINE__
);
$refs = [];
while ($ref = mysqli_fetch_assoc($refs_result))
{
$refs[] = [
    'id'            => (int)$ref['id'],
    'name'          => $ref['name'],
    'file_type'     => $ref['file_type'],
    'file_path'     => $ref['file_path'],
    'display_order' => (int)$ref['display_order'],
];
}

return [
    'id'            => (int)$row['id'],
    'name'          => $row['name'],
    'part_number'   => $row['part_number'],
    'description'   => $row['description'],
    'quantity'      => (int)$row['quantity'],
    'location_id'   => (int)$row['location_id'],
    'location_name' => $row['location_name'],
    'location_path' => location_path($row['location_id']),
    'labels'        => $labels,
    'references'    => $refs,
];
}
