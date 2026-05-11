<?php

function pre()
{
$input = api_input();
mysqli_safe_query("SET NAMES 'utf8'", __FILE__, __LINE__);
}

function get()
{
$payload = query('qry_viewer_state');
api_exit($payload);
}

function post()
{
}
