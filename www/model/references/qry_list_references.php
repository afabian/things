<?php

function qry_list_references($item_id)
{
$id     = (int)$item_id;
$result = mysqli_safe_query(
    "SELECT id, item_id, name, file_type, file_path, display_order
     FROM item_references WHERE item_id = $id ORDER BY display_order, id",
    __FILE__, __LINE__
);
$refs = [];
while ($row = mysqli_fetch_assoc($result))
{
$refs[] = [
    'id'            => (int)$row['id'],
    'item_id'       => (int)$row['item_id'],
    'name'          => $row['name'],
    'file_type'     => $row['file_type'],
    'file_path'     => $row['file_path'],
    'display_order' => (int)$row['display_order'],
];
}
return $refs;
}
