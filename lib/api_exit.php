<?php

function api_exit($data, $code = 200)
{
http_response_code($code);
header('Content-Type: application/json');
echo json_encode($data);
exit;
}
