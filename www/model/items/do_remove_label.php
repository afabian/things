<?php

function do_remove_label($label_id)
{
$id = (int)$label_id;
if (!$id) return;
mysqli_safe_query(
    "DELETE FROM item_labels WHERE id = $id",
    __FILE__, __LINE__
);
}
