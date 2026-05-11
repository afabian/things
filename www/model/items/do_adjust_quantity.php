<?php

function do_adjust_quantity($id, $input)
{
$id    = (int)$id;
$mode  = $input['mode'] ?? 'delta';
$value = (int)($input['value'] ?? 0);
if ($mode === 'total')
{
$qty = max(0, $value);
mysqli_safe_query(
    "UPDATE items SET quantity = $qty WHERE id = $id",
    __FILE__, __LINE__
);
}
else
{
mysqli_safe_query(
    "UPDATE items SET quantity = GREATEST(0, quantity + $value) WHERE id = $id",
    __FILE__, __LINE__
);
}
}
