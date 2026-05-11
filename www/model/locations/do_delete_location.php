<?php

function do_delete_location($id)
{
$id = (int)$id;

$children = mysqli_fetch_assoc(mysqli_safe_query(
    "SELECT COUNT(*) AS n FROM locations WHERE parent_id = $id",
    __FILE__, __LINE__
));
if ((int)$children['n'] > 0)
return ['error' => 'Location has child locations — re-parent or delete them first'];

$items = mysqli_fetch_assoc(mysqli_safe_query(
    "SELECT COUNT(*) AS n FROM items WHERE location_id = $id",
    __FILE__, __LINE__
));
if ((int)$items['n'] > 0)
return ['error' => 'Location still has items — move them first'];

mysqli_safe_query("DELETE FROM locations WHERE id = $id", __FILE__, __LINE__);
return ['success' => true];
}
