<?php

function do_delete_location($id)
{
$id = (int)$id;

$row = mysqli_fetch_assoc(mysqli_safe_query(
    "SELECT parent_id FROM locations WHERE id = $id",
    __FILE__, __LINE__
));
if (!$row) return ['error' => 'Location not found'];

$parent_id = $row['parent_id'] ? (int)$row['parent_id'] : null;

// Collect the full subtree (root + all descendants) using a recursive CTE
$res = mysqli_safe_query(
    "WITH RECURSIVE subtree AS (
         SELECT id FROM locations WHERE id = $id
         UNION ALL
         SELECT l.id FROM locations l JOIN subtree s ON l.parent_id = s.id
     )
     SELECT id FROM subtree",
    __FILE__, __LINE__
);
$subtree_ids = [];
while ($r = mysqli_fetch_assoc($res))
{
$subtree_ids[] = (int)$r['id'];
}
$id_list = implode(',', $subtree_ids);

// Root locations with items in the subtree cannot be deleted
if ($parent_id === null)
{
$cnt = mysqli_fetch_assoc(mysqli_safe_query(
    "SELECT COUNT(*) AS n FROM items WHERE location_id IN ($id_list)",
    __FILE__, __LINE__
));
if ((int)$cnt['n'] > 0)
return ['error' => 'Cannot delete a root location that has items — re-parent it first'];
}

// Move all items in the subtree up to the parent
if ($parent_id !== null)
{
mysqli_safe_query(
    "UPDATE items SET location_id = $parent_id WHERE location_id IN ($id_list)",
    __FILE__, __LINE__
);
}

// Delete all locations in the subtree (FK checks off to avoid ordering issues)
mysqli_safe_query("SET FOREIGN_KEY_CHECKS = 0", __FILE__, __LINE__);
mysqli_safe_query(
    "DELETE FROM locations WHERE id IN ($id_list)",
    __FILE__, __LINE__
);
mysqli_safe_query("SET FOREIGN_KEY_CHECKS = 1", __FILE__, __LINE__);

return ['success' => true];
}
