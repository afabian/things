<?php

function pre()
{
}

function items()
{
$content['body'] = display('items_list');
}

function item()
{
$id = (int)($_GET['id'] ?? 0);
$content['body'] = display('item_detail', $id);
}

function locations()
{
$content['body'] = display('locations_tree');
}

function post()
{
return layout('lay_html');
}
