<?php

function notify_ws()
{
$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
if ($sock === false) return;
@socket_sendto($sock, '1', 1, 0, '127.0.0.1', 8766);
socket_close($sock);
}
