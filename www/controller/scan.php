<?php

function pre()
{
$input = api_input();
mysqli_safe_query("SET NAMES 'utf8'", __FILE__, __LINE__);
}

function process()
{
$qr_serial = $input['qr_serial'] ?? '';
if (!$qr_serial) api_exit(['error' => 'Missing qr_serial'], 400);
$result = action('do_process_scan', $qr_serial);
api_exit($result);
}

function post()
{
}
