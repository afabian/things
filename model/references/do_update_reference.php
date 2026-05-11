<?php

function do_update_reference($id, $input)
{
$id            = (int)$id;
$name          = db_esc($input['name'] ?? '');
$display_order = (int)($input['display_order'] ?? 0);
if (!$id || !$name) return;
mysqli_safe_query(
    "UPDATE item_references SET name = '$name', display_order = $display_order WHERE id = $id",
    __FILE__, __LINE__
);
}
