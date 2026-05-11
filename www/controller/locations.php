<?php

function pre()
{
$input = api_input();
mysqli_safe_query("SET NAMES 'utf8'", __FILE__, __LINE__);
}

function tree()
{
$locations = query('qry_all_locations');
api_exit($locations);
}

function create()
{
$id = action('do_create_location', $input);
if (!$id) api_exit(['error' => 'Invalid input'], 400);
$location = query('qry_get_location', $id);
api_exit($location, 201);
}

function detail()
{
$id = (int)($input['id'] ?? 0);
if (!$id) api_exit(['error' => 'Missing id'], 400);
$location = query('qry_get_location', $id);
if (!$location) api_exit(['error' => 'Not found'], 404);
api_exit($location);
}

function update()
{
$id = (int)($input['id'] ?? 0);
if (!$id) api_exit(['error' => 'Missing id'], 400);
action('do_update_location', $id, $input);
$location = query('qry_get_location', $id);
if (!$location) api_exit(['error' => 'Not found'], 404);
api_exit($location);
}

function post()
{
}
