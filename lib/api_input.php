<?php

function api_input()
{
$body = file_get_contents('php://input');
if ($body)
{
$parsed = json_decode($body, true);
if ($parsed !== null) return $parsed;
}
return array_merge($_GET, $_POST);
}
