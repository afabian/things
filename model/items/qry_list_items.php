<?php

function qry_list_items($search = '', $location_id = 0)
{
$where = [];
if ($search)
{
$esc = db_esc($search);
$where[] = "(i.name LIKE '%$esc%' OR i.part_number LIKE '%$esc%')";
}
if ($location_id)
{
$where[] = "i.location_id = " . (int)$location_id;
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
$result = mysqli_safe_query(
    "SELECT i.id, i.name, i.part_number, i.quantity, i.location_id, l.name AS location_name
     FROM items i LEFT JOIN locations l ON i.location_id = l.id
     $where_sql
     ORDER BY i.name",
    __FILE__, __LINE__
);
$items = [];
while ($row = mysqli_fetch_assoc($result))
{
$items[] = [
    'id'            => (int)$row['id'],
    'name'          => $row['name'],
    'part_number'   => $row['part_number'],
    'quantity'      => (int)$row['quantity'],
    'location_id'   => (int)$row['location_id'],
    'location_name' => $row['location_name'],
];
}
return $items;
}
