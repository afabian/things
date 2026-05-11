<?php

function pre()
{
$input = api_input();
mysqli_safe_query("SET NAMES 'utf8'", __FILE__, __LINE__);
}

function list_refs()
{
$item_id = (int)($input['item_id'] ?? 0);
if (!$item_id) api_exit(['error' => 'Missing item_id'], 400);
$refs = query('qry_list_references', $item_id);
api_exit($refs);
}

function upload()
{
$item_id = (int)($input['item_id'] ?? 0);
if (!$item_id) api_exit(['error' => 'Missing item_id'], 400);
if (empty($_FILES['file'])) api_exit(['error' => 'No file uploaded'], 400);
$ref_id = action('do_create_reference', $item_id, $input, $_FILES['file']);
if (!$ref_id) api_exit(['error' => 'Upload failed — unsupported type or write error'], 400);
$ref = query('qry_get_reference', $ref_id);
api_exit($ref, 201);
}

function update()
{
$id = (int)($input['id'] ?? 0);
if (!$id) api_exit(['error' => 'Missing id'], 400);
action('do_update_reference', $id, $input);
$ref = query('qry_get_reference', $id);
if (!$ref) api_exit(['error' => 'Not found'], 404);
api_exit($ref);
}

function reorder()
{
$item_id   = (int)($input['item_id'] ?? 0);
$id        = (int)($input['id'] ?? 0);
$direction = $input['direction'] ?? '';
if (!$item_id || !$id || !in_array($direction, ['up', 'down']))
    api_exit(['error' => 'Invalid input'], 400);
action('do_reorder_reference', $item_id, $id, $direction);
api_exit(query('qry_list_references', $item_id));
}

function delete_ref()
{
$id = (int)($input['id'] ?? 0);
if (!$id) api_exit(['error' => 'Missing id'], 400);
action('do_delete_reference', $id);
api_exit(['success' => true]);
}

function ai_generate()
{
$item_id = (int)($input['item_id'] ?? 0);
$ref_id  = (int)($input['ref_id'] ?? 0);
$url     = trim($input['url'] ?? '');
$query   = trim($input['query'] ?? '');
if (!$item_id)       api_exit(['error' => 'Missing item_id'], 400);
if (!$ref_id && !$url) api_exit(['error' => 'Missing ref_id or url'], 400);
if (!$query)         api_exit(['error' => 'Missing query'], 400);
set_time_limit(180);
$results = action('do_ai_generate', $item_id, $ref_id, $query, $url);
if (isset($results['error'])) api_exit($results, 500);
api_exit($results, 201);
}

function post()
{
}
