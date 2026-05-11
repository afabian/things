<?php

function do_reorder_reference($item_id, $ref_id, $direction)
{
$item_id = (int)$item_id;
$ref_id  = (int)$ref_id;

// Fetch all refs in current order
$result = mysqli_safe_query(
    "SELECT id FROM item_references WHERE item_id = $item_id ORDER BY display_order ASC, id ASC",
    __FILE__, __LINE__
);
$ids = [];
while ($r = mysqli_fetch_assoc($result))
{
$ids[] = (int)$r['id'];
}

$pos = array_search($ref_id, $ids);
if ($pos === false) return;

// Swap with neighbor
if ($direction === 'up' && $pos > 0)
{
[$ids[$pos], $ids[$pos - 1]] = [$ids[$pos - 1], $ids[$pos]];
}
elseif ($direction === 'down' && $pos < count($ids) - 1)
{
[$ids[$pos], $ids[$pos + 1]] = [$ids[$pos + 1], $ids[$pos]];
}

// Renumber sequentially so display_order values are always clean
foreach ($ids as $order => $id)
{
mysqli_safe_query(
    "UPDATE item_references SET display_order = $order WHERE id = $id",
    __FILE__, __LINE__
);
}
}
