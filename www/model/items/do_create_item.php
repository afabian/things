<?php

function do_create_item($input)
{
$name        = db_esc($input['name'] ?? '');
$part_number = db_esc($input['part_number'] ?? '');
$description = db_esc($input['description'] ?? '');
$quantity    = max(0, (int)($input['quantity'] ?? 0));
$location_id = (int)($input['location_id'] ?? 0);
if (!$name || !$location_id) return 0;
mysqli_safe_query(
    "INSERT INTO items (name, part_number, description, quantity, location_id)
     VALUES ('$name', '$part_number', '$description', $quantity, $location_id)",
    __FILE__, __LINE__
);
return (int)mysqli_insert_id($GLOBALS['fbx']['dbh']);
}
