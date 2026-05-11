<?php

function pre()
{
$input = api_input();
mysqli_safe_query("SET NAMES 'utf8'", __FILE__, __LINE__);
}

function list_items()
{
$q      = $input['q'] ?? '';
$loc_id = (int)($input['location_id'] ?? 0);
$items  = query('qry_list_items', $q, $loc_id);
api_exit($items);
}

function create()
{
$id = action('do_create_item', $input);
if (!$id) api_exit(['error' => 'Invalid input'], 400);
$item = query('qry_get_item', $id);
api_exit($item, 201);
}

function detail()
{
$id = (int)($input['id'] ?? 0);
if (!$id) api_exit(['error' => 'Missing id'], 400);
$item = query('qry_get_item', $id);
if (!$item) api_exit(['error' => 'Not found'], 404);
api_exit($item);
}

function update()
{
$id = (int)($input['id'] ?? 0);
if (!$id) api_exit(['error' => 'Missing id'], 400);
action('do_update_item', $id, $input);
$item = query('qry_get_item', $id);
if (!$item) api_exit(['error' => 'Not found'], 404);
api_exit($item);
}

function adjust_quantity()
{
$id = (int)($input['id'] ?? 0);
if (!$id) api_exit(['error' => 'Missing id'], 400);
action('do_adjust_quantity', $id, $input);
$item = query('qry_get_item', $id);
if (!$item) api_exit(['error' => 'Not found'], 404);
api_exit($item);
}

function move()
{
$id = (int)($input['id'] ?? 0);
if (!$id) api_exit(['error' => 'Missing id'], 400);
$ok = action('do_move_item', $id);
if (!$ok) api_exit(['error' => 'No current location set'], 400);
$item = query('qry_get_item', $id);
api_exit($item);
}

function add_label()
{
$id = (int)($input['id'] ?? 0);
if (!$id) api_exit(['error' => 'Missing id'], 400);
$label_id = action('do_add_label', $id, $input);
if (!$label_id) api_exit(['error' => 'Invalid input or duplicate QR'], 400);
$item = query('qry_get_item', $id);
api_exit($item);
}

function delete_label()
{
$label_id = (int)($input['label_id'] ?? 0);
if (!$label_id) api_exit(['error' => 'Missing label_id'], 400);
action('do_remove_label', $label_id);
api_exit(['success' => true]);
}

function post()
{
}
