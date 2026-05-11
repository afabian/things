<?php

function do_process_scan($qr_serial)
{
$esc = db_esc($qr_serial);

$result = mysqli_safe_query(
    "SELECT i.id, i.name, i.part_number, i.quantity, i.location_id
     FROM item_labels il JOIN items i ON il.item_id = i.id
     WHERE il.qr_serial = '$esc'",
    __FILE__, __LINE__
);
if (mysqli_num_rows($result))
{
$item    = mysqli_fetch_assoc($result);
$item_id = (int)$item['id'];
$loc_id  = (int)$item['location_id'];
mysqli_safe_query(
    "UPDATE app_state SET
         last_scanned_item_id = $item_id,
         last_scanned_qr = '$esc',
         current_location_id = IF(follow_mode = 1, $loc_id, current_location_id),
         updated_at = NOW()
     WHERE id = 1",
    __FILE__, __LINE__
);
notify_ws();
return [
    'type' => 'item',
    'item' => [
        'id'          => $item_id,
        'name'        => $item['name'],
        'part_number' => $item['part_number'],
        'quantity'    => (int)$item['quantity'],
        'location_id' => $loc_id,
    ],
];
}

$result = mysqli_safe_query(
    "SELECT * FROM locations WHERE qr_serial = '$esc'",
    __FILE__, __LINE__
);
if (mysqli_num_rows($result))
{
$location = mysqli_fetch_assoc($result);
$loc_id   = (int)$location['id'];
mysqli_safe_query(
    "UPDATE app_state SET
         current_location_id = $loc_id,
         last_scanned_qr = '$esc',
         updated_at = NOW()
     WHERE id = 1",
    __FILE__, __LINE__
);
notify_ws();
return [
    'type'     => 'location',
    'location' => [
        'id'        => $loc_id,
        'name'      => $location['name'],
        'parent_id' => $location['parent_id'] ? (int)$location['parent_id'] : null,
        'qr_serial' => $location['qr_serial'],
    ],
];
}

mysqli_safe_query(
    "UPDATE app_state SET last_scanned_qr = '$esc', updated_at = NOW() WHERE id = 1",
    __FILE__, __LINE__
);
notify_ws();
return ['type' => 'unknown', 'qr_serial' => $qr_serial];
}
