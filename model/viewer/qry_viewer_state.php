<?php

function qry_viewer_state()
{
$state = mysqli_fetch_assoc(mysqli_safe_query(
    "SELECT last_scanned_item_id FROM app_state WHERE id = 1",
    __FILE__, __LINE__
));
$item_id = (int)($state['last_scanned_item_id'] ?? 0);
if (!$item_id) return ['item' => null, 'reference' => null];

$item_result = mysqli_safe_query(
    "SELECT id, name, part_number, quantity, location_id FROM items WHERE id = $item_id",
    __FILE__, __LINE__
);
if (!mysqli_num_rows($item_result)) return ['item' => null, 'reference' => null];
$item = mysqli_fetch_assoc($item_result);

$ref_result = mysqli_safe_query(
    "SELECT id, name, file_type, file_path FROM item_references
     WHERE item_id = $item_id ORDER BY display_order ASC, id ASC LIMIT 1",
    __FILE__, __LINE__
);
$reference = null;
if (mysqli_num_rows($ref_result))
{
$ref      = mysqli_fetch_assoc($ref_result);
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/things/';
$reference = [
    'id'        => (int)$ref['id'],
    'name'      => $ref['name'],
    'file_type' => $ref['file_type'],
    'url'       => $base_url . $ref['file_path'],
];
}

return [
    'item' => [
        'id'            => (int)$item['id'],
        'name'          => $item['name'],
        'part_number'   => $item['part_number'],
        'quantity'      => (int)$item['quantity'],
        'location_id'   => (int)$item['location_id'],
        'location_path' => location_path($item['location_id']),
    ],
    'reference' => $reference,
];
}
