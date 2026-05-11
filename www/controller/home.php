<?php

function pre()
{
}

function index()
{
$content['body'] = '<h1>Things</h1><p>Up and running.</p>';
}

function post()
{
return layout('lay_html');
}
