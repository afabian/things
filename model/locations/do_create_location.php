<?php

function do_create_location($input)
{
$name      = db_esc($input['name'] ?? '');
$qr_serial = db_esc($input['qr_serial'] ?? '');
$parent_id = isset($input['parent_id']) && $input['parent_id'] ? (int)$input['parent_id'] : 'NULL';
if (!$name || !$qr_serial) return 0;
mysqli_safe_query(
    "INSERT INTO locations (name, parent_id, qr_serial)
     VALUES ('$name', $parent_id, '$qr_serial')",
    __FILE__, __LINE__
);
return (int)mysqli_insert_id($GLOBALS['fbx']['dbh']);
}
