<?php

function do_delete_reference($id)
{
$id = (int)$id;
if (!$id) return;
$result = mysqli_safe_query(
    "SELECT file_path FROM item_references WHERE id = $id",
    __FILE__, __LINE__
);
if (!mysqli_num_rows($result)) return;
$row  = mysqli_fetch_assoc($result);
$file = $GLOBALS['fbx']['site_root'] . $row['file_path'];
mysqli_safe_query("DELETE FROM item_references WHERE id = $id", __FILE__, __LINE__);
if (file_exists($file)) unlink($file);
}
