<?php

function qry_get_location($id)
{
$id = (int)$id;
$result = mysqli_safe_query(
    "SELECT * FROM locations WHERE id = $id",
    __FILE__, __LINE__
);
if (!mysqli_num_rows($result)) return null;
$row = mysqli_fetch_assoc($result);

$children_result = mysqli_safe_query(
    "SELECT id, name, qr_serial FROM locations WHERE parent_id = $id ORDER BY name",
    __FILE__, __LINE__
);
$children = [];
while ($child = mysqli_fetch_assoc($children_result))
{
$children[] = ['id' => (int)$child['id'], 'name' => $child['name'], 'qr_serial' => $child['qr_serial']];
}

$items_result = mysqli_safe_query(
    "SELECT id, name, part_number, quantity FROM items WHERE location_id = $id ORDER BY name",
    __FILE__, __LINE__
);
$items = [];
while ($item = mysqli_fetch_assoc($items_result))
{
$items[] = [
    'id'          => (int)$item['id'],
    'name'        => $item['name'],
    'part_number' => $item['part_number'],
    'quantity'    => (int)$item['quantity'],
];
}

return [
    'id'        => (int)$row['id'],
    'name'      => $row['name'],
    'parent_id' => $row['parent_id'] ? (int)$row['parent_id'] : null,
    'qr_serial' => $row['qr_serial'],
    'path'      => location_path($id),
    'children'  => $children,
    'items'     => $items,
];
}
