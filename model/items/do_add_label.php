<?php

function do_add_label($item_id, $input)
{
$item_id   = (int)$item_id;
$qr_serial = db_esc($input['qr_serial'] ?? '');
if (!$item_id || !$qr_serial) return 0;
mysqli_safe_query(
    "INSERT INTO item_labels (item_id, qr_serial) VALUES ($item_id, '$qr_serial')",
    __FILE__, __LINE__
);
return (int)mysqli_insert_id($GLOBALS['fbx']['dbh']);
}
