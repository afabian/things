<?php

function pre()
{
$input = api_input();
mysqli_safe_query("SET NAMES 'utf8'", __FILE__, __LINE__);
}

function get()
{
$state = query('qry_get_state');
api_exit($state);
}

function toggle_follow()
{
action('do_toggle_follow');
$state = query('qry_get_state');
api_exit($state);
}

function post()
{
}
