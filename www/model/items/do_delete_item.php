<?php

function do_delete_item($id)
{
$id = (int)$id;
if (!$id) return false;

$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/things/uploads/' . $id;

if (is_dir($upload_dir))
{
foreach (glob($upload_dir . '/*') ?: [] as $file)
{
unlink($file);
}
rmdir($upload_dir);
}

mysqli_safe_query(
    "DELETE FROM items WHERE id = $id",
    __FILE__, __LINE__
);

return true;
}
