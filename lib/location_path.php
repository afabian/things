<?php

function location_path($location_id)
{
$id = (int)$location_id;
if (!$id) return [];
$sql = "WITH RECURSIVE path AS (
    SELECT id, name, parent_id, 0 AS depth FROM locations WHERE id = $id
    UNION ALL
    SELECT l.id, l.name, l.parent_id, p.depth + 1
    FROM locations l INNER JOIN path p ON l.id = p.parent_id
)
SELECT id, name FROM path ORDER BY depth DESC";
$result = mysqli_safe_query($sql, __FILE__, __LINE__);
$path = [];
while ($row = mysqli_fetch_assoc($result))
{
$path[] = ['id' => (int)$row['id'], 'name' => $row['name']];
}
return $path;
}
