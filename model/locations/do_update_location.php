<?php

function do_update_location($id, $input)
{
$id   = (int)$id;
$name = db_esc($input['name'] ?? '');
if (!$id || !$name) return;
$parent_id = (array_key_exists('parent_id', $input) && $input['parent_id'] !== null)
    ? (int)$input['parent_id']
    : 'NULL';
if ($parent_id === $id) return;
mysqli_safe_query(
    "UPDATE locations SET name = '$name', parent_id = $parent_id WHERE id = $id",
    __FILE__, __LINE__
);
}
