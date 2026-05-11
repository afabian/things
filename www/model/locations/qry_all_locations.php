<?php

function qry_all_locations()
{
$result = mysqli_safe_query(
    "SELECT id, name, parent_id, qr_serial
     FROM locations
     ORDER BY parent_id IS NULL DESC, parent_id, name",
    __FILE__, __LINE__
);
$locations = [];
while ($row = mysqli_fetch_assoc($result))
{
$locations[] = [
    'id'        => (int)$row['id'],
    'name'      => $row['name'],
    'parent_id' => $row['parent_id'] ? (int)$row['parent_id'] : null,
    'qr_serial' => $row['qr_serial'],
];
}
return $locations;
}
