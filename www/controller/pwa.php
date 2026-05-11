<?php

function pre()
{
}

function scanner()
{
$content['body'] = display('scanner');
}

function post()
{
return layout('lay_html');
}
