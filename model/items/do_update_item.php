<?php

function do_update_item($id, $input)
{
$id          = (int)$id;
$name        = db_esc($input['name'] ?? '');
$part_number = db_esc($input['part_number'] ?? '');
$description = db_esc($input['description'] ?? '');
if (!$id || !$name) return;
mysqli_safe_query(
    "UPDATE items SET name = '$name', part_number = '$part_number', description = '$description'
     WHERE id = $id",
    __FILE__, __LINE__
);
}
