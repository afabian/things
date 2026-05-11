<?php

function qry_get_reference($id)
{
$id     = (int)$id;
$result = mysqli_safe_query(
    "SELECT id, item_id, name, file_type, file_path, display_order
     FROM item_references WHERE id = $id",
    __FILE__, __LINE__
);
if (!mysqli_num_rows($result)) return null;
$row = mysqli_fetch_assoc($result);
return [
    'id'            => (int)$row['id'],
    'item_id'       => (int)$row['item_id'],
    'name'          => $row['name'],
    'file_type'     => $row['file_type'],
    'file_path'     => $row['file_path'],
    'display_order' => (int)$row['display_order'],
];
}
