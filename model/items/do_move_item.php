<?php

function do_move_item($item_id)
{
$item_id = (int)$item_id;
$state   = mysqli_fetch_assoc(mysqli_safe_query(
    "SELECT current_location_id FROM app_state WHERE id = 1",
    __FILE__, __LINE__
));
$loc_id = (int)($state['current_location_id'] ?? 0);
if (!$loc_id) return false;
mysqli_safe_query(
    "UPDATE items SET location_id = $loc_id WHERE id = $item_id",
    __FILE__, __LINE__
);
return true;
}
