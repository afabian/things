<?php

function qry_get_state()
{
$result = mysqli_safe_query(
    "SELECT a.*, l.name AS current_location_name
     FROM app_state a
     LEFT JOIN locations l ON a.current_location_id = l.id
     WHERE a.id = 1",
    __FILE__, __LINE__
);
$row = mysqli_fetch_assoc($result);
if (!$row) return null;
return [
    'current_location_id'   => $row['current_location_id'] ? (int)$row['current_location_id'] : null,
    'current_location_name' => $row['current_location_name'],
    'follow_mode'           => (bool)$row['follow_mode'],
    'last_scanned_qr'       => $row['last_scanned_qr'],
    'last_scanned_item_id'  => $row['last_scanned_item_id'] ? (int)$row['last_scanned_item_id'] : null,
    'updated_at'            => $row['updated_at'],
];
}
